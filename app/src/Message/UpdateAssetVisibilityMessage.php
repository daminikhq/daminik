<?php

namespace App\Message;

final readonly class UpdateAssetVisibilityMessage
{
    public function __construct(
        private ?int $assetId = null,
        private ?int $workspaceId = null,
    ) {
    }

    public function getAssetId(): ?int
    {
        return $this->assetId;
    }

    public function getWorkspaceId(): ?int
    {
        return $this->workspaceId;
    }
}
