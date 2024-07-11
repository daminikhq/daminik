<?php

declare(strict_types=1);

namespace App\Dto\User;

use App\Dto\User\Interface\LocaleRequestChangeInterface;
use App\Dto\User\Interface\NameRequestInterface;
use App\Dto\User\Interface\UsernameRequestInterface;
use App\Entity\User;

class AccountRequest implements NameRequestInterface, UsernameRequestInterface, LocaleRequestChangeInterface
{
    private ?string $name = null;

    private ?string $username = null;

    public function __construct(
        private readonly User $user,
        private ?string $locale = null
    ) {
        if (null !== $this->user->getName()) {
            $this->name = $this->user->getName();
        }

        if (null !== $this->user->getUsername()) {
            $this->username = $this->user->getUsername();
        }
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(?string $locale): AccountRequest
    {
        $this->locale = $locale;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): AccountRequest
    {
        $this->name = $name;

        return $this;
    }

    public function setUsername(?string $username): AccountRequest
    {
        $this->username = is_string($username) ? strtolower($username) : null;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }
}
