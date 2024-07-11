<?php

declare(strict_types=1);

namespace App\Service\File\Helper;

use App\Entity\File;
use App\Entity\Revision;
use App\Entity\RevisionFileStorageUrl;
use App\Entity\Workspace;
use App\Enum\FilesystemType;
use App\Enum\MimeType;
use App\Exception\File\MissingFilenameSlugException;
use App\Exception\File\MissingRevisionException;
use App\Exception\File\MissingWorkspaceException;
use App\Exception\File\RevisionWithoutFileException;
use App\Exception\FileHandlerException;
use App\Exception\Filesystem\MissingClientException;
use App\Exception\Workspace\MissingConfigException;
use App\Repository\RevisionFileStorageUrlRepository;
use App\Repository\RevisionRepository;
use App\Service\Filesystem\FilesystemRegistryInterface;
use AsyncAws\S3\Input\GetObjectRequest;
use AsyncAws\S3\S3Client;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

readonly class UrlHelper implements UrlHelperInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private FilesystemRegistryInterface $filesystemRegistry,
        private RevisionFileStorageUrlRepository $urlRepository,
        private RevisionRepository $revisionRepository,
        private RouterInterface $router,
        private string $defaultPrefix,
    ) {
    }

    /**
     * @throws RevisionWithoutFileException
     * @throws MissingClientException
     * @throws MissingWorkspaceException
     * @throws FileHandlerException
     */
    public function generateUrls(Revision $revision, ?int $width = null, ?int $height = null, bool $forceUpdate = false): ?RevisionFileStorageUrl
    {
        if (!$revision->getFile() instanceof File) {
            throw new RevisionWithoutFileException();
        }

        /*
         * For now we only do this for S3 filesystems
         */
        if ($revision->getFile()->getWorkspace()?->getFilesystem()?->getType() !== FilesystemType::S3->value) {
            return null;
        }

        $storageUrl = $this->urlRepository->findOneBy(['revision' => $revision, 'height' => $height, 'width' => $width]);
        if (null === $storageUrl) {
            $storageUrl = (new RevisionFileStorageUrl())
                ->setRevision($revision)
                ->setHeight($height)
                ->setWidth($width);
            $this->entityManager->persist($storageUrl);
            $forceUpdate = true;
        }

        $now = new \DateTimeImmutable();
        if (false === $forceUpdate && null !== $storageUrl->getUrl() && null !== $storageUrl->getTimeout() && $storageUrl->getTimeout() > $now) {
            return $storageUrl;
        }

        $timeout = $now->add(new \DateInterval('PT12H'));
        $expires = $timeout->add(new \DateInterval('PT12H'));
        $storageUrl->setTimeout($timeout)
            ->setUrl($this->getSignedUrl($revision->getFile(), $width, $height, $revision->getCounter(), $expires));

        return $storageUrl;
    }

    /**
     * @throws FileHandlerException
     * @throws MissingWorkspaceException
     * @throws MissingClientException
     */
    public function getSignedUrl(File $file, ?int $width = null, ?int $height = null, ?int $revisionCounter = null, ?\DateTimeImmutable $expires = null): string
    {
        try {
            $bucketAndObjectKey = $this->getBucketAndObjectKey($file, $width, $height, FileHelper::getRevision($file, $revisionCounter));
        } catch (\Throwable $e) {
            throw new FileHandlerException($e->getMessage(), $e->getCode(), $e);
        }

        $input = new GetObjectRequest([
            'Bucket' => $bucketAndObjectKey['bucket'],
            'Key' => $bucketAndObjectKey['objectKey'],
        ]);

        $workspace = $file->getWorkspace();
        if (!$workspace instanceof Workspace) {
            throw new MissingWorkspaceException();
        }
        $client = $this->filesystemRegistry->getS3Client($workspace);
        if (!$client instanceof S3Client) {
            throw new MissingClientException();
        }

        return $client->presign($input, $expires);
    }

    /**
     * @return array{bucket:string, objectKey: string}
     *
     * @throws MissingConfigException
     * @throws MissingFilenameSlugException
     * @throws MissingWorkspaceException
     */
    public function getBucketAndObjectKey(File $file, ?int $width = null, ?int $height = null, ?Revision $revision = null, ?string $filepath = null): array
    {
        if (!$file->getWorkspace() instanceof Workspace) {
            throw new MissingWorkspaceException();
        }
        if (null === $file->getFilenameSlug()) {
            throw new MissingFilenameSlugException();
        }
        if (!$revision instanceof Revision) {
            $revision = $file->getActiveRevision();
        }

        $config = $revision?->getFilesystem()?->getConfig();
        if (null === $config) {
            $config = $file->getWorkspace()->getFilesystem()?->getConfig();
        }
        if (!is_array($config) || !array_key_exists('Bucket', $config) || !is_string($config['Bucket'])) {
            throw new MissingConfigException('', 0, $config);
        }

        if (null === $filepath) {
            if (null === $width && null === $height) {
                $filepath = FilePathHelper::getFilePath($file->getFilename(), $file->getFilenameSlug(), $revision?->getCounter());
            } else {
                $filepath = FilePathHelper::getFilePathWithSize($file->getFilenameSlug(), $width, $height, $revision?->getCounter());
            }
        }
        $prefix = implode('/', array_filter([$this->defaultPrefix, $file->getWorkspace()->getSlug()]));
        $objectKey = sprintf('%s/%s', $prefix, $filepath);

        return [
            'bucket' => $config['Bucket'],
            'objectKey' => $objectKey,
        ];
    }

    public function getPrivateUrl(File $file, ?int $width = null, ?int $height = null, ?int $revisionCounter = null): ?string
    {
        return match ($file->getWorkspace()?->getFilesystem()?->getType()) {
            FilesystemType::LOCAL->value => $this->getLocalPrivateUrl($file, $width, $height, $revisionCounter),
            FilesystemType::S3->value => $this->getS3PrivateUrl($file, $width, $height, $revisionCounter),
            default => null,
        };
    }

    /**
     * @throws MissingRevisionException
     */
    public function getPublicUrl(File $file, ?int $width = null, ?int $height = null, ?int $revisionCounter = null): ?string
    {
        return match ($file->getWorkspace()?->getFilesystem()?->getType()) {
            FilesystemType::LOCAL->value => $this->getLocalPublicUrl(file: $file, width: $width, height: $height),
            FilesystemType::S3->value => $this->getS3PublicUrl(file: $file, width: $width, height: $height, revisionCounter: $revisionCounter),
            default => null,
        };
    }

    /**
     * @throws MissingRevisionException
     */
    public function getPublicThumbnailUrl(File $file, ?int $revisionCounter = null): ?string
    {
        return match ($file->getWorkspace()?->getFilesystem()?->getType()) {
            FilesystemType::LOCAL->value => $this->getLocalPublicUrl($file, null, 440),
            FilesystemType::S3->value => $this->getS3PublicUrl($file, null, 440, $revisionCounter),
            default => null,
        };
    }

    public function getThumbnailUrl(File $file, ?int $revisionCounter = null): ?string
    {
        return match ($file->getWorkspace()?->getFilesystem()?->getType()) {
            FilesystemType::LOCAL->value => $this->getLocalPrivateUrl($file, null, 440, $revisionCounter),
            FilesystemType::S3->value => $this->getS3PrivateUrl($file, null, 440, $revisionCounter),
            default => null,
        };
    }

    public function getWorkspaceIcon(File $file): ?string
    {
        return match ($file->getWorkspace()?->getFilesystem()?->getType()) {
            FilesystemType::LOCAL->value => $this->getLocalPrivateUrl($file, 256, 256),
            FilesystemType::S3->value => $this->getS3PrivateUrl($file, 256, 256),
            default => null,
        };
    }

    private function getLocalPrivateUrl(File $file, ?int $width, ?int $height, ?int $revisionCounter = null): ?string
    {
        if (null === $width && null === $height) {
            return $this->router->generate('workspace_download_file', [
                'filename' => $file->getFilename(),
                'v' => $file->getActiveRevision()?->getCounter() ?? 0,
                'subdomain' => $file->getWorkspace()?->getSlug(),
            ], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        if (null === $width && 440 === $height) {
            if (null === $revisionCounter) {
                return $this->router->generate('workspace_download_thumbnail', [
                    'filename' => $file->getFilename(),
                    'v' => $file->getActiveRevision()?->getCounter() ?? 0,
                    'subdomain' => $file->getWorkspace()?->getSlug(),
                ], UrlGeneratorInterface::ABSOLUTE_URL);
            }

            return $this->router->generate('workspace_download_revision_thumbnail', [
                'filename' => $file->getFilename(),
                'revision' => $revisionCounter,
                'subdomain' => $file->getWorkspace()?->getSlug(),
            ], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        if (256 === $width && 256 === $height && $file->getWorkspace() instanceof Workspace && null !== $file->getWorkspace()->getSlug()) {
            return $this->router->generate('workspace_download_icon', [
                'workspaceSlug' => $file->getWorkspace()->getSlug(),
                'v' => $file->getActiveRevision()?->getCounter() ?? 0,
                'subdomain' => $file->getWorkspace()->getSlug(),
            ], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        return null;
    }

    private function getLocalPublicUrl(File $file, ?int $width, ?int $height): string
    {
        $parameters = [
            'filename' => $file->getFilename(),
            'subdomain' => $file->getWorkspace()?->getSlug(),
        ];

        if (true !== $file->isPublic()) {
            [$timestamp, $hash] = $this->getTimestampAndHash($file);
            $parameters['timestamp'] = $timestamp;
            $parameters['hash'] = $hash;
        }
        if (null === $width && 440 === $height) {
            return $this->router->generate('public_file_thumbnail',
                $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
        }

        return $this->router->generate('public_file_file', $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
    }

    private function getS3PrivateUrl(File $file, ?int $width = null, ?int $height = null, ?int $revisionCounter = null): ?string
    {
        $revision = FileHelper::getRevision($file, $revisionCounter);
        if (!$revision instanceof Revision) {
            return null;
        }

        if ($revision->getMime() === MimeType::SVG->value) {
            // SVGs don't need special versions
            $width = $height = null;
        }

        foreach ($revision->getStorageUrls() as $storageUrl) {
            if (
                $storageUrl->getWidth() === $width
                && $storageUrl->getHeight() === $height
            ) {
                $now = new \DateTimeImmutable();
                if (null === $storageUrl->getTimeout() || $storageUrl->getTimeout() <= $now) {
                    try {
                        return $this->generateUrls($revision, $width, $height, true)?->getUrl();
                    } catch (\Throwable) {
                        return null;
                    }
                }

                return $storageUrl->getUrl();
            }
        }

        return null;
    }

    /**
     * @throws MissingRevisionException
     */
    private function getS3PublicUrl(File $file, ?int $width, ?int $height, ?int $revisionCounter = null): ?string
    {
        if (null === $revisionCounter) {
            $revision = $file->getActiveRevision();
        } else {
            $revision = $this->revisionRepository->findOneBy(['file' => $file, 'counter' => $revisionCounter]);
        }
        if (null === $revision) {
            throw new MissingRevisionException();
        }
        $storageUrl = $this->urlRepository->findOneBy(['revision' => $revision, 'width' => $width, 'height' => $height]);

        if (null === $width && 440 === $height) {
            return $this->getS3PrivateUrl(file: $file, height: 440, revisionCounter: $revisionCounter);
        }

        return $storageUrl?->getPublicUrl();
    }

    /**
     * @throws MissingRevisionException
     */
    public function setPublicUrl(File $file, ?string $publicUrl, ?RevisionFileStorageUrl $storageUrl = null): ?RevisionFileStorageUrl
    {
        $revision = $file->getActiveRevision();
        if (!$revision instanceof Revision) {
            throw new MissingRevisionException();
        }

        /*
         * For now we only do this for S3 filesystems
         */
        if ($revision->getFile()?->getWorkspace()?->getFilesystem()?->getType() !== FilesystemType::S3->value) {
            return null;
        }
        if (!$storageUrl instanceof RevisionFileStorageUrl) {
            $this->entityManager->flush();
            $storageUrl = $this->urlRepository->findOneBy(['revision' => $revision, 'width' => null, 'height' => null]);
        }
        if (null === $storageUrl) {
            $storageUrl = (new RevisionFileStorageUrl())
                ->setRevision($revision);
            $this->entityManager->persist($storageUrl);
        }
        $storageUrl->setPublicUrl($publicUrl);

        return $storageUrl;
    }

    /**
     * @return array{string, string}
     */
    public function getTimestampAndHash(File $file): array
    {
        $now = new \DateTime();
        $now->setTime((int) $now->format('H'), 0);
        $timestamp = (string) $now->getTimestamp();
        $hash = sha1(sprintf('%s-%s-%s-%s', $file->getTitle(), $file->getWorkspace()?->getName(), $timestamp, $file->getFilenameSlug()));

        return [$timestamp, $hash];
    }

    public function validateTimestampAndHash(File $file, mixed $timestamp, mixed $hash): bool
    {
        if (!is_string($timestamp) || !is_string($hash) || (int) $timestamp < time() - 36000) {
            return false;
        }
        $check = sha1(sprintf('%s-%s-%s-%s', $file->getTitle(), $file->getWorkspace()?->getName(), $timestamp, $file->getFilenameSlug()));

        return $check === $hash;
    }
}
