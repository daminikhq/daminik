<?php

namespace App\Message;

final readonly class AITaggingMessage
{
    public function __construct(
        private ?int $fileId,
        private ?int $userId
    ) {
    }

    public function getFileId(): ?int
    {
        return $this->fileId;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }
}
