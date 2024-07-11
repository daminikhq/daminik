<?php

declare(strict_types=1);

namespace App\Dto\DatabaseLogger;

final class Collection extends MetaData
{
    public function __construct(
        protected ?int $collectionId = null,
        protected ?string $title = null,
        protected ?string $slug = null
    ) {
    }

    public function getCollectionId(): ?int
    {
        return $this->collectionId;
    }

    public function setCollectionId(?int $collectionId): Collection
    {
        $this->collectionId = $collectionId;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): Collection
    {
        $this->title = $title;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): Collection
    {
        $this->slug = $slug;

        return $this;
    }
}
