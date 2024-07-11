<?php

declare(strict_types=1);

namespace App\Listener;

use App\Event\Collection\AssetCollectionConfigChangedEvent;
use App\Event\Membership\MembershipDeletedEvent;
use App\Event\Membership\MembershipUpdatedEvent;
use App\Service\DatabaseLogger\DatabaseLoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class ActivityLoggerListener implements EventSubscriberInterface
{
    public function __construct(
        private DatabaseLoggerInterface $databaseLogger
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AssetCollectionConfigChangedEvent::class => 'logEvent',
            MembershipUpdatedEvent::class => 'logEvent',
            MembershipDeletedEvent::class => 'logEvent',
        ];
    }

    public function logEvent(LoggableEventInterface $event): void
    {
        $this->databaseLogger->log(
            userAction: $event->getUserAction(),
            object: $event->getObject(),
            metadata: $event->getMetadata(),
            actingUser: $event->getActingUser(),
            workspace: $event->getWorkspace()
        );
    }
}
