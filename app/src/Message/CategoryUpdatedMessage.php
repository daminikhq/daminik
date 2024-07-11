<?php

declare(strict_types=1);

namespace App\Message;

final readonly class CategoryUpdatedMessage
{
    public function __construct(
        private int $categoryId,
        private ?int $assetId = null
    ) {
    }

    public function getCategoryId(): int
    {
        return $this->categoryId;
    }

    public function getAssetId(): ?int
    {
        return $this->assetId;
    }
}
