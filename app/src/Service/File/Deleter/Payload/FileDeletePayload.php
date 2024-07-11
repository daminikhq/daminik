<?php

declare(strict_types=1);

namespace App\Service\File\Deleter\Payload;

use App\Entity\File;
use App\Service\File\Deleter\MiddlewarePayloadInterface;
use Psr\Log\LoggerInterface;

readonly class FileDeletePayload implements MiddlewarePayloadInterface
{
    public function __construct(
        private File $file,
        private LoggerInterface $logger,
    ) {
    }

    public function getFile(): File
    {
        return $this->file;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
