<?php

declare(strict_types=1);

namespace App\Service\File\Helper;

use App\Entity\File;
use App\Entity\Revision;
use App\Entity\RevisionFileStorageUrl;
use App\Exception\File\FileException;
use App\Exception\WorkspaceException;

interface UrlHelperInterface
{
    public function generateUrls(Revision $revision, ?int $width = null, ?int $height = null, bool $forceUpdate = false): ?RevisionFileStorageUrl;

    public function getSignedUrl(File $file, ?int $width = null, ?int $height = null, ?int $revisionCounter = null): string;

    /**
     * @return array{bucket:string, objectKey: string}
     *
     * @throws FileException
     * @throws WorkspaceException
     */
    public function getBucketAndObjectKey(File $file, ?int $width = null, ?int $height = null, ?Revision $revision = null, ?string $filepath = null): array;

    public function getPrivateUrl(File $file, ?int $width = null, ?int $height = null, ?int $revisionCounter = null): ?string;

    public function getThumbnailUrl(File $file, ?int $revisionCounter = null): ?string;

    public function getWorkspaceIcon(File $file): ?string;

    public function setPublicUrl(File $file, ?string $publicUrl, ?RevisionFileStorageUrl $storageUrl = null): ?RevisionFileStorageUrl;

    public function getPublicUrl(File $file, ?int $width = null, ?int $height = null, ?int $revisionCounter = null): ?string;

    public function getPublicThumbnailUrl(File $file, ?int $revisionCounter = null): ?string;

    /**
     * @return array{string, string}
     */
    public function getTimestampAndHash(File $file): array;

    public function validateTimestampAndHash(File $file, mixed $timestamp, mixed $hash): bool;
}
