<?php

namespace App\Message;

final readonly class AssetCollectionUpdatedMessage
{
    public function __construct(
        private int $assetCollectionId,
        private ?int $assetId = null
    ) {
    }

    public function getAssetCollectionId(): int
    {
        return $this->assetCollectionId;
    }

    public function getAssetId(): ?int
    {
        return $this->assetId;
    }
}
