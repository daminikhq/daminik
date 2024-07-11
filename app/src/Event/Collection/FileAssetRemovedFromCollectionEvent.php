<?php

declare(strict_types=1);

namespace App\Event\Collection;

use App\Entity\File;
use App\Entity\FileAssetCollection;
use Symfony\Contracts\EventDispatcher\Event;

class FileAssetRemovedFromCollectionEvent extends Event implements FileAssetCollectionEventInterface
{
    public function __construct(
        private readonly FileAssetCollection $fileAssetCollection,
        private readonly File $file
    ) {
    }

    public function getFileAssetCollection(): FileAssetCollection
    {
        return $this->fileAssetCollection;
    }

    public function getFile(): File
    {
        return $this->file;
    }
}
