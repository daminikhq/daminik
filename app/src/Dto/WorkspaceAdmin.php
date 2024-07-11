<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class WorkspaceAdmin
{
    public function __construct(#[Assert\NotBlank]
        private ?string $name, #[Assert\NotBlank]
        private ?string $locale,
        private ?bool $apiAccess = false
    ) {
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): WorkspaceAdmin
    {
        $this->name = $name;

        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(?string $locale): WorkspaceAdmin
    {
        $this->locale = $locale;

        return $this;
    }

    public function getApiAccess(): ?bool
    {
        return $this->apiAccess;
    }

    public function setApiAccess(bool $apiAccess): WorkspaceAdmin
    {
        $this->apiAccess = $apiAccess;

        return $this;
    }
}
