<?php
declare(strict_types=1);
namespace Helhum\SentryTypo3\Integration;

use Sentry\Event;
use Symfony\Component\Console\Input\ArgvInput;
use TYPO3\CMS\Core\Core\Environment;

class CommandContext implements ContextInterface
{

    public function appliesToEvent(Event $event): bool
    {
        return (class_exists(Environment::class) && Environment::isCli())
            || (
                class_exists(Environment::class) === false
                && defined('TYPO3_REQUESTTYPE')
                && defined('TYPO3_REQUESTTYPE_CLI')
                && TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_CLI
            )
            ;
    }

    public function addToEvent(Event $event): void
    {
        $input = new ArgvInput();
        $event->getExtraContext()->merge(
            [
                'typo3.command' => $input->getFirstArgument() ?? 'list',
            ]
        );
        $event->getTagsContext()->merge(
            [
                'typo3.command' => $input->getFirstArgument() ?? 'list',
            ]
        );
    }
}
