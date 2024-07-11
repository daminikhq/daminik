<?php

declare(strict_types=1);

namespace App\Dto\File;

use App\Entity\User;
use App\Entity\Workspace;

final class Upload
{
    /** @noinspection AutowireWrongClass */
    public function __construct(
        private User $uploader,
        private Workspace $workspace,
        private string $context = 'home'
    ) {
    }

    public function getUploader(): User
    {
        return $this->uploader;
    }

    public function setUploader(User $uploader): Upload
    {
        $this->uploader = $uploader;

        return $this;
    }

    public function getWorkspace(): Workspace
    {
        return $this->workspace;
    }

    public function setWorkspace(Workspace $workspace): Upload
    {
        $this->workspace = $workspace;

        return $this;
    }

    public function getContext(): string
    {
        return $this->context;
    }

    public function setContext(string $context): void
    {
        $this->context = $context;
    }
}
