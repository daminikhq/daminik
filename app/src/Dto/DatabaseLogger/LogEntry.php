<?php

/** @noinspection PhpUnused */
declare(strict_types=1);

namespace App\Dto\DatabaseLogger;

use App\Dto\AbstractDto;
use App\Enum\UserAction;

class LogEntry extends AbstractDto
{
    public function __construct(
        private readonly UserAction $userAction,
        private readonly \DateTimeInterface $createdAt,
        private readonly ?User $user = null,
        private readonly ?MetaDataInterface $entityData = null,
        private readonly ?MetaDataInterface $metaData = null
    ) {
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getUserAction(): UserAction
    {
        return $this->userAction;
    }

    public function logAction(): ?string
    {
        return $this->userAction->value;
    }

    public function getUserName(): ?string
    {
        return $this->user?->getTitle();
    }

    public function getEntityData(): ?MetaDataInterface
    {
        return $this->entityData;
    }

    public function getMetaData(): ?MetaDataInterface
    {
        return $this->metaData;
    }
}
