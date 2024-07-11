<?php

declare(strict_types=1);

namespace App\Service\File\Deleter\Middleware;

use App\Exception\File\MissingWorkspaceException;
use App\Service\File\Deleter\FilesystemHandlerInterface;
use App\Service\File\Deleter\MiddlewareInterface;
use App\Service\File\Deleter\MiddlewarePayloadInterface;
use App\Service\File\Deleter\Payload\FileDeletePayload;
use App\Service\File\Helper\FilePathHelper;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemException;

readonly class FileDeleterMiddleware implements MiddlewareInterface
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

        $file->getIconWorkspace()?->setIconFile(null);
        $this->entityManager->flush();

        $this->entityManager->remove($file);

        $this->entityManager->flush();

        if (null !== $file->getFilenameSlug()) {
            $filesystem = $this->filesystemHandler->getFilesystemForFile($file);
            $filepath = FilePathHelper::getFilePath(filename: null, filenameSlug: $file->getFilenameSlug());

            $this->filesystemHandler->deleteRecursive($filesystem, $filepath);
        }

        return $payload;
    }
}
