<?php
declare(strict_types=1);
namespace Helhum\SentryTypo3;

use Helhum\SentryTypo3\Integration\BeforeEventListener;
use Helhum\SentryTypo3\Integration\Typo3Integration;
use Http\Adapter\Guzzle6\Client;
use Jean85\PrettyVersions;
use PackageVersions\Versions;
use Sentry\ClientBuilder;
use Sentry\Integration\FatalErrorListenerIntegration;
use Sentry\State\Hub;

final class Sentry
{
    /**
     * @var bool
     */
    private static $initialized = false;

    public static function initializeOnce(): void
    {
        if (self::$initialized) {
            return;
        }
        self::$initialized = true;
        $defaultOptions = [
            'dsn' => null,
            'project_root' => getenv('TYPO3_PATH_APP'),
            'in_app_exclude' => [
                getenv('TYPO3_PATH_APP') . '/private',
                getenv('TYPO3_PATH_APP') . '/public',
                getenv('TYPO3_PATH_APP') . '/var',
                getenv('TYPO3_PATH_APP') . '/vendor',
            ],
            'prefixes' => [
                getenv('TYPO3_PATH_APP'),
            ],
            'environment' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['environment'] ?? 'production',
            'release' => PrettyVersions::getVersion(Versions::ROOT_PACKAGE_NAME)->getShortCommitHash(),
            'default_integrations' => false,
            'integrations' => [
                new FatalErrorListenerIntegration(),
            ],
            'before_send' => [BeforeEventListener::class, 'onBeforeSend'],
            'send_default_pii' => false,
            'error_types' => E_ALL & ~(E_STRICT | E_NOTICE | E_DEPRECATED | E_USER_DEPRECATED),
        ];
        $options = array_replace($defaultOptions, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['sentry'] ?? []);
        unset($options['typo3_integrations']);
        $httpOptions = $GLOBALS['TYPO3_CONF_VARS']['HTTP'];
        $httpOptions['verify'] = filter_var($httpOptions['verify'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $httpOptions['verify'];
        if (empty($httpOptions['handler'])) unset($httpOptions['handler']);
        $typo3HttpClient = Client::createWithConfig($httpOptions);
        $clientBuilder = ClientBuilder::create($options);
        $clientBuilder->setHttpClient($typo3HttpClient);
        Hub::getCurrent()->bindClient($clientBuilder->getClient());
    }
}
