<?php
declare(strict_types=1);
namespace Helhum\SentryTypo3\Integration;

use Sentry\Event;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class UserContext implements ContextInterface
{
    public function appliesToEvent(Event $event): bool
    {
        return (class_exists(Environment::class) && !Environment::isCli());
    }

    public function addToEvent(Event $event): void
    {
        $userContext = [
            'ip_address' => GeneralUtility::getIndpEnv('REMOTE_ADDR'),
        ];
        $userType = TYPO3_MODE === 'FE' ? 'frontend' : 'backend';
        /** @var UserAspect $userAspect */
        $userAspect = GeneralUtility::makeInstance(Context::class)->getAspect($userType . '.user');
        if ($userAspect->isLoggedIn()) {
            $userContext['userid'] = $userAspect->get('id');
            $userContext['username'] = $userAspect->get('username');
            $userContext['groups'] = implode(', ', $userAspect->getGroupNames());
        }

        $event->getUserContext()->merge($userContext);
    }
}
