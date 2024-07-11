<?php

declare(strict_types=1);

namespace App\Dto\DatabaseLogger;

final class Category extends MetaData
{
    protected ?self $parent = null;

    public function __construct(
        protected ?int $categoryId = null,
        protected ?string $title = null,
        protected ?string $slug = null
    ) {
    }

    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }

    public function setCategoryId(?int $categoryId): Category
    {
        $this->categoryId = $categoryId;

        return $this;
    }

    public function getTitle(): ?string
    {
        if ($this->parent instanceof Category) {
            return trim(implode(' > ', array_filter([$this->parent->getTitle(), $this->title])));
        }

        return $this->title;
    }

    public function setTitle(?string $title): Category
    {
        $this->title = $title;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): Category
    {
        $this->slug = $slug;

        return $this;
    }

    public function getParent(): ?Category
    {
        return $this->parent;
    }

    public function setParent(?Category $parent): Category
    {
        $this->parent = $parent;

        return $this;
    }
}
