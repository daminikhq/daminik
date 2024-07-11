<?php

declare(strict_types=1);

namespace App\Dto\Category;

use App\Entity\Category;
use Symfony\Component\Validator\Constraints as Assert;

class Edit
{
    #[Assert\NotBlank]
    private ?string $title = null;
    private ?Category $parent = null;

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getParent(): ?Category
    {
        return $this->parent;
    }

    public function setParent(?Category $parent): self
    {
        $this->parent = $parent;

        return $this;
    }
}
