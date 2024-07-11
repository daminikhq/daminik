<?php

declare(strict_types=1);

namespace App\Dto\File;

use App\Dto\AbstractDto;

class Metadata extends AbstractDto
{
    /**
     * @var array<string, mixed>|null
     */
    private ?array $exif = null;
    private ?int $width = null;
    private ?int $height = null;

    private ?string $title = null;
    private ?string $description = null;
    private ?string $tagString = null;
    private ?string $artist = null;
    private ?string $copyrightNotice = null;

    private ?string $accentColor = null;

    /**
     * @return array<string, mixed>|null
     */
    public function getExif(): ?array
    {
        return $this->exif;
    }

    /**
     * @param array<string, mixed>|null $exif
     */
    public function setExif(?array $exif): Metadata
    {
        $this->exif = $exif;

        return $this;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function setWidth(?int $width): Metadata
    {
        $this->width = $width;

        return $this;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function setHeight(?int $height): Metadata
    {
        $this->height = $height;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): Metadata
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): Metadata
    {
        $this->description = $description;

        return $this;
    }

    public function getTagString(): ?string
    {
        return $this->tagString;
    }

    public function setTagString(?string $tagString): Metadata
    {
        $this->tagString = $tagString;

        return $this;
    }

    public function getArtist(): ?string
    {
        return $this->artist;
    }

    public function setArtist(?string $artist): Metadata
    {
        $this->artist = $artist;

        return $this;
    }

    public function getCopyrightNotice(): ?string
    {
        return $this->copyrightNotice;
    }

    public function setCopyrightNotice(?string $copyrightNotice): Metadata
    {
        $this->copyrightNotice = $copyrightNotice;

        return $this;
    }

    public function getAccentColor(): ?string
    {
        return $this->accentColor;
    }

    public function setAccentColor(?string $accentColor): Metadata
    {
        $this->accentColor = $accentColor;

        return $this;
    }
}
