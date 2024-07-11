<?php

namespace App\MessageHandler\PostUpload;

use App\Dto\File\Metadata;
use App\Entity\File;
use App\Exception\File\MissingWorkspaceException;
use App\Exception\FileHandlerException;
use App\Message\PostUpload\ReadMetadataMessage;
use App\Repository\FileRepository;
use App\Repository\RevisionRepository;
use App\Service\File\MetadataHandler;
use App\Service\Tag\TagHandlerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class ReadMetadataMessageHandler
{
    public function __construct(
        private LoggerInterface $logger,
        private RevisionRepository $revisionRepository,
        private MetadataHandler $metadataHandler,
        private TagHandlerInterface $tagHandler,
        private EntityManagerInterface $entityManager,
        private FileRepository $fileRepository
    ) {
    }

    /**
     * @throws FileHandlerException
     * @throws \JsonException
     * @throws MissingWorkspaceException
     *
     * @noinspection DuplicatedCode
     */
    public function __invoke(ReadMetadataMessage $message): void
    {
        $this->logger->debug(__METHOD__, [
            'fileId' => $message->getFileId(),
            'revision' => $message->getRevisionId(),
        ]);
        $file = $this->fileRepository->find($message->getFileId());
        if (!$file instanceof File) {
            $this->logger->notice('File ID unknown');

            return;
        }

        if (null !== $message->getRevisionId()) {
            $revision = $this->revisionRepository->find($message->getRevisionId());
        } else {
            $revision = $file->getActiveRevision();
        }

        if (null === $revision) {
            $this->logger->notice('Revision unknown');

            return;
        }

        $fileMetaData = $this->metadataHandler->extractMetadata($revision);

        if ($fileMetaData instanceof Metadata) {
            $revision->setRawExif($fileMetaData->getExif());
            $revision->setWidth($fileMetaData->getWidth());
            $revision->setHeight($fileMetaData->getHeight());
            $revision->setAccentColor($fileMetaData->getAccentColor());

            if (null === $file->getTitle()) {
                $file->setTitle($fileMetaData->getTitle());
            }
            if (null === $file->getDescription()) {
                $file->setDescription($fileMetaData->getDescription());
            }
        }

        $this->entityManager->flush();

        if ($fileMetaData instanceof Metadata && null !== $fileMetaData->getTagString()) {
            $this->tagHandler->addTags($file, $fileMetaData->getTagString(), $revision->getUploader());
        }
    }
}
