<?php

declare(strict_types=1);

namespace Helhum\SentryTypo3\Log\Writer;

use Helhum\SentryTypo3\Sentry;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Log\Writer\AbstractWriter;
use TYPO3\CMS\Core\Log\Writer\WriterInterface;

/**
 * Log writer that writes the log records to Sentry.
 */
class SentryWriter extends AbstractWriter
{
    public function writeLog(LogRecord $record): WriterInterface
    {
        $eventId = Sentry::captureEvent($record);
        if ($eventId === null) {
            // We do not know here whether the event was intentionally
            // skipped or sending to Sentry failed. So in any case
            // log the message when a fallback loggers is configured
            Sentry::getFallbackLogger()?->writeLog($record);
        }
        return $this;
    }
}
