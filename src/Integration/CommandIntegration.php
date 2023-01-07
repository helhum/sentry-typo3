<?php

declare(strict_types=1);

namespace Helhum\SentryTypo3\Integration;

use Sentry\Event;
use Sentry\EventHint;
use Symfony\Component\Console\Input\ArgvInput;
use TYPO3\CMS\Core\Core\Environment;

class CommandIntegration extends AbstractEventProcessingIntegration
{
    public function setupOnce(): void
    {
        if (!Environment::isCli()) {
            return;
        }
        parent::setupOnce();
    }

    protected function processEvent(Event $event, EventHint $hint): void
    {
        $input = new ArgvInput();
        $event->setTag(
            'typo3.command',
            $input->getFirstArgument() ?? 'list',
        );
    }
}
