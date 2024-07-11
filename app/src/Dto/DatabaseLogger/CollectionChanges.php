<?php

declare(strict_types=1);

namespace App\Dto\DatabaseLogger;

class CollectionChanges extends MetaData
{
    /**
     * @param Changes[] $changes
     */
    public function __construct(
        protected Collection $collection,
        protected array $changes = []
    ) {
    }

    public function getCollection(): Collection
    {
        return $this->collection;
    }

    public function setCollection(Collection $collection): CollectionChanges
    {
        $this->collection = $collection;

        return $this;
    }

    /**
     * @param Changes[] $changes
     */
    public function setChanges(array $changes): CollectionChanges
    {
        $this->changes = $changes;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->collection->getTitle();
    }
}
