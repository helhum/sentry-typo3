<?php
declare(strict_types=1);
namespace Helhum\SentryTypo3\Integration;

use Sentry\Event;

interface ContextInterface
{
    public function appliesToEvent(Event $event): bool;
    public function addToEvent(Event $event): void;
}
