<?php

declare(strict_types=1);

namespace App\Message\PostUpload;

final readonly class CreateWorkspaceIconMessage
{
    public function __construct(
        private int $workspaceId,
    ) {
    }

    public function getWorkspaceId(): int
    {
        return $this->workspaceId;
    }
}
