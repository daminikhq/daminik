<?php

declare(strict_types=1);

namespace App\Dto\Utility;

use App\Dto\AbstractDto;

final class DateRange extends AbstractDto
{
    public function __construct(
        private readonly ?\DateTimeInterface $dateTime = null,
        private readonly ?\DateTimeInterface $after = null,
        private readonly ?\DateTimeInterface $before = null
    ) {
    }

    public function getDateTime(): ?\DateTimeInterface
    {
        return $this->dateTime;
    }

    public function getAfter(): ?\DateTimeInterface
    {
        return $this->after;
    }

    public function getBefore(): ?\DateTimeInterface
    {
        return $this->before;
    }
}
