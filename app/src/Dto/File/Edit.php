<?php

declare(strict_types=1);

namespace App\Dto\File;

use App\Entity\AssetCollection;
use App\Entity\Category;

final class Edit
{
    /**
     * @param AssetCollection[] $assetCollections
     */
    public function __construct(
        private ?string $title = null,
        private ?string $description = null,
        private ?bool $public = false,
        private ?string $tags = null,
        private ?Category $category = null,
        private array $assetCollections = []
    ) {
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): Edit
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): Edit
    {
        $this->description = $description;

        return $this;
    }

    public function isPublic(): ?bool
    {
        return $this->public;
    }

    public function setPublic(?bool $public): Edit
    {
        $this->public = $public;

        return $this;
    }

    public function getTags(): ?string
    {
        return $this->tags;
    }

    public function setTags(?string $tags): Edit
    {
        $this->tags = $tags;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): Edit
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return AssetCollection[]
     */
    public function getAssetCollections(): array
    {
        return $this->assetCollections;
    }

    /**
     * @param AssetCollection[] $assetCollections
     */
    public function setAssetCollections(array $assetCollections): Edit
    {
        $this->assetCollections = $assetCollections;

        return $this;
    }
}
