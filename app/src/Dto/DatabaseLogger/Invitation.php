<?php

declare(strict_types=1);

namespace App\Dto\DatabaseLogger;

class Invitation extends MetaData
{
    public function __construct(
        protected ?string $sendToEmail = null,
        protected ?string $role = null
    ) {
    }

    public function getSendToEmail(): ?string
    {
        return $this->sendToEmail;
    }

    public function setSendToEmail(?string $sendToEmail): Invitation
    {
        $this->sendToEmail = $sendToEmail;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(?string $role): Invitation
    {
        $this->role = $role;

        return $this;
    }

    public function getTitle(): ?string
    {
        return implode(' ', array_filter([$this->sendToEmail, sprintf('(%s)', $this->role)]));
    }
}
