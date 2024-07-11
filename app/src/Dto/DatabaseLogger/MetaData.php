<?php

declare(strict_types=1);

namespace App\Dto\DatabaseLogger;

use App\Dto\AbstractDto;

abstract class MetaData extends AbstractDto implements MetaDataInterface
{
    /**
     * @var Changes[]
     */
    protected array $changes = [];

    public function hasChanges(): bool
    {
        return [] !== $this->changes;
    }

    /**
     * @return Changes[]
     */
    public function getChanges(): array
    {
        return $this->changes;
    }
}
