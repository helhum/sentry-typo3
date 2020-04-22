<?php
declare(strict_types=1);
namespace Helhum\SentryTypo3\Log\Writer;

use Helhum\SentryTypo3\Sentry;
use Sentry\Severity;
use Sentry\State\Hub;
use Sentry\State\Scope;
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
    /**
     * @var FileWriter
     */
    private $fallbackWriter;

    public function __construct(array $options = [])
    {
        parent::__construct([]);
        Sentry::initializeOnce();
        if (!empty($options)) {
            $this->fallbackWriter = GeneralUtility::makeInstance(FileWriter::class, $options);
        }
    }

    public function writeLog(LogRecord $record): WriterInterface
    {
        $hub = Hub::getCurrent();
        $hub->withScope(function (Scope $scope) use ($hub, $record) {
            $payload = [
                'level' => $this->getSeverityFromLevel(LogLevel::normalizeLevel($record->getLevel())),
                'message' => $record->getMessage(),
            ];
            $recordData = $record->getData();
            $exception = $recordData['exception'] ?? null;
            $fingerprint = $recordData['fingerprint'] ?? null;
            if ($exception instanceof \Throwable) {
                $payload['exception'] = $exception;
                unset($recordData['exception']);
                if (!$fingerprint && $exception->getCode() > 1000000000) {
                    // If we track an exception and the code appears to be a timestamp,
                    // we assume it to be unique enough to make it the fingerprint
                    // instead of letting the fingerprint being based on the stacktrace,
                    // but only if no fingerprint was set from the caller
                    $fingerprint = [
                        (string)$exception->getCode()
                    ];
                }
            }
            if ($fingerprint) {
                $scope->setFingerprint($fingerprint);
                unset($recordData['fingerprint']);
            }
            $scope->setExtra('typo3.component', $record->getComponent());
            $scope->setExtra('typo3.level', LogLevel::getName(LogLevel::normalizeLevel($record->getLevel())));
            $scope->setExtra('typo3.request_id', $record->getRequestId());
            if (!empty($recordData['tags'])) {
                foreach ($recordData['tags'] as $key => $value) {
                    $scope->setTag((string)$key, $value);
                }
                unset($recordData['tags']);
            }
            if (!empty($recordData['extra'])) {
                foreach ($recordData['extra'] as $key => $value) {
                    $scope->setExtra((string)$key, $value);
                }
                unset($recordData['extra']);
            }
            foreach ($recordData as $key => $value) {
                $scope->setExtra((string)$key, $value);
            }
            try {
                $hub->captureEvent($payload);
            } catch (\Throwable $e) {
                // Avoid hard failure in case connection to sentry failed
                if ($this->fallbackWriter) {
                    $this->fallbackWriter->writeLog(
                        new LogRecord(
                            'Sentry.Writer',
                            LogLevel::ERROR,
                            'Failed to write to Sentry',
                            ['exception' => $e],
                            $record->getRequestId()
                        )
                    );
                }
            }
            if ($this->fallbackWriter && (isset($e) || empty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['sentry']['dsn']))) {
                $this->fallbackWriter->writeLog($record);
            }
        });

        return $this;
    }

    /**
     * Translates the TYPO3 logging framework level into the Sentry severity.
     *
     * @param int $level The TYPO3 logging framework log level
     *
     * @return Severity
     */
    private function getSeverityFromLevel(int $level): Severity
    {
        switch ($level) {
            case LogLevel::DEBUG:
                return Severity::debug();
            case LogLevel::INFO:
            case LogLevel::NOTICE:
                return Severity::info();
            case LogLevel::WARNING:
                return Severity::warning();
            case LogLevel::ERROR:
                return Severity::error();
            case LogLevel::CRITICAL:
            case LogLevel::ALERT:
            case LogLevel::EMERGENCY:
                return Severity::fatal();
            default:
                return Severity::info();
        }
    }
}
