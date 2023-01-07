<?php

declare(strict_types=1);

namespace Helhum\SentryTypo3\Integration;

use Psr\Http\Message\ServerRequestInterface;
use Sentry\Integration\RequestFetcherInterface;
use TYPO3\CMS\Core\Core\Environment;

final class RequestFetcher implements RequestFetcherInterface
{
    /**
     * {@inheritdoc}
     */
    public function fetchRequest(): ?ServerRequestInterface
    {
        if (!isset($GLOBALS['TYPO3_REQUEST']) || Environment::isCli()) {
            return null;
        }

        return $GLOBALS['TYPO3_REQUEST'];
    }
}
