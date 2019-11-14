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

class RequestContext implements ContextInterface
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
        return (class_exists(Environment::class) && !Environment::isCli());
    }

    public function addToEvent(Event $event): void
    {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? ServerRequestFactory::fromGlobals();
        $client = Hub::getCurrent()->getClient();
        if ($request === null || $client === null) {
            return;
        }

        $options = $client->getOptions();
        $requestData = [
            'url' => (string) $request->getUri(),
            'method' => $request->getMethod(),
        ];

        if ($request->getUri()->getQuery()) {
            $requestData['query_string'] = $request->getUri()->getQuery();
        }

        if ($options->shouldSendDefaultPii()) {
            $serverParams = $request->getServerParams();

            if (isset($serverParams['REMOTE_ADDR'])) {
                $requestData['env']['REMOTE_ADDR'] = $serverParams['REMOTE_ADDR'];
            }

            $requestData['cookies'] = $request->getCookieParams();
            $requestData['headers'] = $request->getHeaders();

            $userContext = $event->getUserContext();

            if (null === $userContext->getIpAddress() && isset($serverParams['REMOTE_ADDR'])) {
                $userContext->setIpAddress($serverParams['REMOTE_ADDR']);
            }
        } else {
            $requestData['headers'] = $this->removePiiFromHeaders($request->getHeaders());
        }

        $requestBody = $this->captureRequestBody($options, $request);

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
     * the parsing fails then the raw data is returned. If there are submitted
     * fields or files, all of their information are parsed and returned.
     *
     * @param Options                $options       The options of the client
     * @param ServerRequestInterface $serverRequest The server request
     *
     * @return mixed
     */
    private function captureRequestBody(Options $options, ServerRequestInterface $serverRequest)
    {
        $maxRequestBodySize = $options->getMaxRequestBodySize();
        $requestBody = $serverRequest->getBody();

        if (
            'none' === $maxRequestBodySize ||
            ('small' === $maxRequestBodySize && $requestBody->getSize() > self::REQUEST_BODY_SMALL_MAX_CONTENT_LENGTH) ||
            ('medium' === $maxRequestBodySize && $requestBody->getSize() > self::REQUEST_BODY_MEDIUM_MAX_CONTENT_LENGTH)
        ) {
            return null;
        }

        $requestData = $serverRequest->getParsedBody();
        $requestData = array_merge(
            $this->parseUploadedFiles($serverRequest->getUploadedFiles()),
            \is_array($requestData) ? $requestData : []
        );

        if (!empty($requestData)) {
            return $requestData;
        }

        if ('application/json' === $serverRequest->getHeaderLine('Content-Type')) {
            try {
                return JSON::decode($requestBody->getContents());
            } catch (JsonException $exception) {
                // Fallback to returning the raw data from the request body
            }
        }

        return $requestBody->getContents();
    }

    /**
     * Create an array with the same structure as $uploadedFiles, but replacing
     * each UploadedFileInterface with an array of info.
     *
     * @param array $uploadedFiles The uploaded files info from a PSR-7 server request
     *
     * @return array
     */
    private function parseUploadedFiles(array $uploadedFiles): array
    {
        $result = [];

        foreach ($uploadedFiles as $key => $item) {
            if ($item instanceof UploadedFileInterface) {
                $result[$key] = [
                    'client_filename' => $item->getClientFilename(),
                    'client_media_type' => $item->getClientMediaType(),
                    'size' => $item->getSize(),
                ];
            } elseif (\is_array($item)) {
                $result[$key] = $this->parseUploadedFiles($item);
            } else {
                throw new \UnexpectedValueException(sprintf('Expected either an object implementing the "%s" interface or an array. Got: "%s".', UploadedFileInterface::class, \is_object($item) ? \get_class($item) : \gettype($item)));
            }
        }

        return $result;
    }
}
