<?php

namespace App\MessageHandler;

use App\Message\PostUpload\ReadMetadataMessage;
use App\Message\RecheckAssetMetadataMessage;
use App\Repository\RevisionRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final readonly class RecheckAssetMetadataMessageHandler
{
    public function __construct(
        private RevisionRepository $revisionRepository,
        private MessageBusInterface $bus
    ) {
    }

    public function __invoke(RecheckAssetMetadataMessage $message): void
    {
        if ($message->isForce()) {
            $revisionsToUpdate = $this->revisionRepository->findAll();
        } else {
            $revisionsToUpdate = $this->revisionRepository->findRevisionsWithoutSizes();
        }
        foreach ($revisionsToUpdate as $revision) {
            $file = $revision->getFile();
            if (null !== $file && null !== $file->getId()) {
                $this->bus->dispatch(new ReadMetadataMessage($file->getId(), $revision->getId()));
            }
        }
    }
}
