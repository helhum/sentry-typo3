<?php

declare(strict_types=1);

namespace Helhum\SentryTypo3\Log\Writer;

use Helhum\SentryTypo3\Sentry;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Log\Writer\AbstractWriter;
use TYPO3\CMS\Core\Log\Writer\WriterInterface;

/**
 * Log writer that adds breadcrumbs for Sentry.
 */
class SentryBreadcrumbWriter extends AbstractWriter
{
    public function writeLog(LogRecord $record): WriterInterface
    {
        Sentry::addBreadcrumb($record);
        return $this;
    }
}
