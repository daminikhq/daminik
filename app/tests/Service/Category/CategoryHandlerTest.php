<?php

declare(strict_types=1);

namespace App\Tests\Service\Category;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Repository\FileCategoryRepository;
use App\Service\Category\CategoryHandler;
use App\Service\DatabaseLogger\DatabaseLoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class CategoryHandlerTest extends TestCase
{
    private CategoryHandler $categoryHandler;

    public function setUp(): void
    {
        $this->categoryHandler = new CategoryHandler(
            categoryRepository: $this->createMock(CategoryRepository::class),
            fileCategoryRepository: $this->createMock(FileCategoryRepository::class),
            entityManager: $this->createMock(EntityManagerInterface::class),
            slugger: $this->createMock(SluggerInterface::class),
            dispatcher: $this->createMock(EventDispatcherInterface::class),
            databaseLogger: $this->createMock(DatabaseLoggerInterface::class)
        );
    }

    public function testDoesNotCreateLoop(): void
    {
        $category1 = new Category();
        $parent1 = (new Category())
            ->setParent($category1);
        $parent2 = (new Category())
            ->setParent($parent1);
        $category2 = new Category();
        $this->assertFalse($this->categoryHandler->doesNotCreateLoop($parent2, $category1));
        $this->assertFalse($this->categoryHandler->doesNotCreateLoop($parent1, $category1));
        $this->assertTrue($this->categoryHandler->doesNotCreateLoop($category2, $category1));
    }
}
