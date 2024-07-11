<?php

declare(strict_types=1);

namespace App\Service\File;

use App\Dto\File\Edit;
use App\Dto\File\Rename;
use App\Dto\File\Revision as RevisionDto;
use App\Dto\File\Upload;
use App\Entity\AssetCollection;
use App\Entity\Category;
use App\Entity\File;
use App\Entity\Revision;
use App\Entity\RevisionFileStorageUrl;
use App\Entity\User;
use App\Entity\Workspace;
use App\Enum\FilesystemType;
use App\Enum\FileType;
use App\Enum\MimeType;
use App\Enum\UserAction;
use App\Enum\WorkspaceStatus;
use App\Exception\File\FileException;
use App\Exception\File\MissingFilenameSlugException;
use App\Exception\File\MissingWorkspaceException;
use App\Exception\FileHandlerException;
use App\Message\CategoryCollectionCountUpdateMessage;
use App\Message\Filesize\UpdateUploadSizesMessage;
use App\Message\PostUpload\CreateThumbnailMessage;
use App\Message\PostUpload\CreateWorkspaceIconMessage;
use App\Message\PostUpload\ReadMetadataMessage;
use App\Repository\FileRepository;
use App\Repository\RevisionRepository;
use App\Service\Category\CategoryHandlerInterface;
use App\Service\Collection\CollectionHandlerInterface;
use App\Service\DatabaseLogger\DatabaseLoggerInterface;
use App\Service\File\Helper\FilePathHelper;
use App\Service\File\Helper\UrlHelperInterface;
use App\Service\Filesystem\FilesystemRegistryInterface;
use App\Service\Tag\TagHandlerInterface;
use App\Service\Workspace\CreatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Das Ding ist jetzt schon zu groß
 *
 * @todo Aufräumen, dringend
 */
