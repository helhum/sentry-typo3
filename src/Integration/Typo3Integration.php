<?php

declare(strict_types=1);

namespace Helhum\SentryTypo3\Integration;

use Composer\InstalledVersions;
use Sentry\Event;
use Sentry\EventHint;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Log\LogRecord;

class Typo3Integration extends AbstractEventProcessingIntegration
{
    public function processEvent(Event $event, EventHint $hint): void
    {
        $event->setTag('typo3.version', (new Typo3Version())->getVersion());
        $event->setTag('application_version', InstalledVersions::getPrettyVersion(InstalledVersions::getRootPackage()['name']) ?? 'unknown');
        $this->modifyFingerprintFromException($event, $hint);
        if ($hint->extra['typo3LogRecord'] instanceof LogRecord) {
            $this->enrichEventWithLogRecord($event, $hint->extra['typo3LogRecord']);
        }
    }

    private function modifyFingerprintFromException(Event $event, EventHint $hint): void
    {
        if ($hint->exception instanceof \Throwable
            && $hint->exception->getCode() > 1000000000
        ) {
            $event->setFingerprint([
                (string)$hint->exception->getCode()
            ]);
        }
    }

    private function enrichEventWithLogRecord(Event $event, LogRecord $record): void
    {
        $component = $record->getComponent();
        $event->setTag('typo3.component', $record->getComponent());
        if ($component === 'TYPO3.CMS.Frontend.ContentObject.Exception.ProductionExceptionHandler'
            && isset($record->getData()['code'])
        ) {
            $event->setTag('typo3.error_code', $record->getData()['code']);
        }

        $recordData = $record->getData();

        if (isset($recordData['fingerprint']) && is_array($recordData['fingerprint'])) {
            $event->setFingerprint($recordData['fingerprint']);
        }

        if (isset($recordData['tags']) && is_array($recordData['tags'])) {
            foreach ($recordData['tags'] as $key => $value) {
                $event->setTag((string)$key, $value);
            }
        }

        $eventExtra = $event->getExtra();
        $eventExtra['typo3.request_id'] = $record->getRequestId();
        $eventExtra['typo3.log_level'] = $record->getLevel();
        $eventExtra['typo3.application_context'] = Environment::getContext()->__toString();
        if (is_array($recordData['extra'] ?? null)) {
            foreach ($recordData['extra'] as $key => $value) {
                $eventExtra[$key] = $value;
            }
            unset($recordData['extra']);
        }
        unset(
            $recordData['fingerprint'],
            $recordData['tags'],
            $recordData['exception'],
            $recordData['exception_class'],
            $recordData['file'],
            $recordData['exception_code'],
            $recordData['line'],
            $recordData['message'],
            $recordData['request_url'],
            $recordData['application_mode'],
            $recordData['mode'],
        );
        foreach ($recordData as $key => $value) {
            $eventExtra[$key] = $value;
        }
        $event->setExtra($eventExtra);
    }
}
