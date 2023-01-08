<?php

declare(strict_types=1);

namespace Helhum\SentryTypo3\Log;

use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogRecord;

class FallbackLogger extends Logger
{
    public function __construct(private readonly Logger $logger)
    {
        parent::__construct('Sentry.FallbackLogger');
    }

    public function writeLog(LogRecord $record): void
    {
        $this->logger->writeLog($record);
    }
}
