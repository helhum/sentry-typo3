<?php
declare(strict_types=1);
namespace Helhum\SentryTypo3\Log\Writer;

use Helhum\SentryTypo3\Sentry;
use Sentry\Breadcrumb;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Log\Writer\AbstractWriter;
use TYPO3\CMS\Core\Log\Writer\WriterInterface;

/**
 * Log writer that adds breadcrumbs for Sentry.
 */
class SentryBreadcrumbWriter extends AbstractWriter
{
    public function __construct(array $options = [])
    {
        parent::__construct($options);
        Sentry::initializeOnce();
    }

    public function writeLog(LogRecord $record): WriterInterface
    {
        \Sentry\addBreadcrumb(
            Breadcrumb::fromArray(
                [
                    'level' => $this->getSeverityFromLevel(LogLevel::normalizeLevel($record->getLevel())),
                    'message' => $record->getMessage(),
                    'data' => $record->getData(),
                    'category' => $record->getComponent(),
                ]
            )
        );

        return $this;
    }

    /**
     * Translates the TYPO3 logging framework level into the Sentry severity.
     *
     * @param int $level The TYPO3 logging framework log level
     *
     * @return string
     */
    private function getSeverityFromLevel(int $level): string
    {
        switch ($level) {
            case LogLevel::DEBUG:
                return Breadcrumb::LEVEL_DEBUG;
            case LogLevel::INFO:
            case LogLevel::NOTICE:
                return Breadcrumb::LEVEL_INFO;
            case LogLevel::WARNING:
                return Breadcrumb::LEVEL_WARNING;
            case LogLevel::ERROR:
                return Breadcrumb::LEVEL_ERROR;
            case LogLevel::CRITICAL:
            case LogLevel::ALERT:
            case LogLevel::EMERGENCY:
                return Breadcrumb::LEVEL_CRITICAL;
            default:
                return Breadcrumb::LEVEL_INFO;
        }
    }
}
