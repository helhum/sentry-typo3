<?php

declare(strict_types=1);

use Helhum\SentryTypo3\DependencyInjection\SentryIntegrationsPass;
use Sentry\Integration\IntegrationInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container, ContainerBuilder $containerBuilder) {

    $containerBuilder->registerForAutoconfiguration(IntegrationInterface::class)->addTag('sentry.integration');
    $containerBuilder->addCompilerPass(new SentryIntegrationsPass('sentry.integration'));

};
