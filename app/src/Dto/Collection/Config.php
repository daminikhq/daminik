<?php

declare(strict_types=1);

namespace App\Dto\Collection;

use Symfony\Component\Validator\Constraints as Assert;

final class Config
{
    #[Assert\NotNull]
    private ?string $title = null;
    #[Assert\NotNull]
    private ?string $slug = null;
    #[Assert\NotNull]
    private ?bool $public = false;

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): Config
    {
        $this->title = $title;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): Config
    {
        $this->slug = $slug;

        return $this;
    }

    public function getPublic(): ?bool
    {
        return $this->public;
    }

    public function setPublic(?bool $public): Config
    {
        $this->public = $public;

        return $this;
    }
}
