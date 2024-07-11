<?php

namespace App\MessageHandler;

use App\Entity\FileAssetCollection;
use App\Message\AssetCollectionUpdatedMessage;
use App\Repository\AssetCollectionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class AssetCollectionUpdatedMessageHandler
{
    public function __construct(
        private AssetCollectionRepository $assetCollectionRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(AssetCollectionUpdatedMessage $message): void
    {
        $assetCollection = $this->assetCollectionRepository->find($message->getAssetCollectionId());
        if (null === $assetCollection) {
            return;
        }
        $assetCollection->setAssetCount($assetCollection->getFileAssetCollections()->filter(fn (FileAssetCollection $fileAssetCollection) => !$fileAssetCollection->getFile()?->getDeletedAt() instanceof \DateTime)->count());
        $this->entityManager->flush();
    }
}
