<?php

declare(strict_types=1);

namespace App\Dto\User\Interface;

use App\Entity\User;

interface LocaleRequestChangeInterface
{
    public function getUser(): User;

    public function getLocale(): ?string;

    public function setLocale(?string $locale): self;
}
