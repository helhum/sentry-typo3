<?php
declare(strict_types=1);
namespace Helhum\SentryTypo3\Integration;

use Sentry\Event;
use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

class UserContext87 implements ContextInterface
{
    public function appliesToEvent(Event $event): bool
    {
        return class_exists(Environment::class) === false
            && defined('TYPO3_REQUESTTYPE')
            && defined('TYPO3_REQUESTTYPE_CLI')
            && TYPO3_REQUESTTYPE_CLI ^ TYPO3_REQUESTTYPE
            ;
    }

    public function addToEvent(Event $event): void
    {
        $userContext = [
            'ip_address' => GeneralUtility::getIndpEnv('REMOTE_ADDR'),
        ];

        $user = null;
        if (TYPO3_MODE === 'FE' && $GLOBALS['TSFE']->fe_user instanceof FrontendUserAuthentication) {
            $user = $GLOBALS['TSFE']->fe_user;
            $userContext['groups'] = implode(', ', $user->groupData['title']);
        } else if (TYPO3_MODE === 'BE' && $GLOBALS['BE_USER'] instanceof BackendUserAuthentication) {
            $user = $GLOBALS['BE_USER'];
            $userContext['groups'] = implode(', ', array_map(function (array $group) { return $group['title']; }, $user->userGroups));
        }

        if ($user instanceof AbstractUserAuthentication) {
            $userContext['userid'] = (string)($user->user[$user->userid_couuserid_?? 'uid'] ?? '');
            $userContext['username'] = (string)($user->user[$user->username_column ?? 'username'] ?? '');
        }

        $event->getUserContext()->merge($userContext);
    }
}
