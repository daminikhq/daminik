<?php

declare(strict_types=1);

namespace App\Dto\Api;

use App\Dto\AbstractDto;

class CategoryCollection extends AbstractDto
{
    /** @var Category[] */
    protected array $categories = [];

    public function addCategory(Category $category): self
    {
        if (!in_array($category, $this->categories, true)) {
            $this->categories[] = $category;
        }

        return $this;
    }

    /**
     * @return Category[]
     */
    public function getCategories(): array
    {
        return $this->categories;
    }

    /**
     * @param Category[] $categories
     */
    public function setCategories(array $categories): CategoryCollection
    {
        $this->categories = $categories;

        return $this;
    }

    /**
     * @return array<int, array<int|string, mixed>>
     */
    public function toArray(bool $removeEmpty = false): array
    {
        $return = [];
        foreach ($this->categories as $category) {
            $return[] = $category->toArray(removeEmpty: $removeEmpty);
        }
        if ($removeEmpty) {
            $return = array_filter($return);
        }

        return $return;
    }
}
