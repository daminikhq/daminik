<?php

declare(strict_types=1);

namespace App\Dto\DatabaseLogger;

interface MetaDataInterface extends \JsonSerializable
{
    /**
     * @return array<int|string, mixed>
     */
    public function toArray(): array;

    public function getTitle(): ?string;

    public function hasChanges(): bool;

    /**
     * @return Changes[]
     */
    public function getChanges(): array;
}
