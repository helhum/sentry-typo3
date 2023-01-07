<?php

declare(strict_types=1);

namespace Helhum\SentryTypo3;

use Composer\InstalledVersions;
use GuzzleHttp\Client as GuzzleHttpClient;
use Http\Adapter\Guzzle7\Client;
use Psr\Log\LoggerInterface;
use Sentry\Client as SentryClient;
use Sentry\ClientBuilder;
use Sentry\ClientInterface;
use Sentry\HttpClient\HttpClientFactory;
use Sentry\Integration\IntegrationInterface;
use Sentry\Transport\DefaultTransportFactory;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\ResponseFactory;
use TYPO3\CMS\Core\Http\StreamFactory;
use TYPO3\CMS\Core\Http\UriFactory;

final class SentryClientFactory
{
    private const SDK_IDENTIFIER =  SentryClient::SDK_IDENTIFIER . ' - typo3';
    private const SDK_VERSION = SentryClient::SDK_VERSION;

    /**
     * @param IntegrationInterface[] $integrations
     */
    public function __construct(
        private readonly array $integrations,
        private readonly LoggerInterface $logger,
        private readonly GuzzleHttpClient $typo3HttpClient,
    ) {
    }

    public function createClient(): ClientInterface
    {
        $projectPath = Environment::getProjectPath();
        $defaultOptions = [
            'dsn' => $_SERVER['SENTRY_DSN'] ?? null,
            'in_app_exclude' => [
                $projectPath . '/private',
                $projectPath . '/public',
                $projectPath . '/var',
                $projectPath . '/vendor',
            ],
            'prefixes' => [
                $projectPath . '/public',
                $projectPath . '/private',
                $projectPath,
            ],
            'environment' => $_SERVER['SENTRY_ENVIRONMENT'] ?? $GLOBALS['TYPO3_CONF_VARS']['SYS']['environment'] ?? str_replace('/', '-', (string)(Environment::getContext())),
            'release' => $_SERVER['SENTRY_RELEASE'] ?? InstalledVersions::getPrettyVersion(InstalledVersions::getRootPackage()['name']),
            'send_default_pii' => false,
            'attach_stacktrace' => true,
            'error_types' => E_ALL & ~(E_STRICT | E_NOTICE | E_DEPRECATED | E_USER_DEPRECATED),
        ];
        $options = array_replace($defaultOptions, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['sentry'] ?? []);
        $options['integrations'] = $this->integrations;
        $options['default_integrations'] = false;

        $typo3SentryVersion = InstalledVersions::getPrettyVersion('helhum/sentry-typo3');
        $sdkVersion = self::SDK_VERSION . ' - ' . $typo3SentryVersion;
        $clientBuilder = ClientBuilder::create($options);
        $clientBuilder->setSdkIdentifier(self::SDK_IDENTIFIER);
        $clientBuilder->setSdkVersion($sdkVersion);
        $clientBuilder->setLogger($this->logger);

        $streamFactory = new StreamFactory();
        $httpClientFactory = new HttpClientFactory(
            new UriFactory(),
            new ResponseFactory(),
            $streamFactory,
            new Client($this->typo3HttpClient),
            self::SDK_IDENTIFIER,
            $sdkVersion
        );
        $transportFactory = new DefaultTransportFactory(
            $streamFactory,
            new RequestFactory(),
            $httpClientFactory,
            $this->logger
        );
        $clientBuilder->setTransportFactory($transportFactory);
        return $clientBuilder->getClient();
    }
}
