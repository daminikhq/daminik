<?php

declare(strict_types=1);

namespace App\Util\Mapper\Paginator;

use App\Dto\Collection\Collection;
use App\Entity\AssetCollection;
use App\Entity\FileAssetCollection;

class CollectionEntityToDtoMapper implements ItemMapperInterface
{
    public function map(mixed $origin): Collection
    {
        assert(assertion: $origin instanceof AssetCollection, description: new ItemMapperException());

        $files = [];
        $fileAssetCollections = $origin->getFileAssetCollections()->filter(fn (FileAssetCollection $fileAssetCollection) => !$fileAssetCollection->getFile()?->getDeletedAt() instanceof \DateTime)->slice(0, 4);
        foreach ($fileAssetCollections as $fileAssetCollection) {
            $files[] = $fileAssetCollection->getFile();
        }

        return new Collection(
            id: $origin->getId(),
            title: $origin->getTitle(),
            slug: $origin->getSlug(),
            public: $origin->isPublic(),
            createdAt: $origin->getCreatedAt(),
            updatedAt: $origin->getUpdatedAt(),
            files: array_filter($files),
            assetCount: $origin->getAssetCount() ?? 0
        );
    }
}
