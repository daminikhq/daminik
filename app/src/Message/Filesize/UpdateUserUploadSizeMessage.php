<?php

namespace App\Message\Filesize;

final readonly class UpdateUserUploadSizeMessage
{
    public function __construct(
        private int $userId
    ) {
    }

    public function getUserId(): int
    {
        return $this->userId;
    }
}
