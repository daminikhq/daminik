<?php

declare(strict_types=1);

namespace App\Service\File;

use App\Entity\File;
use App\Entity\Revision;
use App\Entity\Workspace;
use App\Exception\File\MissingRevisionException;
use App\Exception\FileHandlerException;
use App\Service\File\Helper\FilePathHelper;
use App\Service\File\Helper\UrlHelperInterface;
use App\Service\Filesystem\FilesystemRegistryInterface;
use League\Flysystem\FilesystemException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

readonly class Resizer
{
    public function __construct(
        private FilesystemRegistryInterface $filesystemRegistry,
        private UrlHelperInterface $urlHelper,
        private string $tmp = '/tmp'
    ) {
    }

    /**
     * @throws FilesystemException
     * @throws FileHandlerException
     * @throws MissingRevisionException
     * @throws ProcessFailedException
     */
    public function generateWorkspaceIcon(File $file): void
    {
        $this->resize($file, 256, 256);
    }

    /**
     * @throws FileHandlerException
     * @throws FilesystemException
     * @throws MissingRevisionException
     * @throws ProcessFailedException
     */
    public function resize(File $file, ?int $width = null, ?int $height = null, ?Revision $revision = null): void
    {
        if (!$revision instanceof Revision) {
            $revision = $file->getActiveRevision();
        }

        if (!$revision instanceof Revision) {
            throw new MissingRevisionException();
        }

        if (null === $width && null === $height) {
            return;
        }
        $resizeTmpPath = sprintf('%s/%s', $this->tmp, uniqid('resize', true));
        if (!is_dir($resizeTmpPath) && !mkdir($resizeTmpPath, 0755, true) && !is_dir($resizeTmpPath)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $resizeTmpPath));
        }

        $tmpOriginalFilePath = sprintf('%s/%s', $resizeTmpPath, $file->getFilename());
        $tmpResizedPath = $this->getFilePath($resizeTmpPath, $file, $width, $height);

        $workspace = $file->getWorkspace();
        if (!$workspace instanceof Workspace) {
            throw new FileHandlerException('Unable to resize file: workspace not set');
        }
        $filesystem = $this->filesystemRegistry->getWorkspaceFilesystem($workspace);

        $filePath = $file->getFilepath();
        if (null === $filePath) {
            throw new FileHandlerException('Unable to resize file: file path not set');
        }
        file_put_contents($tmpOriginalFilePath, $filesystem->read($filePath));
        $command = match ($file->getMime()) {
            'image/gif' => ['convert', sprintf('%s[0]', $tmpOriginalFilePath), '-resize', sprintf('%sx%s', $width, $height), $tmpResizedPath],
            'image/svg+xml' => $this->getSvgCommand($width, $height, $tmpOriginalFilePath, $tmpResizedPath),
            'image/jpeg' => ['convert', $tmpOriginalFilePath, '-auto-orient', '-resize', sprintf('%sx%s', $width, $height), $tmpResizedPath],
            default => ['convert', $tmpOriginalFilePath, '-resize', sprintf('%sx%s', $width, $height), $tmpResizedPath],
        };
        $process = new Process($command);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $newFile = file_get_contents($tmpResizedPath);
        if (false === $newFile) {
            throw new FileHandlerException('Unable to resize file: temporary file not accessible');
        }
        $saveDirectory = $this->getFileDirectory($revision);
        $filePath = $this->getFilePath($saveDirectory, $file, $width, $height);
        $filesystem->write($filePath, $newFile);
        $this->urlHelper->generateUrls($revision, $width, $height);
    }

    /**
     * @return array<int, string|int>
     */
    private function getSvgCommand(?int $width, ?int $height, string $tmpOriginalFilePath, string $tmpResizedPath): array
    {
        $command = ['rsvg-convert'];
        if (null !== $width) {
            $command = array_merge($command, ['-w', $width]);
        }
        if (null !== $height) {
            $command = array_merge($command, ['-h', $height]);
        }

        return array_merge($command, [$tmpOriginalFilePath, '-o', $tmpResizedPath]);
    }

    private function getFileDirectory(?Revision $revision): string
    {
        if (!$revision instanceof Revision || !$revision->getFile() instanceof File || null === $revision->getFile()->getFilenameSlug()) {
            throw new \RuntimeException('Unable to get file directory');
        }
        $fileDirectory = FilePathHelper::getFilePath(null, $revision->getFile()->getFilenameSlug());

        return implode('/', array_filter([$fileDirectory, $revision->getCounter()]));
    }

    private function getFilePath(string $directoryName, File $file, ?int $width, ?int $height): string
    {
        return sprintf('%s/%s-%sx%s.png', $directoryName, $file->getFilenameSlug(), $width, $height);
    }
}
