<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\Entity\Workspace;

interface AutoCompleteQueriable
{
    /**
     * @return AutoCompleteItem[]
     */
    public function getForAutocomplete(Workspace $workspace, ?string $query = null, int $limit = 10, bool $cached = true): array;
}
