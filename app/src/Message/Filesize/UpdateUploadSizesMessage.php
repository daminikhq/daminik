<?php

namespace App\Message\Filesize;

final readonly class UpdateUploadSizesMessage
{
    public function __construct(
        private int $fileId,
        private int $revisionId
    ) {
    }

    public function getFileId(): int
    {
        return $this->fileId;
    }

    public function getRevisionId(): int
    {
        return $this->revisionId;
    }
}
