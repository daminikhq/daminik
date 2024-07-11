<?php

declare(strict_types=1);

namespace App\Dto\Api;

use App\Dto\AbstractDto;

class Tag extends AbstractDto
{
    protected string $slug;
    protected string $title;

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }
}
