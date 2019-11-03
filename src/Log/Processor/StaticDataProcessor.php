<?php
declare(strict_types=1);
namespace Helhum\SentryTypo3\Log\Processor;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Log\Processor\AbstractProcessor;

/**
 * A log processor that adds any static data provided in configuration.
 */
class StaticDataProcessor extends AbstractProcessor
{
    /**
     * @var array
     */
    private $data = [];

    /**
     * @param array $data
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
