<?php
declare(strict_types=1);
namespace Helhum\SentryTypo3\Integration;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Sentry\Event;
use Sentry\Exception\JsonException;
use Sentry\Options;
use Sentry\State\Hub;
use Sentry\Util\JSON;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class RequestContext87 implements ContextInterface
{
    /**
     * This constant represents the size limit in bytes beyond which the body
     * of the request is not captured when the `max_request_body_size` option
     * is set to `small`.
     */
    private const REQUEST_BODY_SMALL_MAX_CONTENT_LENGTH = 10 ** 3;

    /**
     * This constant represents the size limit in bytes beyond which the body
     * of the request is not captured when the `max_request_body_size` option
     * is set to `medium`.
     */
    private const REQUEST_BODY_MEDIUM_MAX_CONTENT_LENGTH = 10 ** 4;

    public function appliesToEvent(Event $event): bool
    {
        return class_exists(Environment::class) === false
            && defined('TYPO3_REQUESTTYPE')
            && defined('TYPO3_REQUESTTYPE_CLI')
            && TYPO3_REQUESTTYPE !== TYPO3_REQUESTTYPE_CLI
            ;
    }

    public function addToEvent(Event $event): void
    {
        $client = Hub::getCurrent()->getClient();
        if ($client === null) {
            return;
        }

        $options = $client->getOptions();
        $requestData = [
            'url' => GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'),
            'method' => $_SERVER['REQUEST_METHOD'],
        ];

        if (GeneralUtility::getIndpEnv('QUERY_STRING')) {
            $requestData['query_string'] = GeneralUtility::getIndpEnv('QUERY_STRING');
        }

        if ($options->shouldSendDefaultPii()) {
            $serverParams = $_SERVER;

            if (isset($serverParams['REMOTE_ADDR'])) {
                $requestData['env']['REMOTE_ADDR'] = $serverParams['REMOTE_ADDR'];
            }

            $requestData['cookies'] = $_COOKIE;
            $requestData['headers'] = $this->prepareHeaders($_SERVER);
        } else {
            $requestData['headers'] = $this->removePiiFromHeaders($this->prepareHeaders($_SERVER));
        }

        $requestBody = $this->captureRequestBody($options);

        if (!empty($requestBody)) {
            $requestData['data'] = $requestBody;
        }

        $event->setRequest($requestData);
    }

    /**
     * Removes headers containing potential PII.
     *
     * @param array<string, array<int, string>> $headers Array containing request headers
     *
     * @return array<string, array<int, string>>
     */
    private function removePiiFromHeaders(array $headers): array
    {
        $keysToRemove = ['authorization', 'cookie', 'set-cookie', 'remote_addr'];

        return array_filter(
            $headers,
            static function (string $key) use ($keysToRemove): bool {
                return !\in_array(strtolower($key), $keysToRemove, true);
            },
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Gets the decoded body of the request, if available. If the Content-Type
     * header contains "application/json" then the content is decoded and if
     * the parsing fails then the raw data is returned.
     *
     * @param Options $options The options of the client
     *
     * @return mixed
     */
    private function captureRequestBody(Options $options)
    {
        $maxRequestBodySize = $options->getMaxRequestBodySize();
        $requestData = file_get_contents('php://input');

        if (
            'none' === $maxRequestBodySize ||
            ('small' === $maxRequestBodySize && mb_strlen($requestData) > self::REQUEST_BODY_SMALL_MAX_CONTENT_LENGTH) ||
            ('medium' === $maxRequestBodySize && mb_strlen($requestData) > self::REQUEST_BODY_MEDIUM_MAX_CONTENT_LENGTH)
        ) {
            return null;
        }

        try {
            return JSON::decode($requestData);
        } catch (\Exception $exception) {
            // Fallback to returning the raw data from the request body
        }

        return $requestData;
    }

    /**
     * Fetch headers from $_SERVER variables
     * which are only the ones starting with HTTP_* and CONTENT_*
     *
     * @param array $server
     * @return array
     */
    private function prepareHeaders(array $server)
    {
        $headers = [];
        foreach ($server as $key => $value) {
            if (strpos($key, 'HTTP_COOKIE') === 0) {
                // Cookies are handled using the $_COOKIE superglobal
                continue;
            }
            if (!empty($value)) {
                if (strpos($key, 'HTTP_') === 0) {
                    $name = str_replace('_', ' ', substr($key, 5));
                    $name = str_replace(' ', '-', ucwords(strtolower($name)));
                    $name = strtolower($name);
                    $headers[$name] = $value;
                } elseif (strpos($key, 'CONTENT_') === 0) {
                    $name = substr($key, 8); // Content-
                    $name = 'Content-' . (($name === 'MD5') ? $name : ucfirst(strtolower($name)));
                    $name = strtolower($name);
                    $headers[$name] = $value;
                }
            }
        }
        return $headers;
    }
}
