<?php

declare(strict_types=1);

namespace App\Dto\DatabaseLogger;

class CategoryChanges extends MetaData
{
    /**
     * @param Changes[] $changes
     */
    public function __construct(
        protected Category $category,
        protected array $changes = []
    ) {
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function setCategory(Category $category): CategoryChanges
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @param Changes[] $changes
     */
    public function setChanges(array $changes): CategoryChanges
    {
        $this->changes = $changes;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->category->getTitle();
    }
}
