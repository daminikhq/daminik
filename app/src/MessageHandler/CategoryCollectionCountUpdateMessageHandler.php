<?php

namespace App\MessageHandler;

use App\Message\AssetCollectionUpdatedMessage;
use App\Message\CategoryCollectionCountUpdateMessage;
use App\Message\CategoryUpdatedMessage;
use App\Repository\FileRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final readonly class CategoryCollectionCountUpdateMessageHandler
{
    public function __construct(
        private FileRepository $fileRepository,
        private MessageBusInterface $bus
    ) {
    }

    public function __invoke(CategoryCollectionCountUpdateMessage $message): void
    {
        if (null === $message->getFileId()) {
            return;
        }
        $file = $this->fileRepository->find($message->getFileId());
        if (null === $file) {
            return;
        }
        foreach ($file->getFileCategories() as $fileCategory) {
            if (null !== $fileCategory->getCategory() && null !== $fileCategory->getCategory()->getId()) {
                $this->bus->dispatch(new CategoryUpdatedMessage($fileCategory->getCategory()->getId(), $file->getId()));
            }
        }
        foreach ($file->getFileAssetCollections() as $fileAssetCollection) {
            if (null !== $fileAssetCollection->getAssetCollection() && null !== $fileAssetCollection->getAssetCollection()->getId()) {
                $this->bus->dispatch(new AssetCollectionUpdatedMessage($fileAssetCollection->getAssetCollection()->getId(), $file->getId()));
            }
        }
    }
}
