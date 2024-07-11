<?php

declare(strict_types=1);

namespace App\Service\File;

use App\Entity\File;
use App\Enum\HandleDeleteAction;

interface DeleterInterface
{
    /**
     * @throws \ReflectionException
     */
    public function completelyDeleteFile(File $file): void;

    public function handleDeletionForm(File $file, ?HandleDeleteAction $deleteFormAction): void;

    public function emptyBin(): void;

    public function delete(File $file, bool $softDelete = true): void;

    public function unDeleteFile(File $file): void;
}
