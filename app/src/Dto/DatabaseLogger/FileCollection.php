<?php

declare(strict_types=1);

namespace App\Dto\DatabaseLogger;

class FileCollection extends MetaData
{
    public function __construct(
        protected File $file,
        protected Collection $collection
    ) {
    }

    public function getFile(): File
    {
        return $this->file;
    }

    public function setFile(File $file): FileCollection
    {
        $this->file = $file;

        return $this;
    }

    public function getCollection(): Collection
    {
        return $this->collection;
    }

    public function setCollection(Collection $collection): FileCollection
    {
        $this->collection = $collection;

        return $this;
    }

    public function getTitle(): ?string
    {
        return implode(' - ', array_filter([$this->file->getTitle(), $this->collection->getTitle()]));
    }
}
