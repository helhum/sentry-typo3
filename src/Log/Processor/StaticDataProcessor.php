<?php

declare(strict_types=1);

namespace Helhum\SentryTypo3\Log\Processor;

use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Log\Processor\AbstractProcessor;

/**
 * A log processor that adds any static data provided in configuration.
 */
class StaticDataProcessor extends AbstractProcessor
{
    /**
     * @var array<string, mixed>
     */
    private $data = [];

    /**
     * @param array<string, mixed> $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * Processes a log record and returns the same.
     *
     * @param LogRecord $logRecord The log record to process
     * @return LogRecord The processed log record with additional data
     */
    public function processLogRecord(LogRecord $logRecord)
    {
        $logRecord->addData($this->data);

        return $logRecord;
    }
}
