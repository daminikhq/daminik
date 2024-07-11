<?php

declare(strict_types=1);

namespace App\Event\Category;

use App\Entity\FileCategory;
use Symfony\Contracts\EventDispatcher\Event;

class FileAddedToCategoryEvent extends Event implements FileCategoryEventInterface
{
    public function __construct(
        private readonly FileCategory $fileCategory,
    ) {
    }

    public function getFileCategory(): FileCategory
    {
        return $this->fileCategory;
    }
}
