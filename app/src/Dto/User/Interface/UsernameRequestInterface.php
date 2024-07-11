<?php

declare(strict_types=1);

namespace App\Dto\User\Interface;

use App\Entity\User;

interface UsernameRequestInterface
{
    public function getUser(): User;

    public function setUsername(?string $username): self;

    public function getUsername(): ?string;
}
