<?php

declare(strict_types=1);

namespace App\Service\Filesystem;

use App\Entity\FileSystem;
use App\Entity\Revision;
use App\Entity\Workspace;
use AsyncAws\S3\S3Client;
use League\Flysystem\FilesystemOperator;

interface FilesystemRegistryInterface
{
    public function getWorkspaceFilesystemConfig(Workspace $workspace): FileSystem;

    public function getWorkspaceFilesystem(Workspace $workspace): FilesystemOperator;

    public function getS3Client(Workspace $workspace): ?S3Client;

    public function getRevisionFilesystem(Revision $revision): FilesystemOperator;

    public function getRevisionFilesystemConfig(Revision $revision): FileSystem;
}
