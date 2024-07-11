<?php

declare(strict_types=1);

namespace App\Dto\DatabaseLogger;

final class File extends MetaData
{
    public function __construct(
        protected ?int $fileId = null,
        protected ?string $fileName = null,
        protected ?string $mimeType = null,
        protected ?string $title = null,
        protected ?string $description = null,
        protected ?bool $public = null,
    ) {
    }

    public function getFileId(): ?int
    {
        return $this->fileId;
    }

    public function setFileId(?int $fileId): File
    {
        $this->fileId = $fileId;

        return $this;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(?string $fileName): File
    {
        $this->fileName = $fileName;

        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(?string $mimeType): File
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title ?? $this->fileName;
    }

    public function setTitle(?string $title): File
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): File
    {
        $this->description = $description;

        return $this;
    }

    public function getPublic(): ?bool
    {
        return $this->public;
    }

    public function setPublic(?bool $public): File
    {
        $this->public = $public;

        return $this;
    }
}
