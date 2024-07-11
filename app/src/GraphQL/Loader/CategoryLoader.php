<?php

declare(strict_types=1);

namespace App\GraphQL\Loader;

use App\Dto\Api\Category;
use App\Dto\Api\CategoryCollection;
use App\Entity\Workspace;
use App\Repository\CategoryRepository;
use App\Service\Workspace\WorkspaceIdentifier;
use App\Util\Mapper\Api\CategoryMapper;

readonly class CategoryLoader
{
    public function __construct(
        private WorkspaceIdentifier $workspaceIdentifier,
        private CategoryRepository $categoryRepository
    ) {
    }

    public function loadCategoryBySlug(string $slug): ?Category
    {
        $workspace = $this->workspaceIdentifier->getWorkspace();
        if (!$workspace instanceof Workspace) {
            return null;
        }
        $category = $this->categoryRepository->findOneBy(['workspace' => $workspace, 'slug' => $slug]);
        if (null === $category) {
            return null;
        }

        return CategoryMapper::mapEntityToDto($category);
    }

    public function loadCategories(): ?CategoryCollection
    {
        $workspace = $this->workspaceIdentifier->getWorkspace();
        if (!$workspace instanceof Workspace) {
            return null;
        }
        $categories = $this->categoryRepository->findBy(['workspace' => $workspace], ['slug' => 'DESC']);
        $collection = new CategoryCollection();
        foreach ($categories as $category) {
            $collection->addCategory(CategoryMapper::mapEntityToDto($category));
        }

        return $collection;
    }
}
