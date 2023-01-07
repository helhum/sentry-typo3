<?php

declare(strict_types=1);

namespace Helhum\SentryTypo3;

use Sentry\EventId;
use Sentry\SentrySdk;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class Sentry
{
    private static bool $initialized = false;
    private static ?SentryMessageFactory $messageFactory = null;

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

    private static function initialize(): bool
    {
        if (self::$initialized) {
            return true;
        }
        try {
            $client = GeneralUtility::makeInstance(SentryClientFactory::class)->createClient();
            self::$messageFactory = GeneralUtility::makeInstance(SentryMessageFactory::class);
        } catch (\Throwable $e) {
            GeneralUtility::makeInstance(LogManager::class)
                ->getLogger('Sentry.Logger')
                ->error('Could not initialize Sentry, because an error occurred before DI was available', ['exception' => $e]);
            return false;
        }
        SentrySdk::init()->bindClient($client);
        return self::$initialized = true;
    }
}
