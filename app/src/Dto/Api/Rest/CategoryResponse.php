<?php

declare(strict_types=1);

namespace App\Dto\Api\Rest;

use App\Dto\Api\Category;
use OpenApi\Attributes as OA;

#[OA\Schema(
    type: 'object'
)]
class CategoryResponse extends AssetsResponse
{
    protected ?Category $parent = null;

    /**
     * @var Category[]
     */
    protected array $children = [];

    public function getParent(): ?Category
    {
        return $this->parent;
    }

    public function setParent(?Category $parent): CategoryResponse
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Category[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * @param Category[] $children
     */
    public function setChildren(array $children): CategoryResponse
    {
        $this->children = $children;

        return $this;
    }
}
