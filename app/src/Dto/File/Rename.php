<?php

declare(strict_types=1);

namespace App\Dto\File;

class Rename
{
    private ?string $slug = null;

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): Rename
    {
        $this->slug = $slug;

        return $this;
    }
}
