<?php

declare(strict_types=1);

namespace App\Service\File\Deleter\Middleware;

use App\Repository\FileUserMetaDataRepository;
use App\Service\File\Deleter\MiddlewareInterface;
use App\Service\File\Deleter\MiddlewarePayloadInterface;
use App\Service\File\Deleter\Payload\FileDeletePayload;
use Doctrine\ORM\EntityManagerInterface;

readonly class FileUserMetaDataDeleterMiddleware implements MiddlewareInterface
{
    public function __construct(
        private FileUserMetaDataRepository $fileUserMetaDataRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function pipe(MiddlewarePayloadInterface $payload): MiddlewarePayloadInterface
    {
        assert($payload instanceof FileDeletePayload);
        $file = $payload->getFile();
        foreach ($this->fileUserMetaDataRepository->findBy(['file' => $file]) as $userMetaDatum) {
            $this->entityManager->remove($userMetaDatum);
        }
        $this->entityManager->flush();

        return $payload;
    }
}
