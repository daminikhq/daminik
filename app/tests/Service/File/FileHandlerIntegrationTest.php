<?php

declare(strict_types=1);

namespace App\Tests\Service\File;

use App\Dto\File\Edit;
use App\Dto\File\Rename;
use App\Dto\File\Revision as RevisionDto;
use App\Entity\Category;
use App\Entity\File;
use App\Service\File\FileHandler;
use App\Tests\Traits\HasTestEntityFunctionsTrait;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileHandlerIntegrationTest extends KernelTestCase
{
    use HasTestEntityFunctionsTrait;

    private ContainerInterface $container;
    private ?EntityManagerInterface $entityManager = null;

    /**
     * @throws \Exception
     */
    public function setUp(): void
    {
        self::bootKernel();
        $this->container = static::getContainer();
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->container->get(EntityManagerInterface::class);
        $this->entityManager = $entityManager;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager?->close();
        $this->entityManager = null;
    }

    /**
     * @throws \Exception
     * @throws FilesystemException
     */
    public function testRenameFile(): void
    {
        $user = $this->createTestUser($this->entityManager);
        $file = $this->createTestFile($this->entityManager, 'private', 'public', 'jpg');

        $revisionDto = new RevisionDto(
            $user,
            $file
        );
        $uploadedFile = new UploadedFile('tests/resources/upload-test', 'test-upload.jpg');

        $rename = (new Rename())->setSlug('Düster');

        $this->entityManager?->flush();

        /** @var FileHandler $fileHandler */
        $fileHandler = $this->container->get(FileHandler::class);
        $fileHandler->saveRevision($revisionDto, $uploadedFile, $user);
        $this->entityManager?->flush();

        $renamedFile = $fileHandler->renameFile($file, $rename, $user);

        $this->entityManager?->flush();

        self::assertSame($file, $renamedFile);
        self::assertSame('duester', $renamedFile->getPublicFilenameSlug());
        self::assertSame('duester.jpg', $renamedFile->getFilename());
    }

    /**
     * @throws \Exception
     */
    public function testEditFile(): void
    {
        $category = $this->createTestCategory();
        $file = $this->createBasicTestFile();
        $edit = (new Edit())
            ->setCategory($category);
        $user = $this->createTestUser($this->entityManager);

        /** @var FileHandler $fileHandler */
        $fileHandler = $this->container->get(FileHandler::class);
        $fileHandler->editFile($file, $edit, $user);
        $this->entityManager?->flush();
        $this->assertSame('test.png', $file->getFilepath());
        $this->assertNotCount(0, $file->getFileCategories());
    }

    /**
     * @throws \Exception
     */
    private function createBasicTestFile(): File
    {
        return $this->createTestFile(
            entityManager: $this->entityManager
        );
    }

    /**
     * @throws \Exception
     */
    private function createTestCategory(): Category
    {
        $workspace = $this->createTestWorkspace($this->entityManager);
        $user = $this->createTestUser($this->entityManager);
        $category = (new Category())
            ->setTitle('Test Category')
            ->setSlug('test-category')
            ->setWorkspace($workspace)
            ->setCreator($user);
        $this->entityManager?->persist($category);

        return $category;
    }
}
