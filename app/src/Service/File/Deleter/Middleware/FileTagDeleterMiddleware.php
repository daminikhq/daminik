<?php

declare(strict_types=1);

namespace App\Service\File\Deleter\Middleware;

use App\Service\File\Deleter\MiddlewareInterface;
use App\Service\File\Deleter\MiddlewarePayloadInterface;
use App\Service\File\Deleter\Payload\FileDeletePayload;
use Doctrine\ORM\EntityManagerInterface;

readonly class FileTagDeleterMiddleware implements MiddlewareInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function pipe(MiddlewarePayloadInterface $payload): MiddlewarePayloadInterface
    {
        assert($payload instanceof FileDeletePayload);
        $file = $payload->getFile();
        foreach ($file->getFileTags() as $fileTag) {
            $file->removeFileTag($fileTag);
            $this->entityManager->remove($fileTag);
        }
        $this->entityManager->flush();

        return $payload;
    }
}
