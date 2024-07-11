<?php

declare(strict_types=1);

namespace App\Service\File\Deleter;

use App\Entity\File;
use App\Exception\File\MissingWorkspaceException;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;

interface FilesystemHandlerInterface
{
    /**
     * @throws FilesystemException
     */
    public function deleteRecursive(FilesystemOperator $filesystem, string $filepath): void;

    /**
     * @throws MissingWorkspaceException
     */
    public function getFilesystemForFile(File $file): FilesystemOperator;
}
