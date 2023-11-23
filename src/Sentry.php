<?php

declare(strict_types=1);

namespace Helhum\SentryTypo3;

use Helhum\SentryTypo3\Log\FallbackLogger;
use Sentry\EventId;
use Sentry\SentrySdk;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class Sentry
{
    private static bool $initialized = false;
    private static ?SentryMessageFactory $messageFactory = null;
    private static ?FallbackLogger $fallbackLogger = null;

    public static function captureEvent(LogRecord $record): ?EventId
    {
        $initialized = self::initialize();
        if (!$initialized) {
            return null;
        }
        $message = self::$messageFactory?->createFromLogRecord($record);
        return $message !== null
            ? SentrySdk::getCurrentHub()
                ->captureEvent(
                    $message->event,
                    $message->eventHint
                )
            : null;
    }

    public static function addBreadcrumb(LogRecord $record): bool
    {
        $initialized = self::initialize();
        if (!$initialized) {
            return false;
        }
        $breadcrumb = self::$messageFactory?->createBreadCrumbFromLogRecord($record);
        return $breadcrumb !== null
            && SentrySdk::getCurrentHub()
                ->addBreadcrumb($breadcrumb);
    }

    public static function getFallbackLogger(): ?FallbackLogger
    {
        self::initializeFallbackLogger();
        return self::$fallbackLogger;
    }

    private static function initializeFallbackLogger(): void
    {
        if (!isset(self::$fallbackLogger) && isset($GLOBALS['TYPO3_CONF_VARS']['LOG']['Sentry']['FallbackLogger'])) {
            self::$fallbackLogger = new FallbackLogger(GeneralUtility::makeInstance(LogManager::class)->getLogger('Sentry.FallbackLogger'));
        }
    }

    private static function initialize(): bool
    {
        if (self::$initialized) {
            return true;
        }
        if (empty($_SERVER['SENTRY_DSN']) && empty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['sentry']['dsn'])) {
            return false;
        }
        try {
            self::$messageFactory = GeneralUtility::makeInstance(SentryMessageFactory::class);
            $client = GeneralUtility::makeInstance(SentryClientFactory::class)->createClient();
        } catch (\Throwable $e) {
            GeneralUtility::makeInstance(LogManager::class)->getLogger('Sentry.Logger')
                ->error('Could not initialize Sentry, because an error occurred before DI was available', ['exception' => $e]);
            return false;
        }
        SentrySdk::init()->bindClient($client);
        return self::$initialized = true;
    }
}
