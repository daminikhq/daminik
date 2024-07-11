<?php

declare(strict_types=1);

namespace App\Event\Category;

use App\Entity\FileCategory;

interface FileCategoryEventInterface
{
    public function getFileCategory(): FileCategory;
}
