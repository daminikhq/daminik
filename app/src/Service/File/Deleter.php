<?php

declare(strict_types=1);

namespace App\Service\File;

use App\Entity\File;
use App\Enum\HandleDeleteAction;
use App\Enum\UserAction;
use App\Message\CategoryCollectionCountUpdateMessage;
use App\Message\CompletelyDeleteAssetMessage;
use App\Message\Filesize\UpdateUploadSizesMessage;
use App\Repository\FileRepository;
use App\Service\DatabaseLogger\DatabaseLoggerInterface;
use App\Service\File\Deleter\Payload\FileDeletePayload;
use App\Service\File\Deleter\Pipeline\FileDeletePipeline;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class Deleter implements DeleterInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $deleteLogger,
        private MessageBusInterface $bus,
        private DatabaseLoggerInterface $databaseLogger,
        private FileRepository $fileRepository,
        private FileDeletePipeline $deletePipeline
    ) {
    }

    /**
     * @throws \ReflectionException
     */
    public function completelyDeleteFile(File $file): void
    {
        if (!$file->getCompletelyDeleteStarted() instanceof \DateTimeImmutable) {
            $file->setCompletelyDeleteStarted(new \DateTimeImmutable());
            $this->entityManager->flush();
        }

        $deletePayload = new FileDeletePayload(file: $file, logger: $this->deleteLogger);
        $this->deletePipeline->pipe($deletePayload);
    }

    /**
     * @throws ExceptionInterface
     */
    public function handleDeletionForm(File $file, ?HandleDeleteAction $deleteFormAction): void
    {
        if (!$deleteFormAction instanceof HandleDeleteAction || null === $file->getId()) {
            return;
        }
        if (HandleDeleteAction::DELETE === $deleteFormAction) {
            $this->bus->dispatch(new CompletelyDeleteAssetMessage($file->getId()));
            $this->databaseLogger->log(UserAction::COMPLETELY_DELETE_FILE, $file);
            $this->entityManager->flush();

            return;
        }
        $file->setDeletedAt(null);
        $this->databaseLogger->log(UserAction::UNDELETE_FILE, $file);
        $this->entityManager->flush();
        foreach ($file->getRevisions() as $revision) {
            if (null !== $file->getId() && null !== $revision->getId()) {
                $this->bus->dispatch(new UpdateUploadSizesMessage($file->getId(), $revision->getId()));
            }
        }
    }

    /**
     * @throws ExceptionInterface
     */
    public function emptyBin(): void
    {
        $files = $this->fileRepository->findDeletedBefore(new \DateTime());
        foreach ($files as $file) {
            $file->setCompletelyDeleteStarted(new \DateTimeImmutable());
            $this->databaseLogger->log(UserAction::COMPLETELY_DELETE_FILE, $file);
            if (null !== $file->getId()) {
                $this->bus->dispatch(new CompletelyDeleteAssetMessage($file->getId()));
            }
        }
        $this->entityManager->flush();
    }

    /**
     * @throws ExceptionInterface
     */
    public function delete(File $file, bool $softDelete = true): void
    {
        $id = $file->getId();
        if ($softDelete || !$file->getDeletedAt() instanceof \DateTime) {
            $file->setDeletedAt(new \DateTime());

            $this->databaseLogger->log(UserAction::DELETE_FILE, $file);
            $this->bus->dispatch(new CategoryCollectionCountUpdateMessage($file->getId()));

            $this->entityManager->flush();
            foreach ($file->getRevisions() as $revision) {
                if (null !== $file->getId() && null !== $revision->getId()) {
                    $this->bus->dispatch(new UpdateUploadSizesMessage($file->getId(), $revision->getId()));
                }
            }

            return;
        }

        if (null !== $id) {
            $this->bus->dispatch(new CompletelyDeleteAssetMessage($id));
            $this->databaseLogger->log(UserAction::COMPLETELY_DELETE_FILE, $file);
            $this->entityManager->flush();
        }
    }

    /**
     * @throws ExceptionInterface
     */
    public function unDeleteFile(File $file): void
    {
        $file->setDeletedAt(null);

        $this->databaseLogger->log(UserAction::UNDELETE_FILE, $file);
        $this->entityManager->flush();
        $this->bus->dispatch(new CategoryCollectionCountUpdateMessage($file->getId()));
        foreach ($file->getRevisions() as $revision) {
            if (null !== $file->getId() && null !== $revision->getId()) {
                $this->bus->dispatch(new UpdateUploadSizesMessage($file->getId(), $revision->getId()));
            }
        }
    }
}
