<?php

declare(strict_types=1);

namespace App\Dto\Filter;

class FilterFormDto
{
    /**
     * @param string[] $filetype
     */
    public function __construct(
        protected ?string $s = null,
        protected ?string $sort = null,
        protected array $filetype = [],
        protected ?string $tags = null,
        protected ?string $uploadedby = null,
        protected ?string $uploadedat = null
    ) {
    }

    /**
     * @return string[]
     */
    public function getFiletype(): array
    {
        return $this->filetype;
    }

    public function getTags(): ?string
    {
        return $this->tags;
    }

    public function getS(): ?string
    {
        return $this->s;
    }

    public function getSort(): ?string
    {
        return $this->sort;
    }

    public function getUploadedby(): ?string
    {
        return $this->uploadedby;
    }

    public function getUploadedat(): ?string
    {
        return $this->uploadedat;
    }
}
