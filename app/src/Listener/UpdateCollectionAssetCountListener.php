<?php

declare(strict_types=1);

namespace App\Listener;

use App\Entity\AssetCollection;
use App\Entity\File;
use App\Event\Collection\FileAssetAddedToCollectionEvent;
use App\Event\Collection\FileAssetCollectionEventInterface;
use App\Event\Collection\FileAssetRemovedFromCollectionEvent;
use App\Message\AssetCollectionUpdatedMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class UpdateCollectionAssetCountListener implements EventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private MessageBusInterface $bus
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FileAssetAddedToCollectionEvent::class => 'updateCollectionCount',
            FileAssetRemovedFromCollectionEvent::class => 'updateCollectionCount',
        ];
    }

    public function updateCollectionCount(FileAssetCollectionEventInterface $event): void
    {
        $fileAssetCollection = $event->getFileAssetCollection();
        $assetCollection = $fileAssetCollection->getAssetCollection();
        $file = $fileAssetCollection->getFile();
        if (!$file instanceof File && $event instanceof FileAssetRemovedFromCollectionEvent) {
            $file = $event->getFile();
        }
        if (!$assetCollection instanceof AssetCollection || null === $assetCollection->getId()) {
            $this->logger->info('No asset collection found', [
                'asset' => $fileAssetCollection->getFile()?->getFilenameSlug(),
            ]);

            return;
        }
        if (!$file instanceof File || null === $file->getId()) {
            $this->logger->info('No file found', [
                'collection' => $assetCollection->getSlug(),
            ]);

            return;
        }
        $this->logger->info(
            $event instanceof FileAssetAddedToCollectionEvent ? 'Asset added to collection' : 'Asset removed from collection',
            [
                'asset' => $file->getFilenameSlug(),
                'collection' => $assetCollection->getSlug(),
            ]
        );
        $this->bus->dispatch(new AssetCollectionUpdatedMessage($assetCollection->getId(), $file->getId()));
    }
}
