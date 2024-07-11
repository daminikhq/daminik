<?php

namespace App\MessageHandler;

use App\Exception\FileHandlerException;
use App\Message\UpdateAssetVisibilityMessage;
use App\Repository\FileRepository;
use App\Repository\WorkspaceRepository;
use App\Service\File\FileHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final readonly class UpdateAssetVisibilityMessageHandler
{
    public function __construct(
        private FileRepository $fileRepository,
        private FileHandler $fileHandler,
        private MessageBusInterface $bus,
        private WorkspaceRepository $workspaceRepository,
    ) {
    }

    /**
     * @throws FileHandlerException
     * @throws ExceptionInterface
     */
    public function __invoke(UpdateAssetVisibilityMessage $message): void
    {
        if (null !== $message->getAssetId()) {
            $file = $this->fileRepository->find($message->getAssetId());
            if (null !== $file) {
                $this->fileHandler->checkVisibility($file);
            }
        } elseif (null !== $message->getWorkspaceId()) {
            $workspace = $this->workspaceRepository->find($message->getWorkspaceId());
            if (null !== $workspace) {
                $files = $this->fileRepository->findBy(['workspace' => $workspace, 'public' => true]);
                foreach ($files as $file) {
                    $this->bus->dispatch(new UpdateAssetVisibilityMessage($file->getId()));
                }
            }
        }
    }
}
