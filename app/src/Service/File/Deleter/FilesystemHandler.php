<?php

declare(strict_types=1);

namespace App\Service\File\Deleter;

use App\Entity\File;
use App\Entity\Workspace;
use App\Exception\File\MissingWorkspaceException;
use App\Service\Filesystem\FilesystemRegistryInterface;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;

readonly class FilesystemHandler implements FilesystemHandlerInterface
{
    public function __construct(
        private FilesystemRegistryInterface $filesystemRegistry
    ) {
    }

    /**
     * @throws FilesystemException
     */
    public function deleteRecursive(FilesystemOperator $filesystem, string $filepath): void
    {
        $contents = $filesystem->listContents($filepath);
        foreach ($contents as $content) {
            if ($content->isFile()) {
                $filesystem->delete($content->path());
            }
            if ($content->isDir()) {
                $this->deleteRecursive($filesystem, $content->path());
            }
        }
        $filesystem->deleteDirectory($filepath);
    }

    /**
     * @throws MissingWorkspaceException
     */
    public function getFilesystemForFile(File $file): FilesystemOperator
    {
        $workspace = $file->getWorkspace();
        if (!$workspace instanceof Workspace) {
            throw new MissingWorkspaceException('Error accessing file system');
        }

        return $this->filesystemRegistry->getWorkspaceFilesystem($workspace);
    }
}
