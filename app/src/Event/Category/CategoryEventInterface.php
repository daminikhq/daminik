<?php

declare(strict_types=1);

namespace App\Event\Category;

use App\Entity\Category;

interface CategoryEventInterface
{
    public function getCategory(): Category;
}
