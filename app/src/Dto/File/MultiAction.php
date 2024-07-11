<?php

declare(strict_types=1);

namespace App\Dto\File;

use App\Entity\AssetCollection;
use App\Entity\Category;
use App\Entity\File;
use App\Entity\User;

class MultiAction
{
    /**
     * @param File[] $files
     */
    public function __construct(
        private \App\Enum\MultiAction $action,
        private User $user,
        private array $files = [],
        private ?AssetCollection $collection = null,
        private ?Category $category = null
    ) {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): MultiAction
    {
        $this->user = $user;

        return $this;
    }

    public function getAction(): \App\Enum\MultiAction
    {
        return $this->action;
    }

    public function setAction(\App\Enum\MultiAction $action): MultiAction
    {
        $this->action = $action;

        return $this;
    }

    /**
     * @return File[]
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * @param File[] $files
     */
    public function setFiles(array $files): MultiAction
    {
        $this->files = $files;

        return $this;
    }

    public function getCollection(): ?AssetCollection
    {
        return $this->collection;
    }

    public function setCollection(?AssetCollection $collection): MultiAction
    {
        $this->collection = $collection;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): MultiAction
    {
        $this->category = $category;

        return $this;
    }
}
