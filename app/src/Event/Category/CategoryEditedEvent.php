<?php

declare(strict_types=1);

namespace App\Event\Category;

use App\Entity\Category;
use Symfony\Contracts\EventDispatcher\Event;

class CategoryEditedEvent extends Event implements CategoryEventInterface
{
    public function __construct(
        private readonly Category $category
    ) {
    }

    public function getCategory(): Category
    {
        return $this->category;
    }
}
