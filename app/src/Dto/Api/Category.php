<?php

declare(strict_types=1);

namespace App\Dto\Api;

use App\Dto\AbstractDto;

class Category extends AbstractDto
{
    protected string $slug;
    protected string $title;
    protected ?Category $parent = null;

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): Category
    {
        $this->slug = $slug;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): Category
    {
        $this->title = $title;

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
