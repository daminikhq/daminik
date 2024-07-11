<?php

declare(strict_types=1);

namespace App\Event\Collection;

use App\Entity\FileAssetCollection;
use Symfony\Contracts\EventDispatcher\Event;

class FileAssetAddedToCollectionEvent extends Event implements FileAssetCollectionEventInterface
{
    public function __construct(
        private readonly FileAssetCollection $fileAssetCollection,
    ) {
    }

    public function getFileAssetCollection(): FileAssetCollection
    {
        return $this->fileAssetCollection;
    }
}