readonly class FileHandler
{
    public function __construct(
        private FilesystemRegistryInterface $filesystemRegistry,
        private SluggerInterface $slugger,
        private FileRepository $fileRepository,
        private RevisionRepository $revisionRepository,
        private EntityManagerInterface $entityManager,
        private DatabaseLoggerInterface $databaseLogger,
        private TagHandlerInterface $tagHandler,
        private CategoryHandlerInterface $categoryHandler,
        private UrlHelperInterface $urlHelper,
        private CollectionHandlerInterface $collectionHandler,
        private LoggerInterface $logger,
        private MessageBusInterface $bus,
        private CreatorInterface $workspaceCreator,
        private string $tmp = '/tmp'
    ) {
    }

    /**
     * @throws FileHandlerException
     */
    public function renameFile(File $file, Rename $renameDto, User $user): File
    {
        if ($file->getPublicFilenameSlug() === $renameDto->getSlug()) {
            return $file;
        }
        $workspace = $file->getWorkspace();
        $mime = $file->getMime();
        if (!$workspace instanceof Workspace || null === $mime || null === $renameDto->getSlug()) {
            throw new FileHandlerException();
        }
        if ($file->isPublic()) {
            try {
                $this->removePublicFile($file);
            } catch (\Throwable $e) {
                throw new FileHandlerException($e->getMessage(), $e->getCode(), $e);
            }
        }
        $oldFilename = $file->getFilename();
        $newSlug = $this->getUniqueSlug($renameDto->getSlug(), $workspace);
        $extension = $file->getExtension() ?? MimeTypes::getDefault()->getExtensions($mime)[0] ?? null;
        $filename = implode('.', array_filter([$newSlug, $extension]));
        $file->setExtension($extension)
            ->setPublicFilenameSlug($newSlug)
            ->setFilename($filename)
            ->setUpdatedBy($user);
        $this->databaseLogger->log(UserAction::EDIT_FILE, $file, [
            'filename' => [
                'old' => $oldFilename,
                'new' => $file->getFilename(),
            ],
        ], $user);
        $this->entityManager->flush();
        if ($file->isPublic()) {
            try {
                $this->createPublicFile($file);
                $this->entityManager->flush();
            } catch (\Throwable $e) {
                throw new FileHandlerException($e->getMessage(), $e->getCode(), $e);
            }
        }

        return $file;
    }

    /**
     * @throws FileHandlerException
     * @throws MissingWorkspaceException
     */
    public function getMime(File $file): string
    {
        if (null !== $file->getMime()) {
            return $file->getMime();
        }

        if (null === $file->getFilepath()) {
            throw new FileHandlerException();
        }

        $filesystem = $this->getFilesystemForFile($file);

        try {
            return $filesystem->mimeType($file->getFilepath());
        } catch (FilesystemException $e) {
            throw new FileHandlerException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws MissingWorkspaceException
     */
    private function getFilesystemForFile(File $file, ?Revision $revision = null): FilesystemOperator
    {
        if (!$revision instanceof Revision) {
            $revision = $file->getActiveRevision();
        }
        if (!$revision instanceof Revision) {
            $workspace = $file->getWorkspace();
            if (!$workspace instanceof Workspace) {
                throw new MissingWorkspaceException();
            }

            return $this->filesystemRegistry->getWorkspaceFilesystem($workspace);
        }

        return $this->filesystemRegistry->getRevisionFilesystem($revision);
    }

    /**
     * @throws FilesystemException
     * @throws MissingFilenameSlugException
     * @throws MissingWorkspaceException
     */
    private function removePublicFile(File $file, ?RevisionFileStorageUrl $storageUrl = null): void
    {
        $workspace = $file->getWorkspace();
        if (!$workspace instanceof Workspace) {
            throw new MissingWorkspaceException();
        }
        if (null === $file->getFilenameSlug() || null === $file->getPublicFilenameSlug()) {
            throw new MissingFilenameSlugException();
        }
        $fileSystem = $this->getFilesystemForFile($file);
        $publicFilePath = FilePathHelper::getFilePath(filename: $file->getFilename(), filenameSlug: $file->getPublicFilenameSlug(), short: true);
        if ($fileSystem->has($publicFilePath)) {
            $fileSystem->delete($publicFilePath);
        } else {
            $longPublicFilePath = FilePathHelper::getFilePath(filename: $file->getFilename(), filenameSlug: $file->getFilenameSlug());
            if ($fileSystem->has($longPublicFilePath)) {
                $fileSystem->delete($longPublicFilePath);
            }
        }
        $this->urlHelper->setPublicUrl(file: $file, publicUrl: null, storageUrl: $storageUrl);
    }

    /**
     * @throws FileHandlerException
     */
    private function getUniqueSlug(string $originalFilename, Workspace $workspace): string
    {
        $finalFilenameSlug = $filenameSlug = $this->slugger->slug(strtolower($originalFilename))->toString();

        $filenameIsUnique = false;
        $filenameCount = 1;
        while (!$filenameIsUnique) {
            try {
                $checkFile = $this->fileRepository->findWithSlug($workspace, $finalFilenameSlug);
            } catch (NonUniqueResultException $e) {
                throw new FileHandlerException(message: $e->getMessage(), code: $e->getCode(), previous: $e);
            }
            if (!$checkFile instanceof File) {
                $filenameIsUnique = true;
            } else {
                $finalFilenameSlug = sprintf('%s-%s', $filenameSlug, $filenameCount);
                ++$filenameCount;
            }
        }

        return $finalFilenameSlug;
    }

    /**
     * @throws FileException
     * @throws FilesystemException
     * @throws MissingWorkspaceException
     */
    private function createPublicFile(File $file, ?RevisionFileStorageUrl $storageUrl = null): void
    {
        $workspace = $file->getWorkspace();
        if (!$workspace instanceof Workspace) {
            throw new MissingWorkspaceException();
        }
        if (null === $file->getFilenameSlug() || null === $file->getPublicFilenameSlug()) {
            throw new MissingFilenameSlugException();
        }
        /*
         * For now we only do this for S3 filesystems
         */
        if ($workspace->getFilesystem()?->getType() !== FilesystemType::S3->value) {
            return;
        }

        if (true !== $file->isPublic() || WorkspaceStatus::BLOCKED->value === $workspace->getStatus()) {
            return;
        }

        $fileSystem = $this->getFilesystemForFile($file);
        $filePath = $file->getActiveRevision()?->getFilepath();
        if (null === $filePath) {
            $filePath = FilePathHelper::getFilePath(filename: $file->getFilename(), filenameSlug: $file->getFilenameSlug(), revisionCounter: $file->getActiveRevision()?->getCounter());
        }

        $publicFilePath = FilePathHelper::getFilePath(filename: $file->getFilename(), filenameSlug: $file->getPublicFilenameSlug(), short: true);
        $fileSystem->copy(source: $filePath, destination: $publicFilePath, config: [
            'visibility' => 'public',
        ]);
        $this->urlHelper->setPublicUrl(file: $file, publicUrl: $fileSystem->publicUrl($publicFilePath), storageUrl: $storageUrl);
    }

    /**
     * @throws FileHandlerException
     * @throws MissingWorkspaceException
     * @throws ExceptionInterface
     */
    public function deleteFile(File $file, bool $softDelete = true): void
    {
        if (null === $file->getFilepath()) {
            throw new FileHandlerException();
        }

        if ($softDelete) {
            $file->setDeletedAt(new \DateTime());

            $this->databaseLogger->log(UserAction::DELETE_FILE, $file);
            $this->bus->dispatch(new CategoryCollectionCountUpdateMessage($file->getId()));

            return;
        }

        $filesystem = $this->getFilesystemForFile($file);
        try {
            $filesystem->delete($file->getFilepath());
        } catch (FilesystemException $e) {
            throw new FileHandlerException($e->getMessage(), $e->getCode(), $e);
        }
        $this->entityManager->remove($file);
    }

    /**
     * @throws FileHandlerException
     * @throws MissingFilenameSlugException
     * @throws MissingWorkspaceException
     */
    public function getResizedContent(File $file, ?int $width, ?int $height, bool $returnOriginalIfMissing = true, ?Revision $revision = null): string
    {
        if (!$revision instanceof Revision) {
            $revision = $file->getActiveRevision();
        }
        if (!$this->sizeExists($file, $width, $height, $revision)) {
            if ($returnOriginalIfMissing) {
                return $this->getFileContent($file);
            }
            throw new FileHandlerException('Size not found');
        }
        if (!$file->getWorkspace() instanceof Workspace) {
            throw new MissingWorkspaceException();
        }
        if (null === $file->getFilenameSlug()) {
            throw new MissingFilenameSlugException();
        }
        $filesystem = $this->filesystemRegistry->getWorkspaceFilesystem($file->getWorkspace());
        try {
            return $filesystem->read(FilePathHelper::getFilePathWithSize($file->getFilenameSlug(), $width, $height, $revision?->getCounter()));
        } catch (FilesystemException $e) {
            throw new FileHandlerException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws FileHandlerException
     * @throws MissingFilenameSlugException
     */
    public function sizeExists(File $file, ?int $width, ?int $height, ?Revision $revision = null): bool
    {
        if (null === $width && null === $height) {
            return false;
        }

        if (!$file->getWorkspace() instanceof Workspace) {
            throw new FileHandlerException('Workspace is missing');
        }

        $filesystem = $this->filesystemRegistry->getWorkspaceFilesystem($file->getWorkspace());

        if (null === $file->getFilenameSlug()) {
            throw new MissingFilenameSlugException('Filepath is missing');
        }
        if (!$revision instanceof Revision) {
            $revision = $file->getActiveRevision();
        }

        try {
            return $filesystem->has(FilePathHelper::getFilePathWithSize($file->getFilenameSlug(), $width, $height, $revision?->getCounter()));
        } catch (FilesystemException $e) {
            throw new FileHandlerException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws FileHandlerException
     * @throws MissingWorkspaceException
     */
    public function getFileContent(File $file): string
    {
        if (null === $file->getFilepath()) {
            throw new FileHandlerException();
        }

        $filesystem = $this->getFilesystemForFile($file);

        try {
            return $filesystem->read($file->getFilepath());
        } catch (FilesystemException $e) {
            throw new FileHandlerException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws FileHandlerException
     */
    public function editFile(File $file, Edit $edit, User $user): void
    {
        /** @var array<string, array<string, mixed>> $changedValues */
        $changedValues = [];
        if ($edit->getTitle() !== $file->getTitle()) {
            $changedValues['title'] = ['old' => $file->getTitle(), 'new' => $edit->getTitle()];
        }
        if ($edit->getDescription() !== $file->getDescription()) {
            $changedValues['description'] = ['old' => $file->getDescription(), 'new' => $edit->getDescription()];
        }
        if ($edit->isPublic() !== $file->isPublic()) {
            $changedValues['public'] = ['old' => $file->isPublic(), 'new' => $edit->isPublic()];
        }

        $file->setTitle($edit->getTitle())
            ->setDescription($edit->getDescription())
            ->setPublic($edit->isPublic());

        $oldTagString = $this->tagHandler->getTagString($file);
        if ($edit->getTags() !== $oldTagString) {
            $changedValues['tags'] = ['old' => $oldTagString, 'new' => $edit->getTags()];
            $this->tagHandler->updateTags(file: $file, tagString: (string) $edit->getTags(), user: $user);
        }

        $this->categoryHandler->updateFileCategory(file: $file, category: $edit->getCategory(), user: $user);
        $this->collectionHandler->updateFileCollections(file: $file, collections: $edit->getAssetCollections(), user: $user);

        $this->databaseLogger->log(UserAction::EDIT_FILE, $file, $changedValues, $user);

        $this->togglePublicFile($file);
        $file->setUpdatedBy($user);
    }

    /**
     * @throws FileHandlerException
     */
    private function togglePublicFile(File $file, ?RevisionFileStorageUrl $storageUrl = null, bool $refreshPublicFile = false): void
    {
        if ($file->getWorkspace()?->getFilesystem()?->getType() === FilesystemType::S3->value) {
            try {
                if ($file->isPublic() && $file->getWorkspace()->getStatus() !== WorkspaceStatus::BLOCKED->value) {
                    if ($refreshPublicFile) {
                        $this->removePublicFile($file, $storageUrl);
                    }
                    $this->createPublicFile($file, $storageUrl);
                } else {
                    $this->removePublicFile($file, $storageUrl);
                }
            } catch (\Throwable $e) {
                throw new FileHandlerException($e->getMessage(), $e->getCode(), $e);
            }
        }
    }

    /**
     * @throws FileHandlerException
     */
    public function setRevisionById(File $file, string $revisionId, ?User $user = null): void
    {
        $revision = $this->revisionRepository->find((int) $revisionId);
        if (null === $revision || $revision->getFile() !== $file) {
            return;
        }
        $file->setActiveRevision($revision);
        if ($user instanceof User) {
            $file->setUpdatedBy($user);
        }
        $this->entityManager->flush();
        if ($file->isPublic()) {
            $this->togglePublicFile(file: $file, refreshPublicFile: true);
        }
    }

    /**
     * @throws FileHandlerException
     * @throws MissingWorkspaceException
     */
    public function provideLocalFile(File $file, ?Revision $revision = null): ?string
    {
        $filesystem = $this->getFilesystemForFile($file, $revision);

        if (!$revision instanceof Revision) {
            $revision = $file->getActiveRevision();
        }
        if (!$revision instanceof Revision) {
            throw new FileHandlerException('File not found');
        }

        $tmpFileName = $this->getTmpFileName($file, $revision);
        $filePath = $revision->getFilepath();
        if (null === $filePath) {
            throw new FileHandlerException('Unable to resize file: file path not set');
        }
        try {
            file_put_contents($tmpFileName, $filesystem->read($filePath));
        } catch (FilesystemException $e) {
            throw new FileHandlerException(message: 'Unable to write file: '.$e->getMessage(), code: $e->getCode(), previous: $e);
        }

        return $tmpFileName;
    }

    /**
     * @throws FileHandlerException
     */
    private function getTmpFileName(File $file, ?Revision $revision): string
    {
        if (!$revision instanceof Revision) {
            $revision = $file->getActiveRevision();
        }
        if (!$revision instanceof Revision) {
            throw new FileHandlerException('File not found');
        }

        $tmpPath = sprintf('%s/%s', $this->tmp, $revision->getId());
        if (!is_dir($tmpPath) && !mkdir($tmpPath, 0755, true) && !is_dir($tmpPath)) {
            throw new FileHandlerException(sprintf('Directory "%s" was not created', $tmpPath));
        }

        return sprintf('%s/%s', $tmpPath, $file->getFilename());
    }

    /**
     * @throws FileHandlerException
     */
    public function removeLocalFile(File $file, ?Revision $revision = null): void
    {
        $tmpFileName = $this->getTmpFileName($file, $revision);
        if (file_exists($tmpFileName)) {
            unlink($tmpFileName);
        }
    }

    /**
     * @throws FileHandlerException
     */
    public function saveUserAvatar(User $user, UploadedFile $uploadedFile): File
    {
        $globalWorkspace = $this->getGlobalWorkspace();
        $avatar = $this->fileRepository->findOneBy(['workspace' => $globalWorkspace, 'uploader' => $user, 'type' => FileType::AVATAR->value]);
        if (!$avatar instanceof File) {
            $upload = new Upload(uploader: $user, workspace: $globalWorkspace);

            return $this->saveUploadedFile(upload: $upload, uploadedFile: $uploadedFile, fileType: FileType::AVATAR);
        }
        $revisionDto = new RevisionDto(
            $user,
            $avatar
        );
        try {
            $this->saveRevision($revisionDto, $uploadedFile);
        } catch (\Throwable $e) {
            throw new FileHandlerException($e->getMessage(), $e->getCode(), $e);
        }

        return $avatar;
    }

    private function getGlobalWorkspace(): Workspace
    {
        return $this->workspaceCreator->getGlobalWorkspace();
    }

    /**
     * @throws FileHandlerException
     */
    public function saveUploadedFile(Upload $upload, UploadedFile $uploadedFile, FileType $fileType = FileType::ASSET): File
    {
        /*
         * Step 0: Check the allowed mime types
         */
        $fileMimeType = $uploadedFile->getMimeType();
        $this->checkValidMimeType($fileMimeType);

        /*
         * Step 1: Get the filename and make sure it is unique
         */
        $workspace = $upload->getWorkspace();
        $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $finalFilenameSlug = $this->getUniqueSlug($originalFilename, $workspace);

        $extension = $uploadedFile->guessExtension();
        $filename = $finalFilenameSlug.'.'.$extension;

        /*
         * Step 2: Create the entities
         */
        $file = (new File())
            ->setWorkspace($workspace)
            ->setUploader($upload->getUploader())
            ->setFilename($filename)
            ->setFilenameSlug($finalFilenameSlug)
            ->setPublicFilenameSlug($finalFilenameSlug)
            ->setExtension($extension)
            ->setPublic(true)
            ->setType($fileType->value)
            ->setMime($uploadedFile->getMimeType());
        $this->entityManager->persist($file);

        /*
         * Step 3: Save uploaded file as new (first) revision
         * This is also where the file gets put into the storage
         */
        $revisionDto = new RevisionDto(
            $upload->getUploader(),
            $file
        );
        try {
            $this->saveRevision($revisionDto, $uploadedFile, $upload->getUploader());
        } catch (\Throwable $e) {
            throw new FileHandlerException($e->getMessage(), $e->getCode(), $e);
        }

        $this->databaseLogger->log(UserAction::UPLOAD_FILE, $file);
        $this->togglePublicFile($file);

        /*
         * Steo 4: Map context
         */
        if ('home' !== $upload->getContext()) {
            $this->handleUploadContext($file, $upload);
        }

        return $file;
    }

    /**
     * @throws FileHandlerException
     */
    private function checkValidMimeType(?string $fileMimeType): void
    {
        if (null === $fileMimeType) {
            throw new FileHandlerException();
        }
        $mimeType = MimeType::tryFrom($fileMimeType);
        if (!$mimeType instanceof MimeType) {
            throw new FileHandlerException();
        }
        if (!in_array(needle: $mimeType, haystack: MimeType::validCases(), strict: true)) {
            throw new FileHandlerException();
        }
    }

    /**
     * @throws FileHandlerException
     * @throws FilesystemException
     * @throws MissingWorkspaceException
     */
    public function saveRevision(RevisionDto $upload, UploadedFile $uploadedFile, ?User $user = null): Revision
    {
        $file = $upload->getFile();
        $filenameSlug = $file->getFilenameSlug();
        $workspace = $file->getWorkspace();
        if (null === $filenameSlug || !$workspace instanceof Workspace) {
            $this->logger->error(__METHOD__, [
                'filenameSlug' => $filenameSlug,
                'workspace' => $workspace,
            ]);
            throw new FileHandlerException();
        }
        $filesystem = $this->getFilesystemForFile($file);
        $filesystemConfig = $this->filesystemRegistry->getWorkspaceFilesystemConfig($workspace);
        $revision = 1;
        $directory = FilePathHelper::getFilePath(null, $filenameSlug);
        while ($filesystem->directoryExists(sprintf('%s/%s', $directory, $revision))) {
            ++$revision;
        }
        $filepath = FilePathHelper::getFilePath($file->getFilename(), $filenameSlug, $revision);
        $filesystem->write($filepath, $uploadedFile->getContent());

        $sha1 = sha1_file($uploadedFile->getRealPath());
        if (false === $sha1) {
            $sha1 = null;
        }

        $revisionEntity = (new Revision())
            ->setFile($file)
            ->setFilepath($filepath)
            ->setUploader($upload->getUploader())
            ->setMime($uploadedFile->getMimeType())
            ->setSha1($sha1)
            ->setCounter($revision)
            ->setFileSize($uploadedFile->getSize())
            ->setFileSystem($filesystemConfig);
        $file->setActiveRevision($revisionEntity);
        if ($user instanceof User) {
            $file->setUpdatedBy($user);
        }
        $this->entityManager->persist($revisionEntity);

        $revisionStorageUrl = $this->urlHelper->generateUrls($revisionEntity);

        $this->togglePublicFile(file: $file, storageUrl: $revisionStorageUrl, refreshPublicFile: true);

        return $revisionEntity;
    }

    private function handleUploadContext(File $file, Upload $upload): void
    {
        $contextArray = explode('-', $upload->getContext(), 2);
        if (count($contextArray) < 2) {
            return;
        }
        switch ($contextArray[0]) {
            case 'category':
                $category = $this->categoryHandler->getCategoryBySlug($contextArray[1], $upload->getWorkspace());
                if ($category instanceof Category) {
                    $this->categoryHandler->updateFileCategory($file, $category, $upload->getUploader());
                }

                return;
            case 'collection':
                $collection = $this->collectionHandler->getCollectionBySlug($contextArray[1], $upload->getWorkspace());
                if ($collection instanceof AssetCollection) {
                    $this->collectionHandler->updateFileCollections($file, [$collection], $upload->getUploader());
                }

                return;
        }
    }

    /**
     * @throws FileHandlerException
     * @throws ExceptionInterface
     */
    public function saveUploadedFileAsIcon(Upload $upload, UploadedFile $uploadedFile, ?Workspace $workspace = null): File
    {
        if (!$workspace instanceof Workspace) {
            $workspace = $upload->getWorkspace();
        }
        $file = $this->saveUploadedFile(upload: $upload, uploadedFile: $uploadedFile, fileType: FileType::ICON);

        $workspace->setIconFile($file);

        if (is_int($workspace->getId())) {
            $this->bus->dispatch(new CreateWorkspaceIconMessage($workspace->getId()));
        }
        if (is_int($file->getId())) {
            if (is_int($file->getActiveRevision()?->getId())) {
                $this->bus->dispatch(new UpdateUploadSizesMessage($file->getId(), $file->getActiveRevision()->getId()));
            }
            $this->bus->dispatch(new CreateThumbnailMessage($file->getId(), $file->getActiveRevision()?->getId()));
            $this->bus->dispatch(new ReadMetadataMessage($file->getId(), $file->getActiveRevision()?->getId()));
        }

        return $file;
    }

    /**
     * @throws FileHandlerException
     */
    public function checkVisibility(File $file): void
    {
        $this->togglePublicFile(file: $file, refreshPublicFile: true);
    }
}
