<?php

declare(strict_types=1);

namespace App\Dto\DatabaseLogger;

final class FileChanges extends MetaData
{
    /**
     * @param Changes[] $changes
     */
    public function __construct(
        protected File $file,
        protected array $changes = []
    ) {
    }

    public function getFile(): File
    {
        return $this->file;
    }

    public function setFile(File $file): FileChanges
    {
        $this->file = $file;

        return $this;
    }

    /**
     * @param Changes[] $changes
     */
    public function setChanges(array $changes): FileChanges
    {
        $this->changes = $changes;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->file->getTitle();
    }
}
