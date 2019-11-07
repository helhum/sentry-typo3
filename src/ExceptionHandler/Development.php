<?php
declare(strict_types=1);
namespace Helhum\SentryTypo3\ExceptionHandler;

use TYPO3\CMS\Core\Error\DebugExceptionHandler;

/**
 * Only intended for use with TYPO3 < 9.
 */
class Development extends DebugExceptionHandler
{
    protected function writeLogEntries(\Throwable $exception, $context)
    {
        (new LogException())->logException($exception, $context);
        parent::writeLogEntries($exception, $context);
    }
}
