<?php

namespace App\MessageHandler\Filesize;

use App\Message\Filesize\UpdateMembershipUploadSizeMessage;
use App\Message\Filesize\UpdateUploadSizesMessage;
use App\Message\Filesize\UpdateUserUploadSizeMessage;
use App\Message\Filesize\UpdateWorkspaceUploadSizeMessage;
use App\Repository\RevisionRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final readonly class UpdateUploadSizesMessageHandler
{
    public function __construct(
        private MessageBusInterface $bus,
        private RevisionRepository $revisionRepository,
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(UpdateUploadSizesMessage $message): void
    {
        $revision = $this->revisionRepository->find($message->getRevisionId());
        if (null === $revision || null === $revision->getFile() || $revision->getFile()->getId() !== $message->getFileId()) {
            throw new \UnexpectedValueException();
        }

        $uploader = $revision->getUploader();
        if (null !== $uploader && null !== $uploader->getId()) {
            try {
                $this->bus->dispatch(new UpdateUserUploadSizeMessage($uploader->getId()));
            } catch (ExceptionInterface $e) {
                $this->logger->critical($e->getMessage());
            }
        }
        $workspace = $revision->getFile()->getWorkspace();
        if (null !== $workspace && null !== $workspace->getId()) {
            try {
                $this->bus->dispatch(new UpdateWorkspaceUploadSizeMessage($workspace->getId()));
            } catch (ExceptionInterface $e) {
                $this->logger->critical($e->getMessage());
            }
        }

        if (null !== $workspace && null !== $workspace->getId() && null !== $uploader && null !== $uploader->getId()) {
            try {
                $this->bus->dispatch(new UpdateMembershipUploadSizeMessage($uploader->getId(), $workspace->getId()));
            } catch (\Throwable $e) {
                $this->logger->critical($e->getMessage());
            }
        }
    }
}
