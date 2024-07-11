<?php

declare(strict_types=1);

namespace App\Dto\Filter;

use App\Dto\AbstractDto;

class FilterMonth extends AbstractDto
{
    public function __construct(
        private readonly string $dateString,
        private readonly \DateTimeInterface $dateTime
    ) {
    }

    public function getDateString(): string
    {
        return $this->dateString;
    }

    public function getDateTime(): \DateTimeInterface
    {
        return $this->dateTime;
    }
}
