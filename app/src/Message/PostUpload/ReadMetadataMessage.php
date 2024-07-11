<?php

namespace App\Message\PostUpload;

final readonly class ReadMetadataMessage
{
    public function __construct(
        private int $fileId,
        private ?int $revisionId = null
    ) {
    }

    public function getFileId(): int
    {
        return $this->fileId;
    }

    public function getRevisionId(): ?int
    {
        return $this->revisionId;
    }
}
