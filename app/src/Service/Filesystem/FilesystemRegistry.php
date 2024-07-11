<?php

declare(strict_types=1);

namespace App\Service\Filesystem;

use App\Entity\Revision;
use App\Entity\Workspace;
use App\Enum\BucketType;
use App\Enum\FilesystemType;
use App\Exception\File\MissingWorkspaceException;
use App\Exception\Filesystem\FilesystemException;
use App\Exception\Filesystem\MissingConfigException;
use App\Exception\Filesystem\RegistryException;
use App\Repository\FileSystemRepository;
use AsyncAws\S3\S3Client;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\AsyncAwsS3\AsyncAwsS3Adapter;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Local\LocalFilesystemAdapter;

final readonly class FilesystemRegistry implements FilesystemRegistryInterface
{
    private FilesystemType $defaultFilesystemType;

    public function __construct(
        string $defaultFileSystem,
        private string $localStoragePath,
        private FileSystemRepository $fileSystemRepository,
        private EntityManagerInterface $entityManager,
        private S3Client $defaultS3Client,
        private ?string $defaultS3Type = null,
        private ?string $defaultS3BucketPrefix = null,
        private ?string $defaultS3Bucket = null,
        private ?string $defaultPrefix = null,
    ) {
        $this->defaultFilesystemType = FilesystemType::tryFrom($defaultFileSystem) ?? FilesystemType::LOCAL;
    }

    /**
     * @throws FilesystemException
     * @throws MissingWorkspaceException
     */
    public function getRevisionFilesystem(Revision $revision): FilesystemOperator
    {
        $workspace = $revision->getFile()?->getWorkspace();
        if (!$workspace instanceof Workspace) {
            throw new MissingWorkspaceException();
        }
        $filesystemConfig = $this->getRevisionFilesystemConfig($revision);

        return $this->getFilesystemForConfig($workspace, $filesystemConfig);
    }

    /**
     * @throws FilesystemException
     * @throws MissingWorkspaceException
     */
    public function getRevisionFilesystemConfig(Revision $revision): \App\Entity\FileSystem
    {
        $workspace = $revision->getFile()?->getWorkspace();
        if (!$workspace instanceof Workspace) {
            throw new MissingWorkspaceException();
        }
        $filesystemConfig = $revision->getFilesystem();
        if (!$filesystemConfig instanceof \App\Entity\FileSystem) {
            $filesystemConfig = $this->getWorkspaceFilesystemConfig($workspace);
            $revision->setFileSystem($filesystemConfig);
            $this->entityManager->flush();
        }

        return $filesystemConfig;
    }

    /**
     * @throws FilesystemException
     */
    public function getWorkspaceFilesystem(Workspace $workspace): FilesystemOperator
    {
        $workspaceFilesystemConfig = $this->getWorkspaceFilesystemConfig($workspace);

        return $this->getFilesystemForConfig($workspace, $workspaceFilesystemConfig);
    }

    private function getLocalFilesystemConfig(): \App\Entity\FileSystem
    {
        $filesystemConfig = $this->fileSystemRepository->findOneBy(['type' => FilesystemType::LOCAL->value]);
        if (null === $filesystemConfig) {
            $filesystemConfig = (new \App\Entity\FileSystem())
                ->setType(FilesystemType::LOCAL->value)
                ->setTitle('Local Filesystem')
                ->setConfig([]);
            $this->entityManager->persist($filesystemConfig);
        }

        return $filesystemConfig;
    }

    /**
     * @throws MissingConfigException
     * @throws RegistryException
     */
    private function getDefaultS3Config(): \App\Entity\FileSystem
    {
        $bucketType = BucketType::tryOrDefault($this->defaultS3Type);
        switch ($bucketType) {
            case BucketType::SINGLE:
                if (null === $this->defaultS3Bucket) {
                    throw new MissingConfigException('Bucket not configured');
                }

                return $this->returnBucketS3Config($bucketType, $this->defaultS3Bucket);
            case BucketType::YEAR:
                if (null === $this->defaultS3BucketPrefix) {
                    throw new MissingConfigException('Prefix not configured');
                }

                return $this->returnYearS3Config($bucketType, $this->defaultS3BucketPrefix);
            default:
        }
        throw new RegistryException('Unable to have a default filesystem with this configuration');
    }

    /**
     * @throws FilesystemException
     */
    private function getDefaultFilesystemConfig(): \App\Entity\FileSystem
    {
        return match ($this->defaultFilesystemType) {
            FilesystemType::LOCAL => $this->getLocalFilesystemConfig(),
            FilesystemType::S3 => $this->getDefaultS3Config(),
        };
    }

    /**
     * @throws MissingConfigException
     */
    private function getFilesystemForConfig(Workspace $workspace, \App\Entity\FileSystem $workspaceFilesystemConfig): Filesystem
    {
        return match ($workspaceFilesystemConfig->getType()) {
            FilesystemType::LOCAL->value => $this->getLocalFilesystem($workspace),
            FilesystemType::S3->value => $this->getS3Filesystem($this->defaultS3Client, $workspace, $workspaceFilesystemConfig),
            default => throw new MissingConfigException('Unknown filesystem type'),
        };
    }

    private function getLocalFilesystem(Workspace $workspace): Filesystem
    {
        $path = sprintf('%s/%s', $this->localStoragePath, $workspace->getSlug());

        $adapter = new LocalFilesystemAdapter($path);

        return new Filesystem($adapter);
    }

    /**
     * @throws MissingConfigException
     */
    private function getS3Filesystem(S3Client $s3Client, Workspace $workspace, \App\Entity\FileSystem $filesystemConfig): Filesystem
    {
        $config = $filesystemConfig->getConfig();
        $bucket = $filesystemConfig->getBucket() ?? (array_key_exists('Bucket', $config) && is_string($config['Bucket'])) ? $config['Bucket'] : null;
        if (!is_string($bucket)) {
            throw new MissingConfigException('Bucket not configured');
        }

        $prefix = implode('/', array_filter([$this->defaultPrefix, $workspace->getSlug()]));
        $adapter = new AsyncAwsS3Adapter($s3Client, $bucket, $prefix);

        return new Filesystem($adapter);
    }

    public function getS3Client(Workspace $workspace): ?S3Client
    {
        if ($workspace->getFilesystem()?->getType() === FilesystemType::S3->value) {
            return $this->defaultS3Client;
        }

        return null;
    }

    private function createS3Config(
        BucketType $bucketType,
        ?string $prefix = null,
        ?string $year = null,
        ?string $bucketName = null
    ): \App\Entity\FileSystem {
        $title = 'S3';
        if (BucketType::YEAR === $bucketType) {
            if (null === $year) {
                $year = date('Y');
            }

            $bucketYearPrefix = sprintf('%s-%s', $prefix, $year);
            $yearBucket = null;
            $bucketOutput = $this->defaultS3Client->listBuckets();
            $buckets = $bucketOutput->getBuckets();
            foreach ($buckets as $bucket) {
                if (is_string($bucket->getName()) && str_starts_with($bucket->getName(), $bucketYearPrefix)) {
                    $yearBucket = $bucket;
                }
            }

            if (null === $yearBucket) {
                $hash = substr(sha1((string) time()), 0, 7);
                $bucketName = sprintf('%s-%s-%s', $this->defaultS3BucketPrefix, $year, $hash);

                $this->defaultS3Client->createBucket(['Bucket' => $bucketName]);
            } else {
                $bucketName = $yearBucket->getName();
            }
            $title = 'S3 for '.$year;
        }

        $fileSystemConfig = (new \App\Entity\FileSystem())
            ->setType(FilesystemType::S3->value)
            ->setYear($year)
            ->setTitle($title)
            ->setConfig([
                'Bucket' => $bucketName,
            ])
            ->setBucket($bucketName);
        $this->entityManager->persist($fileSystemConfig);
        $this->entityManager->flush();

        return $fileSystemConfig;
    }

    /**
     * @throws FilesystemException
     */
    public function getWorkspaceFilesystemConfig(Workspace $workspace): \App\Entity\FileSystem
    {
        $workspaceFilesystemConfig = $workspace->getFilesystem();
        // Workspace doesn't have a config yet
        if (!$workspaceFilesystemConfig instanceof \App\Entity\FileSystem) {
            // Step 1: Check if workspace has files in local filesystem already (this should only happen on dev systems)
            $localFilesystem = $this->getLocalFilesystem($workspace);
            try {
                if ([] !== $localFilesystem->listContents('.')->toArray()) {
                    $workspaceFilesystemConfig = $this->getLocalFilesystemConfig();
                } else {
                    // Step 2: Get Default filesystem
                    $workspaceFilesystemConfig = $this->getDefaultFilesystemConfig();
                }
            } catch (\League\Flysystem\FilesystemException $e) {
                throw new FilesystemException($e->getMessage(), $e->getCode(), $e);
            }
            $workspace->setFilesystem($workspaceFilesystemConfig);
            $this->entityManager->flush();
        } elseif ($workspaceFilesystemConfig->getType() === FilesystemType::S3->value && null === $workspaceFilesystemConfig->getBucket()) {
            $config = $workspaceFilesystemConfig->getConfig();
            if (array_key_exists('Bucket', $config) && is_string($config['Bucket'])) {
                $workspaceFilesystemConfig->setBucket($config['Bucket']);
            }
        }

        return $workspaceFilesystemConfig;
    }

    private function returnBucketS3Config(BucketType $bucketType, string $bucket): \App\Entity\FileSystem
    {
        $filesystemConfig = $this->fileSystemRepository->findOneBy(
            [
                'type' => FilesystemType::S3->value,
                'bucket' => $bucket,
            ]
        );

        return $filesystemConfig ?? $this->createS3Config($bucketType, bucketName: $bucket);
    }

    /** @noinspection PhpSameParameterValueInspection */
    private function returnYearS3Config(BucketType $bucketType, string $prefix, ?string $year = null): \App\Entity\FileSystem
    {
        if (null === $year) {
            $year = date('Y');
        }
        $filesystemConfig = $this->fileSystemRepository->findOneBy(['type' => FilesystemType::S3->value, 'year' => $year]);

        return $filesystemConfig ?? $this->createS3Config($bucketType, prefix: $prefix, year: $year);
    }
}
