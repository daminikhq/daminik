<?php

declare(strict_types=1);

namespace App\Dto\Filter;

class BooleanFilter
{
    public function __construct(
        public string $key,
        public bool $value = false,
        public ?string $label = null
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setValue(bool $value): BooleanFilter
    {
        $this->value = $value;

        return $this;
    }

    public function getValue(): bool
    {
        return $this->value;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }
}
