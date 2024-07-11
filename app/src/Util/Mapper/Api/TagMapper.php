<?php

declare(strict_types=1);

namespace App\Util\Mapper\Api;

use App\Dto\Api\Tag;
use App\Util\Mapper\MapperException;

class TagMapper
{
    /**
     * @throws MapperException
     */
    public static function mapEntityToDto(\App\Entity\Tag $tag): Tag
    {
        if (null === $tag->getSlug() || null === $tag->getTitle()) {
            throw new MapperException(\App\Entity\Tag::class, Tag::class);
        }

        return (new Tag())
            ->setSlug($tag->getSlug())
            ->setTitle($tag->getTitle());
    }
}
