<?php
declare(strict_types=1);
namespace Helhum\SentryTypo3\Integration;

use Psr\Log\AbstractLogger;
use Sentry\Event;
use Sentry\EventHint;
use Sentry\Stacktrace;

class CleanupStackTraceIntegration extends AbstractEventProcessingIntegration
{
    protected function processEvent(Event $event, EventHint $hint): void
    {
        $stacktrace = $event->getStacktrace();
        if ($stacktrace === null) {
            return;
        }
        foreach ($stacktrace->getFrames() as $index => $frame) {
            if (!str_starts_with($frame->getFunctionName() ?? '', AbstractLogger::class . '::')) {
                continue;
            }
            $stacktraceBeforeLogCall = new Stacktrace(array_slice($stacktrace->getFrames(), 0, $index));
            $event->setStacktrace($stacktraceBeforeLogCall);
            break;
        }
    }
}
