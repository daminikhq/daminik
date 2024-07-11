<?php

declare(strict_types=1);

namespace App\Tests\Service\File;

use App\Dto\File\Upload;
use App\Entity\File;
use App\Entity\Revision;
use App\Entity\User;
use App\Entity\Workspace;
use App\Exception\File\MissingFilenameSlugException;
use App\Exception\FileHandlerException;
use App\Repository\FileRepository;
use App\Repository\RevisionRepository;
use App\Service\Category\CategoryHandlerInterface;
use App\Service\Collection\CollectionHandlerInterface;
use App\Service\DatabaseLogger\DatabaseLoggerInterface;
use App\Service\File\FileHandler;
use App\Service\File\Helper\UrlHelperInterface;
use App\Service\Filesystem\FilesystemRegistryInterface;
use App\Service\Tag\TagHandlerInterface;
use App\Service\Workspace\CreatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

class FileHandlerUnitTest extends TestCase
{
    private Workspace $workspace;
    private MockObject&FilesystemOperator $fileSystem;
    private MockObject&FileRepository $fileRepository;
    private MockObject&FilesystemRegistryInterface $fileSystemRegistry;

    private FileHandler $fileHandler;

    public function setUp(): void
    {
        $this->workspace = new Workspace();
        $this->fileSystem = $this->createMock(FilesystemOperator::class);
        $this->fileSystemRegistry = $this->createMock(FilesystemRegistryInterface::class);
        $this->fileSystemRegistry->method('getWorkspaceFilesystem')->with($this->workspace)->willReturn($this->fileSystem);

        $this->fileRepository = $this->createMock(FileRepository::class);

        $this->fileHandler = (new FileHandler(
            filesystemRegistry: $this->fileSystemRegistry,
            slugger: new AsciiSlugger(),
            fileRepository: $this->fileRepository,
            revisionRepository: $this->createMock(RevisionRepository::class),
            entityManager: $this->createMock(EntityManagerInterface::class),
            databaseLogger: $this->createMock(DatabaseLoggerInterface::class),
            tagHandler: $this->createMock(TagHandlerInterface::class),
            categoryHandler: $this->createMock(CategoryHandlerInterface::class),
            urlHelper: $this->createMock(UrlHelperInterface::class),
            collectionHandler: $this->createMock(CollectionHandlerInterface::class),
            logger: $this->createMock(LoggerInterface::class),
            bus: $this->createMock(MessageBusInterface::class),
            workspaceCreator: $this->createMock(CreatorInterface::class),
        ));
    }

    /**
     * @throws FileHandlerException
     * @throws MissingFilenameSlugException
     */
    public function testSizeDoesExistWithoutRevision(): void
    {
        $file = (new File())
            ->setWorkspace($this->workspace)
            ->setFilepath('test/test/test.png')
            ->setFilenameSlug('test');

        $width = 100;
        $height = 100;
        $expectedFilePath = 'test/test/test-100x100.png';

        $this->fileSystem->method('has')->with($expectedFilePath)->willReturn(true);

        self::assertTrue($this->fileHandler->sizeExists($file, $width, $height));
    }

    /**
     * @throws FileHandlerException
     * @throws MissingFilenameSlugException
     */
    public function testSizeDoesExistWithRevision(): void
    {
        $revision = (new Revision())
            ->setFilepath('test/test/1/test.png')
            ->setCounter(1);

        $file = (new File())
            ->setWorkspace($this->workspace)
            ->setFilepath('test/test/test.png')
            ->setFilenameSlug('test')
            ->setActiveRevision($revision);

        $revision->setFile($file);

        $width = 100;
        $height = 100;
        $expectedFilePath = 'test/test/1/test-100x100.png';

        $this->fileSystem->method('has')->with($expectedFilePath)->willReturn(true);

        self::assertTrue($this->fileHandler->sizeExists($file, $width, $height));
    }

    /**
     * @throws FileHandlerException
     */
    public function testFileUpload(): void
    {
        $uploader = new User();
        $upload = new Upload($uploader, $this->workspace);
        $uploadedFile = new UploadedFile('tests/resources/upload-test', 'test-upload.jpg');

        /*
         * Check for duplicate filename
         */
        $this->fileRepository->expects(self::once())->method('findWithSlug');

        /*
         * Two times: once for the file and once for the meta data
         */
        $this->fileSystemRegistry->expects(self::once())->method('getWorkspaceFilesystem')->with($this->workspace)->willReturn($this->fileSystem);

        $file = $this->fileHandler->saveUploadedFile($upload, $uploadedFile);
        $this->assertSame($uploader, $file->getUploader());
        $this->assertSame($this->workspace, $file->getWorkspace());
        $this->assertSame('test-upload.jpg', $file->getFilename());

        /*
         * First 4 letters of the filename slug
         * Filename slug
         * Revision counter (1 for a new file)
         * Filename
         */
        $this->assertSame('test/test-upload/1/test-upload.jpg', $file->getActiveRevision()?->getFilepath());

        /*
         * SHA1 hash of the test file. Will be useful for duplicate detection
         */
        $this->assertSame('578101133b95fcf7ebd4402abd118ea99c8caa68', $file->getActiveRevision()->getSha1());
    }
}
