<?php

declare(strict_types=1);

namespace Helhum\SentryTypo3\Integration;

use Psr\Http\Message\ServerRequestInterface;
use Sentry\Event;
use Sentry\EventHint;
use Sentry\Integration\RequestFetcherInterface;
use Sentry\UserDataBag;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class UserIntegration extends AbstractEventProcessingIntegration
{
    public function __construct(private readonly RequestFetcherInterface $requestFetcher)
    {
    }

    public function setupOnce(): void
    {
        if (Environment::isCli()) {
            return;
        }
        parent::setupOnce();
    }

    protected function processEvent(Event $event, EventHint $hint): void
    {
        $userContext = [
            'ip_address' => GeneralUtility::getIndpEnv('REMOTE_ADDR'),
        ];
        $request = $this->requestFetcher->fetchRequest();
        if ($request instanceof ServerRequestInterface) {
            $userType = ApplicationType::fromRequest($request)->isFrontend() ? 'frontend' : 'backend';
            /** @var UserAspect $userAspect */
            $userAspect = GeneralUtility::makeInstance(Context::class)->getAspect($userType . '.user');
            if ($userAspect->isLoggedIn()) {
                $userContext['id'] = $userAspect->get('id');
                $userContext['username'] = $userAspect->get('username');
                $userContext['groups'] = implode(', ', $userAspect->getGroupNames());
            }
        }
        $event->setUser(UserDataBag::createFromArray(($userContext)));
    }
}
