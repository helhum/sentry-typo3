<?php
declare(strict_types=1);
namespace Helhum\SentryTypo3\ExceptionHandler;

use TYPO3\CMS\Core\Error\AbstractExceptionHandler;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Only intended for use with TYPO3 < 9.
 *
 * Not an exception handler, just extends in order to make use of existing methods.
 * Only provides the logException API for provided exception handler to not duplicate code.
 */
class LogException extends AbstractExceptionHandler
{
    /**
     * @var Logger
     */
    private $logger;

    public function __construct()
    {
        if ($this->logger === null) {
            $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
        }
    }

    public function logException(\Throwable $exception, $context)
    {
        // Do not write any logs for this message to avoid filling up tables or files with illegal requests
        if ($exception->getCode() === 1396795884) {
            return;
        }

        $filePathAndName = $exception->getFile();
        $exceptionCodeNumber = $exception->getCode() > 0 ? '#' . $exception->getCode() . ': ' : '';
        $logTitle = 'Core: Exception handler (' . $context . ')';
        $logMessage = 'Uncaught TYPO3 Exception: ' . $exceptionCodeNumber . $exception->getMessage() . ' | '
            . get_class($exception) . ' thrown in file ' . $filePathAndName . ' in line ' . $exception->getLine();
        if ($context === 'WEB') {
            $logMessage .= '. Requested URL: ' . $this->anonymizeToken(GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'));
        }
        try {
            $this->logger->critical($logTitle . ': ' . $logMessage, [
                'TYPO3_MODE' => TYPO3_MODE,
                'exception' => $exception
            ]);
        } catch (\Exception $exception) {
        }
    }

    public function echoExceptionWeb(\Throwable $exception)
    {
    }

    public function echoExceptionCLI(\Throwable $exception)
    {
    }
}
