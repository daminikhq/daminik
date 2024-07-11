<?php

namespace App\Tests\Service\Category;

use App\Dto\Category\Create;
use App\Service\Category\CategoryHandler;
use App\Tests\Traits\HasTestEntityFunctionsTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CategoryHandlerIntegrationTest extends KernelTestCase
{
    use HasTestEntityFunctionsTrait;

    private ?EntityManagerInterface $entityManager = null;
    private CategoryHandler $categoryHandler;

    /**
     * @throws \Exception
     */
    public function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $this->entityManager = $entityManager;

        /** @var CategoryHandler $categoryHandler */
        $categoryHandler = $container->get(CategoryHandler::class);
        $this->categoryHandler = $categoryHandler;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager?->close();
        $this->entityManager = null;
    }

    /**
     * @throws \Exception
     */
    public function testCategoryWithoutTitle(): void
    {
        $workspace = $this->createTestWorkspace($this->entityManager);
        $user = $this->createTestUser($this->entityManager);

        /*
         * This can only happen in the applicaion when the Symfony validator fails.
         */
        $emptyCreate = new Create();
        $this->expectException(\RuntimeException::class);
        $this->categoryHandler->createCategory(create: $emptyCreate, workspace: $workspace, user: $user);
    }

    /**
     * @throws \Exception
     */
    public function testCreateCategory(): void
    {
        $workspace = $this->createTestWorkspace($this->entityManager);
        $user = $this->createTestUser($this->entityManager);
        $file = $this->createTestFile($this->entityManager);

        $testCreate = (new Create())->setTitle('Test');
        $testCategory = $this->categoryHandler->createCategory(create: $testCreate, workspace: $workspace, user: $user);
        $this->assertSame('Test', $testCategory->getTitle());
        $this->assertSame($workspace, $testCategory->getWorkspace());
        $this->assertSame($user, $testCategory->getCreator());
        $this->assertSame('test', $testCategory->getSlug());

        /*
         * Here we test a couple of things:
         * No duplicate slugs
         * Adding the category to a file
         * Requesting the (main) category of a file
         */
        $testFileCreate = (new Create())->setTitle('Test');
        $testFileCategory = $this->categoryHandler->createCategory(create: $testFileCreate, workspace: $workspace, user: $user, file: $file);
        $this->assertSame('Test', $testFileCategory->getTitle());
        $this->assertSame($workspace, $testFileCategory->getWorkspace());
        $this->assertSame($user, $testFileCategory->getCreator());
        $this->assertSame('test-1', $testFileCategory->getSlug());
        $this->assertSame($testFileCategory, $this->categoryHandler->getFileCategory($file));
    }

    /**
     * @throws \Exception
     */
    public function testFileCategories(): void
    {
        $workspace = $this->createTestWorkspace($this->entityManager);
        $user = $this->createTestUser($this->entityManager);
        $file = $this->createTestFile($this->entityManager);

        $createCategory1 = (new Create())->setTitle('File Category 1');
        $testCategory1 = $this->categoryHandler->createCategory(create: $createCategory1, workspace: $workspace, user: $user);
        $createCategory2 = (new Create())->setTitle('File Category 2');
        $testCategory2 = $this->categoryHandler->createCategory(create: $createCategory2, workspace: $workspace, user: $user);

        $this->assertNull($this->categoryHandler->getFileCategory($file));
        $this->assertCount(0, $this->categoryHandler->getFileCategories($file));

        $file = $this->categoryHandler->updateFileCategory($file, $testCategory1, $user);
        $this->assertSame($testCategory1, $this->categoryHandler->getFileCategory($file));
        $this->assertCount(1, $this->categoryHandler->getFileCategories($file));

        $file = $this->categoryHandler->updateFileCategory($file, $testCategory2, $user);
        $this->assertSame($testCategory2, $this->categoryHandler->getFileCategory($file));
        $this->assertCount(1, $this->categoryHandler->getFileCategories($file));
    }
}
