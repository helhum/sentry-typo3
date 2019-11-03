<?php
declare(strict_types=1);
namespace Helhum\SentryTypo3\ContentObject\Exception;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Frontend\ContentObject\Exception\ProductionExceptionHandler;

class ExceptionHandler extends ProductionExceptionHandler
{
    protected function logException(\Exception $exception, $errorMessage, $code)
    {
        if (Environment::getContext()->isDevelopment()) {
            throw $exception;
        }
        $this->logger->alert(
            sprintf($errorMessage, $code),
            [
                'exception' => $exception,
                'tags' => [
                    'typo3.error_code' => $code,
                ],
            ]
        );
    }
}
