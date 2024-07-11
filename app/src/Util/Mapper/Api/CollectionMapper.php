<?php

declare(strict_types=1);

namespace App\Util\Mapper\Api;

use App\Dto\Api\Collection;
use App\Entity\AssetCollection;
use App\Util\Mapper\MapperException;

class CollectionMapper
{
    /**
     * @throws MapperException
     */
    public static function mapEntityToDto(AssetCollection $assetCollection): Collection
    {
        if (null === $assetCollection->getSlug() || null === $assetCollection->getTitle()) {
            throw new MapperException(AssetCollection::class, Collection::class);
        }

        return (new Collection())
            ->setSlug($assetCollection->getSlug())
            ->setTitle($assetCollection->getTitle());
    }
}
