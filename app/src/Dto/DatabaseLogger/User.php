<?php

declare(strict_types=1);

namespace App\Dto\DatabaseLogger;

final class User extends MetaData
{
    public function __construct(
        protected int $userId,
        protected ?string $username,
        protected ?string $name,
    ) {
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): User
    {
        $this->userId = $userId;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): User
    {
        $this->username = $username;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): User
    {
        $this->name = $name;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->name ?? $this->username;
    }
}
