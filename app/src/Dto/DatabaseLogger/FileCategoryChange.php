<?php

declare(strict_types=1);

namespace App\Dto\DatabaseLogger;

class FileCategoryChange extends MetaData
{
    public function __construct(
        protected File $file,
        protected ?Category $oldCategory = null,
        protected ?Category $newCategory = null
    ) {
    }

    public function getFile(): File
    {
        return $this->file;
    }

    public function getOldCategory(): ?Category
    {
        return $this->oldCategory;
    }

    public function getNewCategory(): ?Category
    {
        return $this->newCategory;
    }

    public function getTitle(): ?string
    {
        return implode(' - ', array_filter([$this->oldCategory?->getTitle(), $this->newCategory?->getTitle()]));
    }
}
