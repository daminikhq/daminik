<?php

declare(strict_types=1);

namespace App\Util\Mapper\Api;

use App\Dto\Api\Category;
use App\Util\Mapper\MapperException;

class CategoryMapper
{
    /**
     * @throws MapperException
     */
    public static function mapEntityToDto(\App\Entity\Category $category): Category
    {
        if (null === $category->getSlug() || null === $category->getTitle()) {
            throw new MapperException(\App\Entity\Category::class, Category::class);
        }

        $parent = null;
        if ($category->getParent() instanceof \App\Entity\Category) {
            $parent = self::mapEntityToDto($category->getParent());
        }

        return (new Category())
            ->setSlug($category->getSlug())
            ->setTitle($category->getTitle())
            ->setParent($parent);
    }
}
