<?php

declare(strict_types=1);

namespace App\Dto\Api;

use App\Dto\AbstractDto;
use OpenApi\Attributes as OA;

class Asset extends AbstractDto
{
    public function __construct(
        #[OA\Property(
            example: 'photo'
        )]
        protected string $slug,
        #[OA\Property(
            example: 'photo.png'
        )]
        protected string $filename,
        #[OA\Property(
            example: true
        )]
        protected bool $public,
        #[OA\Property(
            example: 'https://example.com/photo.png'
        )]
        protected ?string $publicUrl = null
    ) {
    }

    protected ?string $title = null;
    protected ?string $description = null;
    protected ?string $mime = null;

    protected ?string $url = null;

    protected ?int $width = null;
    protected ?int $height = null;

    protected ?Category $category = null;

    /** @var Tag[] */
    protected array $tags = [];

    /** @var Collection[] */
    protected array $collections = [];

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): Asset
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): Asset
    {
        $this->description = $description;

        return $this;
    }

    public function getPublic(): bool
    {
        return $this->public;
    }

    public function setPublic(bool $public): Asset
    {
        $this->public = $public;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): Asset
    {
        $this->slug = $slug;

        return $this;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): Asset
    {
        $this->filename = $filename;

        return $this;
    }

    public function getMime(): ?string
    {
        return $this->mime;
    }

    public function setMime(?string $mime): Asset
    {
        $this->mime = $mime;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): Asset
    {
        $this->url = $url;

        return $this;
    }

    public function getPublicUrl(): ?string
    {
        return $this->publicUrl;
    }

    public function setPublicUrl(?string $publicUrl): Asset
    {
        $this->publicUrl = $publicUrl;

        return $this;
    }

    /**
     * @return Tag[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @param Tag[] $tags
     */
    public function setTags(array $tags): Asset
    {
        $this->tags = $tags;

        return $this;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function setWidth(?int $width): Asset
    {
        $this->width = $width;

        return $this;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function setHeight(?int $height): Asset
    {
        $this->height = $height;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): Asset
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return Collection[]
     */
    public function getCollections(): array
    {
        return $this->collections;
    }

    /**
     * @param Collection[] $collections
     */
    public function setCollections(array $collections): Asset
    {
        $this->collections = $collections;

        return $this;
    }
}
