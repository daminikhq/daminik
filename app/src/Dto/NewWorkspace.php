<?php

declare(strict_types=1);

namespace App\Dto;

use App\Validator as AppAssert;
use Symfony\Component\Validator\Constraints as Assert;

final class NewWorkspace
{
    #[Assert\NotBlank]
    private ?string $name = null;

    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^[a-z0-9\-]+$/', message: 'Your subdomain can only contain letters, numbers and -')]
    #[AppAssert\AllowedWorkspaceSlug]
    private ?string $slug = null;

    private ?string $locale = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): NewWorkspace
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): NewWorkspace
    {
        if (is_string($slug)) {
            $slug = strtolower($slug);
        }
        $this->slug = $slug;

        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(?string $locale): NewWorkspace
    {
        $this->locale = $locale;

        return $this;
    }
}
