<?php

declare(strict_types=1);

namespace App\Tests\Service\File\Filter;

use App\Dto\Utility\SortFilterPaginateArguments;
use App\Entity\File;
use App\Entity\User;
use App\Entity\Workspace;
use App\Enum\MimeType;
use App\Enum\SortParam;
use App\Service\File\FilePaginationHandler;
use App\Service\File\Filter\ChoiceFilter;
use App\Service\File\Filter\FavoriteFilter;
use App\Service\File\Filter\MimeTypeFilter;
use App\Service\File\Helper\FilterHelper;
use App\Service\File\UserMetaDataHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FavoriteFilterIntegrationTest extends KernelTestCase
{
    private ContainerInterface $container;
    private ?EntityManagerInterface $entityManager = null;
    private ?User $user = null;
    private ?Workspace $workspace = null;
    private int $testUserCount = 0;

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

    /**
     * @throws \Exception
     */
    public function testFavoritesAreSetAndQueried(): void
    {
        $file = $this->createTestFile();
        $file2 = $this->createTestFile('-2');
        $user = $this->createTestUser();
        $workspace = $this->createTestWorkspace();

        /** @var UserMetaDataHandler $metaDataHandler */
        $metaDataHandler = $this->container->get(UserMetaDataHandler::class);
        $fileUserData = $metaDataHandler->markAsFavorite($file, $user);
        $fileUserData2 = $metaDataHandler->markAsFavorite($file2, $user);

        self::assertTrue($fileUserData);
        self::assertTrue($fileUserData2);
        $this->entityManager?->flush();

        $filters = [
            new ChoiceFilter(['deletedAt', 'isNull']),
            new MimeTypeFilter(FilterHelper::getMimeTypeFilters()),
            new FavoriteFilter($user),
        ];

        /** @var FilePaginationHandler $filePaginationHandler */
        $filePaginationHandler = $this->container->get(FilePaginationHandler::class);

        $files = $filePaginationHandler->filterAndPaginateFiles(
            workspace: $workspace,
            sortFilterPaginateArguments: new SortFilterPaginateArguments(
                sort: SortParam::UPLOADED_DESC,
                page: 1,
                limit: 10,
            ),
            additionalFilters: $filters
        );
        self::assertSame(2, $files->getTotal());
        $items = $files->getItems();
        self::assertContains($file, $items);
        self::assertContains($file2, $items);

        $fileUserData2 = $metaDataHandler->markAsFavorite($file2, $user);
        self::assertFalse($fileUserData2);
        $this->entityManager?->flush();

        $files = $filePaginationHandler->filterAndPaginateFiles(
            workspace: $workspace,
            sortFilterPaginateArguments: new SortFilterPaginateArguments(
                sort: SortParam::UPLOADED_DESC,
                page: 1,
                limit: 10,
            ),
            additionalFilters: $filters
        );
        self::assertSame(1, $files->getTotal());
        $items = $files->getItems();
        self::assertContains($file, $items);
        self::assertNotContains($file2, $items);
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
    private function createTestFile(?string $suffix = null): File
    {
        $user = $this->createTestUser();

        $workspace = $this->createTestWorkspace();

        $filenameSlug = 'favorite';
        if (null !== $suffix) {
            $filenameSlug .= $suffix;
        }
        $filePath = $filenameSlug.'.png';

        $file = (new File())
            ->setWorkspace($workspace)
            ->setFilepath($filePath)
            ->setFilenameSlug($filenameSlug)
            ->setMime(MimeType::PNG->value)
            ->setUploader($user);
        $this->entityManager?->persist($file);

        return $file;
    }

    /**
     * @throws \Exception
     */
    private function createTestUser(): User
    {
        if ($this->user instanceof User) {
            return $this->user;
        }
        $user = (new User())
            ->setEmail($this->testUserCount.'-favfilter-'.sha1(random_int(0, 1000).time()).'@example.com')
            ->setPassword('favorite');
        $this->entityManager?->persist($user);
        $this->user = $user;
        ++$this->testUserCount;

        return $user;
    }

    /**
     * @throws \Exception
     */
    private function createTestWorkspace(): Workspace
    {
        if ($this->workspace instanceof Workspace) {
            return $this->workspace;
        }

        $user = $this->createTestUser();

        $workspace = (new Workspace())
            ->setName('Favorite')
            ->setSlug('favorite')
            ->setCreatedBy($user);
        $this->entityManager?->persist($workspace);
        $this->workspace = $workspace;

        return $workspace;
    }
}
