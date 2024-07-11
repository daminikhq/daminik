<?php

declare(strict_types=1);

namespace App\Dto\DatabaseLogger;

final class Changes extends MetaData
{
    public function __construct(
        protected string $field,
        protected ?string $oldValue = null,
        protected ?string $newValue = null,
    ) {
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function setField(string $field): Changes
    {
        $this->field = $field;

        return $this;
    }

    public function getOldValue(): ?string
    {
        return $this->oldValue;
    }

    public function setOldValue(?string $oldValue): Changes
    {
        $this->oldValue = $oldValue;

        return $this;
    }

    public function getNewValue(): ?string
    {
        return $this->newValue;
    }

    public function setNewValue(?string $newValue): Changes
    {
        $this->newValue = $newValue;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->field;
    }
}
