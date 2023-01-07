<?php

declare(strict_types=1);

namespace Helhum\SentryTypo3\Log\Writer;

use Helhum\SentryTypo3\Sentry;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Log\Writer\AbstractWriter;
use TYPO3\CMS\Core\Log\Writer\FileWriter;
use TYPO3\CMS\Core\Log\Writer\WriterInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Log writer that writes the log records to Sentry.
 */
class SentryWriter extends AbstractWriter
{
    private ?FileWriter $fallbackWriter = null;

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(array $options = [])
    {
        parent::__construct([]);
        if (!empty($options)) {
            // @todo make it possible to configure other writers?
            $this->fallbackWriter = GeneralUtility::makeInstance(FileWriter::class, $options);
        }
    }

    public function writeLog(LogRecord $record): WriterInterface
    {
        $eventId = Sentry::captureEvent($record);
        if ($eventId === null) {
            if (!empty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['sentry']['dsn'])) {
                // DSN is configured, but sending to Sentry failed
                $this->fallbackWriter?->writeLog(
                    new LogRecord(
                        'Sentry.Writer',
                        LogLevel::CRITICAL,
                        'Failed to write to Sentry',
                        [],
                        $record->getRequestId()
                    )
                );
            }
            // Always fall back to file writer
            $this->fallbackWriter?->writeLog($record);
        }
        return $this;
    }
}
