<?php

declare(strict_types=1);

namespace App\Dto\Utility;

use App\Dto\Filter\FilterMonth;
use App\Enum\MimeType;

readonly class FilterOptions
{
    /**
     * @param MimeType[]    $mimeTypes
     * @param FilterMonth[] $months
     */
    public function __construct(
        private bool $filtered = false,
        private array $mimeTypes = [],
        private array $months = []
    ) {
    }

    /**
     * @return MimeType[]
     */
    public function getMimeTypes(): array
    {
        return $this->mimeTypes;
    }

    /**
     * @return FilterMonth[]
     */
    public function getMonths(): array
    {
        return $this->months;
    }

    public function isFiltered(): bool
    {
        return $this->filtered;
    }
}
