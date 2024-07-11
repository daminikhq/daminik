<?php

namespace App\Message\Filesize;

final readonly class UpdateWorkspaceUploadSizeMessage
{
    public function __construct(
        private int $workspaceId
    ) {
    }

    public function getWorkspaceId(): int
    {
        return $this->workspaceId;
    }
}
