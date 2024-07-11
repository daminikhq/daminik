<?php

declare(strict_types=1);

namespace App\Event\Collection;

use App\Entity\FileAssetCollection;

interface FileAssetCollectionEventInterface
{
    public function getFileAssetCollection(): FileAssetCollection;
}
