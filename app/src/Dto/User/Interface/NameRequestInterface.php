<?php

declare(strict_types=1);

namespace App\Dto\User\Interface;

use App\Entity\User;

interface NameRequestInterface
{
    public function getUser(): User;

    public function getName(): ?string;

    public function setName(?string $name): self;
}
