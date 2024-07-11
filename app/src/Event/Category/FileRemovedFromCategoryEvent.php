<?php

declare(strict_types=1);

namespace App\Event\Category;

use App\Entity\File;
use App\Entity\FileCategory;
use Symfony\Contracts\EventDispatcher\Event;

class FileRemovedFromCategoryEvent extends Event implements FileCategoryEventInterface
{
    public function __construct(
        private readonly FileCategory $fileCategory,
        private readonly File $file
    ) {
    }

    public function getFileCategory(): FileCategory
    {
        return $this->fileCategory;
    }

    public function getFile(): File
    {
        return $this->file;
    }
}
