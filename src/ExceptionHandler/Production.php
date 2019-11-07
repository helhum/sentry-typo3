<?php
declare(strict_types=1);
namespace Helhum\SentryTypo3\ExceptionHandler;

use TYPO3\CMS\Core\Error\ProductionExceptionHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Only intended for use with TYPO3 < 9.
 */
class Production extends ProductionExceptionHandler
{
    protected function writeLogEntries(\Throwable $exception, $context)
    {
        (new LogException())->logException($exception, $context);
        parent::writeLogEntries($exception, $context);
    }
}
