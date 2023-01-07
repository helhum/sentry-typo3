<?php

declare(strict_types=1);

namespace Helhum\SentryTypo3;

use Sentry\Event;
use Sentry\EventHint;

final class SentryMessage
{
    public function __construct(
        public readonly Event $event,
        public readonly EventHint $eventHint,
    ) {
    }
}
