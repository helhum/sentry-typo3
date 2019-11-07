<?php
declare(strict_types=1);
namespace Helhum\SentryTypo3\Integration;

use Sentry\Event;

class BeforeEventListener
{
    public static function onBeforeSend(Event $event): ?Event
    {
        /** @var ContextInterface[] $integrations */
        $integrations = [
            new Typo3Context(),
            new RequestContext(),
            new CommandContext(),
            new UserContext(),
            new UserContext87(),
        ];

        if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['sentry']['typo3_integrations'])) {
            $integrations = array_map(
                function($className) {
                    return new $className();
                },
                $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['sentry']['typo3_integrations']
            );
        }

        foreach ($integrations as $integration) {
            if (!$integration->appliesToEvent($event)) {
                continue;
            }
            $integration->addToEvent($event);
        }

        return $event;
    }
}
