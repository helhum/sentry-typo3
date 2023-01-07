<?php

declare(strict_types=1);

namespace Helhum\SentryTypo3\Integration;

use Sentry\Event;
use Sentry\EventHint;
use Sentry\Integration\IntegrationInterface;
use Sentry\SentrySdk;
use Sentry\State\Scope;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * This integration decides whether an event should not be captured according
 * to a series of options that must match with its data.
 */
final class IgnoreTypo3ComponentErrorsIntegration implements IntegrationInterface
{
    /**
     * @var array<string, mixed> The options
     */
    private $options;

    /**
     * Creates a new instance of this integration and configures it with the
     * given options.
     *
     * @param array{ignore_exception_codes?: array<int>,ignore_component_namespaces?: array<string>} $options The options
     */
    public function __construct(array $options = [])
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'ignore_exception_codes' => [],
            'ignore_component_namespaces' => [],
        ]);

        $resolver->setAllowedTypes('ignore_exception_codes', ['array']);
        $resolver->setAllowedTypes('ignore_component_namespaces', ['array']);

        $this->options = $resolver->resolve($options);
    }

    /**
     * {@inheritdoc}
     */
    public function setupOnce(): void
    {
        Scope::addGlobalEventProcessor(static function (Event $event, EventHint $hint): ?Event {
            $integration = SentrySdk::getCurrentHub()->getIntegration(self::class);

            if ($integration !== null && $integration->shouldDropEvent($event, $hint, $integration->options)) {
                return null;
            }

            return $event;
        });
    }

    /**
     * Checks whether the given event should be dropped according to the options
     * that configures the current instance of this integration.
     *
     * @param Event                $event   The event to check
     * @param EventHint $hint
     * @param array<string, mixed> $options The options of the integration
     */
    private function shouldDropEvent(Event $event, EventHint $hint, array $options): bool
    {
        if ($this->isIgnoredException($event, $hint, $options)) {
            return true;
        }

        if ($this->isIgnoredComponent($event, $options)) {
            return true;
        }

        return false;
    }

    /**
     * Checks whether the given event should be dropped or not according to the
     * criteria defined in the integration's options.
     *
     * @param Event                $event   The event instance
     * @param EventHint $hint
     * @param array<string, mixed> $options The options of the integration
     */
    private function isIgnoredException(Event $event, EventHint $hint, array $options): bool
    {
        if (empty($hint->exception)) {
            return false;
        }

        foreach ($options['ignore_exception_codes'] as $ignoredExceptionCode) {
            if ($hint->exception->getCode() === $ignoredExceptionCode) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks whether the given event should be dropped or not according to the
     * criteria defined in the integration's options.
     *
     * @param Event                $event   The event instance
     * @param array<string, mixed> $options The options of the integration
     */
    private function isIgnoredComponent(Event $event, array $options): bool
    {
        $component = $event->getTags()['typo3.component'] ?? null;

        if (empty($component)) {
            return false;
        }

        foreach ($options['ignore_component_namespaces'] as $componentNamespace) {
            if (str_starts_with($component, $componentNamespace)) {
                return true;
            }
        }

        return false;
    }
}
