<?php

declare(strict_types=1);

namespace Helhum\SentryTypo3\DependencyInjection;

use Helhum\SentryTypo3\SentryClientFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class SentryIntegrationsPass implements CompilerPassInterface
{
    private readonly DependencyOrderingService $orderingService;

    public function __construct(private readonly string $tagName)
    {
        $this->orderingService = new DependencyOrderingService();
    }

    public function process(ContainerBuilder $container): void
    {
        $integrations = [];
        $unorderedIntegrations = $this->collectIntegrations($container);
        foreach ($this->orderingService->orderByDependencies($unorderedIntegrations) as $integration) {
            $integrations[] = $integration['service'];
        }

        $factoryDefinition = $container->findDefinition(SentryClientFactory::class);
        $this->injectSentryLogger($container, $factoryDefinition);
        $factoryDefinition->setPublic(true);
        $factoryDefinition->setArgument('$integrations', $integrations);
    }

    /**
     * @param ContainerBuilder $container
     * @return array<string, array{service: Definition, before: array<string>, after: array<string>}>
     */
    protected function collectIntegrations(ContainerBuilder $container): array
    {
        $unorderedIntegrations = [];
        foreach ($container->findTaggedServiceIds($this->tagName) as $serviceName => $tags) {
            $service = $container->findDefinition($serviceName);
            if (!$service->isAutoconfigured()
                || $service->isAbstract()
            ) {
                continue;
            }
            $this->injectSentryLogger($container, $service);
            $configuredIntegrations = [];
            foreach ($tags as $attributes) {
                if (($attributes['disabled'] ?? false) === true
                    || (
                        ($attributes['autoconfigured'] ?? false) === true
                        && ($configuredIntegrations[$serviceName] ?? false) === true
                    )
                ) {
                    continue;
                }
                $configuredIntegrations[$serviceName] = true;
                $integrationIdentifier = (string)($attributes['identifier'] ?? $serviceName);
                $unorderedIntegrations[$integrationIdentifier] = [
                    'service' => $service,
                    'before' => GeneralUtility::trimExplode(',', $attributes['before'] ?? '', true),
                    'after' => GeneralUtility::trimExplode(',', $attributes['after'] ?? '', true),
                ];
            }
        }
        return $unorderedIntegrations;
    }

    private function injectSentryLogger(ContainerBuilder $container, Definition $definition): void
    {
        $reflectionClass = $container->getReflectionClass($definition->getClass(), false);
        if ($reflectionClass === null) {
            return;
        }
        $constructor = $reflectionClass->getConstructor();
        if ($constructor === null) {
            return;
        }
        foreach ($constructor->getParameters() as $parameter) {
            $name = '$' . $parameter->getName();
            if (!$parameter->hasType()) {
                continue;
            }
            $type = $parameter->getType();
            if (!($type instanceof \ReflectionNamedType && $type->getName() === LoggerInterface::class)) {
                continue;
            }
            $logger = new Definition(Logger::class);
            $logger->setFactory([new Reference(LogManager::class), 'getLogger']);
            $logger->setArguments(['Sentry.Logger']);
            $logger->setShared(true);
            $definition->setArgument($name, $logger);
        }
    }
}
