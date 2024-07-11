<?php

declare(strict_types=1);

namespace App\Util\Mapper\Api;

use App\Dto\Api\Rest\CategoryResponse;
use App\Entity\Category;
use App\Entity\File;
use App\Service\File\Helper\UrlHelperInterface;
use App\Util\Mapper\MapperException;
use App\Util\Paginator;

class CategoryResponseMapper
{
    /**
     * @throws MapperException
     */
    public static function mapPaginatorToCategoryResponse(
        Paginator $files,
        Category $category, UrlHelperInterface $urlHelper,
        bool $includePrivateUrls = true
    ): CategoryResponse {
        $response = new CategoryResponse(
            page: $files->getPage(),
            pages: $files->getPages(),
            total: $files->getTotal()
        );

        /** @var File $file */
        foreach ($files->getItems() as $file) {
            $response->addAsset(FileMapper::mapEntityToDto(file: $file, urlHelper: $urlHelper, includePrivateUrl: $includePrivateUrls));
        }

        if ($category->getParent() instanceof Category) {
            $response->setParent(CategoryMapper::mapEntityToDto($category->getParent()));
        }

        $children = [];
        /** @var Category $child */
        foreach ($category->getChildren() as $child) {
            $children[] = CategoryMapper::mapEntityToDto($child);
        }
        $response->setChildren($children);

        return $response;
    }
}
