<?php

declare(strict_types=1);

namespace App\Dto\DatabaseLogger;

class WorkspaceChanges extends MetaData
{
    /**
     * @param Changes[] $changes
     */
    public function __construct(
        protected Workspace $workspace,
        protected array $changes = []
    ) {
    }

    public function getWorkspace(): Workspace
    {
        return $this->workspace;
    }

    public function setWorkspace(Workspace $workspace): WorkspaceChanges
    {
        $this->workspace = $workspace;

        return $this;
    }

    /**
     * @param Changes[] $changes
     *
     * @return $this
     */
    public function setChanges(array $changes): self
    {
        $this->changes = $changes;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->workspace->getTitle();
    }
}
