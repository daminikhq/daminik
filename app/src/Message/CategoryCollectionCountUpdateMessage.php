<?php

namespace App\Message;

final readonly class CategoryCollectionCountUpdateMessage
{
    public function __construct(private ?int $fileId = null)
    {
    }

    public function getFileId(): ?int
    {
        return $this->fileId;
    }
}
