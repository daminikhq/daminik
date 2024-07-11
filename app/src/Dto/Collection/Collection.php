<?php

declare(strict_types=1);

namespace App\Dto\Collection;

use App\Dto\AbstractDto;
use App\Entity\File;

class Collection extends AbstractDto
{
    /**
     * @param File[] $files
     */
    public function __construct(
        private readonly ?int $id = null,
        private readonly ?string $title = null,
        private readonly ?string $slug = null,
        private readonly ?bool $public = false,
        private readonly ?\DateTimeInterface $createdAt = null,
        private readonly ?\DateTimeInterface $updatedAt = null,
        private readonly array $files = [],
        private readonly int $assetCount = 0
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function getPublic(): ?bool
    {
        return $this->public;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    /**
     * @return File[]
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    public function getAssetCount(): int
    {
        return $this->assetCount;
    }
}
