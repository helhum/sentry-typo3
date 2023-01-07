<?php

declare(strict_types=1);

namespace Helhum\SentryTypo3;

use Sentry\Breadcrumb;
use Sentry\Event;
use Sentry\EventHint;
use Sentry\ExceptionMechanism;
use Sentry\Severity;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogRecord;

final class SentryMessageFactory
{
    public function createFromLogRecord(LogRecord $record): SentryMessage
    {
        $recordData = $record->getData();
        $event = Event::createEvent();
        $event->setLevel($this->getSeverityFromLevel($record->getLevel()));
        $event->setMessage($this->processPlaceholders($record->getMessage(), $recordData));
        $exception = $recordData['exception'] ?? null;
        $hintData = [
            'extra' => ['typo3LogRecord' => $record]
        ];
        if ($exception instanceof \Throwable) {
            $hintData['exception'] = $exception;
        }
        if (str_contains($record->getComponent(), 'ExceptionHandler')
            || str_contains($record->getComponent(), 'ExceptionRenderer')
        ) {
            $hintData['mechanism'] = new ExceptionMechanism(ExceptionMechanism::TYPE_GENERIC, false);
            $event->setMessage($exception->getMessage());
        }
        return new SentryMessage($event, EventHint::fromArray($hintData));
    }

    public function createBreadCrumbFromLogRecord(LogRecord $record): Breadcrumb
    {
        return Breadcrumb::fromArray(
            [
                'level' => $this->getBreadCrumbLevelFromLevel($record->getLevel()),
                'message' => $this->processPlaceholders($record->getMessage(), $record->getData()),
                'data' => $record->getData(),
                'category' => $record->getComponent(),
            ]
        );
    }

    /**
     * Translates the PSR level into the Sentry severity.
     *
     * @param string $level The PSR log level
     *
     * @return Severity
     */
    private function getSeverityFromLevel(string $level): Severity
    {
        return match ($level) {
            LogLevel::DEBUG => Severity::debug(),
            LogLevel::WARNING => Severity::warning(),
            LogLevel::ERROR => Severity::error(),
            LogLevel::CRITICAL, LogLevel::ALERT, LogLevel::EMERGENCY => Severity::fatal(),
            default => Severity::info(),
        };
    }

    /**
     * Translates the PSR level into the Sentry severity.
     *
     * @param string $level The PSR log level
     *
     * @return string
     */
    private function getBreadCrumbLevelFromLevel(string $level): string
    {
        return match ($level) {
            LogLevel::DEBUG => Breadcrumb::LEVEL_DEBUG,
            LogLevel::WARNING => Breadcrumb::LEVEL_WARNING,
            LogLevel::ERROR => Breadcrumb::LEVEL_ERROR,
            LogLevel::CRITICAL, LogLevel::ALERT, LogLevel::EMERGENCY => Breadcrumb::LEVEL_FATAL,
            default => Breadcrumb::LEVEL_INFO,
        };
    }

    /**
     * @param string $message
     * @param array<string, mixed> $context
     * @return string
     */
    private function processPlaceholders(string $message, array $context = []): string
    {
        $replace = [];
        foreach ($context as $key => $value) {
            if (is_scalar($value) || $value instanceof \Stringable) {
                $replace['{' . $key . '}'] = $value;
            }
        }
        return strtr($message, $replace);
    }
}
