<?php

namespace App\MessageHandler\PostUpload;

use App\Entity\File;
use App\Enum\MimeType;
use App\Message\PostUpload\CreateThumbnailMessage;
use App\Repository\FileRepository;
use App\Repository\RevisionRepository;
use App\Service\File\Resizer;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CreateThumbnailMessageHandler
{
    public function __construct(
        private LoggerInterface $logger,
        private FileRepository $fileRepository,
        private RevisionRepository $revisionRepository,
        private Resizer $resizer,
        private EntityManagerInterface $entityManager
    ) {
    }

    /** @noinspection DuplicatedCode */
    public function __invoke(CreateThumbnailMessage $message): void
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

        if (null !== $revision && $revision->getMime() === MimeType::SVG->value) {
            // No thumbnails for SVGs
            return;
        }

        try {
            $this->resizer->resize($file, null, 440, $revision);
            $this->entityManager->flush();
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage(), [
                'exception' => $e::class,
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }
}
