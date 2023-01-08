<?php

declare(strict_types=1);

namespace Helhum\SentryTypo3\Error;

final class ErrorHandlerLogged extends \Exception
{
    public function __construct(
        public readonly string $logLevel,
        public readonly string $logMessage
    ) {
        parent::__construct($logMessage);
    }
}
