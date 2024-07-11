<?php

namespace App\Message\Filesize;

final readonly class UpdateMembershipUploadSizeMessage
{
    public function __construct(
        private int $userId,
        private int $workspaceId
    ) {
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getWorkspaceId(): int
    {
        return $this->workspaceId;
    }
}
