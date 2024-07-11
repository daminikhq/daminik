<?php

declare(strict_types=1);

namespace App\Service\File;

use App\Entity\File;
use App\Entity\Workspace;
use App\Exception\File\GetterException;

interface GetterInterface
{
    /**
     * @throws GetterException
     */
    public function getFile(Workspace $workspace, ?string $filename = null, ?string $slug = null, bool $includeDeleted = false): ?File;

    /**
     * @param array<string>|null $filename
     *
     * @return File[]
     */
    public function getFiles(Workspace $workspace, ?array $filename = null): array;
}
