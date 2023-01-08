<?php

declare(strict_types=1);

namespace Helhum\SentryTypo3\Error;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Sentry\ErrorHandler as SentryErrorHandler;
use TYPO3\CMS\Core\Error\ErrorHandler as Typo3ErrorHandler;
use TYPO3\CMS\Core\Error\ErrorHandlerInterface;
use TYPO3\CMS\Core\Error\Exception as Typo3ErrorException;

final class ErrorHandler implements ErrorHandlerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private readonly Typo3ErrorHandler $typo3ErrorHandler;
    private ?\Throwable $unHandledError = null;

    public function __construct($errorHandlerErrors)
    {
        $this->typo3ErrorHandler = new Typo3ErrorHandler($errorHandlerErrors);
        $this->typo3ErrorHandler->setLogger(
            new class() extends AbstractLogger {
                public function log($level, $message, array $context = []): void
                {
                    throw new ErrorHandlerLogged($level, $message);
                }
            }
        );
    }

    public function registerErrorHandler(): void
    {
        set_error_handler(\Closure::fromCallable([$this, 'handleError']));
        $sentryErrorHandler = SentryErrorHandler::registerOnceErrorHandler();
        $sentryErrorHandler->addErrorHandlerListener(function (\ErrorException $exception) {
            try {
                $this->typo3ErrorHandler->handleError(
                    $exception->getSeverity(),
                    $exception->getMessage(),
                    $exception->getFile(),
                    $exception->getLine(),
                );
            } catch (Typo3ErrorException $e) {
                $this->unHandledError = $exception;
            } catch (ErrorHandlerLogged $e) {
                $this->logger?->log($e->logLevel, $exception->getMessage(), ['exception' => $exception]);
            }
        });
    }

    public function handleError($errorLevel, $errorMessage, $errorFile, $errorLine): bool
    {
        if ($this->unHandledError instanceof \Throwable) {
            $errorToBeThrown = $this->unHandledError;
            $this->unHandledError = null;
            throw $errorToBeThrown;
        }
        return self::ERROR_HANDLED;
    }

    public function setExceptionalErrors($exceptionalErrors): void
    {
        $this->typo3ErrorHandler->setExceptionalErrors($exceptionalErrors);
    }

    public function setDebugMode(bool $debugMode): void
    {
        $this->typo3ErrorHandler->setDebugMode($debugMode);
    }
}
