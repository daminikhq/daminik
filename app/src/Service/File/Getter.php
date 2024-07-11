<?php

declare(strict_types=1);

namespace App\Service\File;

use App\Entity\File;
use App\Entity\Workspace;
use App\Exception\File\GetterException;
use App\Repository\FileRepository;

readonly class Getter implements GetterInterface
{
    public function __construct(
        private FileRepository $fileRepository
    ) {
    }

    /**
     * $filename or $slug must be set.
     *
     * @throws GetterException
     */
    public function getFile(
        Workspace $workspace,
        ?string $filename = null,
        ?string $slug = null,
        bool $includeDeleted = false,
    ): ?File {
        if (null === $filename && null === $slug) {
            throw new GetterException('filename or slug must be set');
        }
        $criteria = ['workspace' => $workspace];
        if (null !== $slug) {
            $criteria['filenameSlug'] = $slug;
        }
        if (null !== $filename) {
            $criteria['filename'] = $filename;
        }
        if (!$includeDeleted) {
            $criteria['deletedAt'] = null;
        }

        return $this->fileRepository->findOneBy($criteria);
    }

    /**
     * @param array<string>|null $filename
     *
     * @return File[]
     */
    public function getFiles(Workspace $workspace, ?array $filename = null): array
    {
        if (null === $filename) {
            return $this->fileRepository->findBy(['workspace' => $workspace]);
        }

        return $this->fileRepository->findBy(['workspace' => $workspace, 'filename' => $filename]);
    }
}
