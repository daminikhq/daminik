<?php

declare(strict_types=1);

namespace App\Service\File\Deleter\Middleware;

use App\Entity\File;
use App\Entity\Revision;
use App\Exception\File\MissingWorkspaceException;
use App\Service\File\Deleter\FilesystemHandlerInterface;
use App\Service\File\Deleter\MiddlewareInterface;
use App\Service\File\Deleter\MiddlewarePayloadInterface;
use App\Service\File\Deleter\Payload\FileDeletePayload;
use App\Service\File\Helper\FilePathHelper;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemException;

readonly class FileRevisionDeleterMiddleware implements MiddlewareInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private FilesystemHandlerInterface $filesystemHandler
    ) {
    }

    /**
     * @throws FilesystemException
     * @throws MissingWorkspaceException
     */
    public function pipe(MiddlewarePayloadInterface $payload): MiddlewarePayloadInterface
    {
        assert($payload instanceof FileDeletePayload);
        $file = $payload->getFile();
        foreach ($file->getRevisions() as $revision) {
            $this->completelyDeleteRevision($revision, $file);
        }

        return $payload;
    }

    /**
     * @throws FilesystemException
     * @throws MissingWorkspaceException
     */
    private function completelyDeleteRevision(Revision $revision, File $file): void
    {
        $filepath = null;
        if (null !== $file->getFilenameSlug()) {
            $filepath = FilePathHelper::getFilePath(filename: null, filenameSlug: $file->getFilenameSlug(), revisionCounter: $revision->getCounter());
        }

        if ($revision === $file->getActiveRevision()) {
            $file->setActiveRevision(null);
            $this->entityManager->flush();
        }

        foreach ($revision->getStorageUrls() as $storageUrl) {
            $revision->removeStorageUrl($storageUrl);
            $this->entityManager->remove($storageUrl);
        }
        $this->entityManager->remove($revision);
        $this->entityManager->flush();

        $filesystem = $this->filesystemHandler->getFilesystemForFile($file);
        if (null !== $filepath) {
            $this->filesystemHandler->deleteRecursive($filesystem, $filepath);
        }
    }
}
