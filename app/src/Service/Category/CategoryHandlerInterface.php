<?php

declare(strict_types=1);

namespace App\Service\Category;

use App\Dto\Category\Create;
use App\Dto\Category\Edit;
use App\Dto\Utility\SortFilterPaginateArguments;
use App\Entity\Category;
use App\Entity\File;
use App\Entity\User;
use App\Entity\Workspace;
use App\Exception\Category\CategoryException;
use App\Util\Paginator;

interface CategoryHandlerInterface
{
    /**
     * @return Category[]
     */
    public function getCategories(Workspace $workspace): array;

    public function createCategory(Create $create, Workspace $workspace, User $user, ?File $file = null): Category;

    /**
     * @param Category[] $newCategories
     */
    public function updateFileCategories(File $file, array $newCategories, User $user): File;

    /**
     * @return Category[]
     */
    public function getFileCategories(File $file): array;

    public function getFileCategory(File $file): ?Category;

    public function updateFileCategory(File $file, ?Category $category, User $user): File;

    public function filterAndPaginateCategories(Workspace $workspace, SortFilterPaginateArguments $sortFilterPaginateArguments, ?Category $parent = null): Paginator;

    public function getCategoryBySlug(string $slug, Workspace $workspace): ?Category;

    /**
     * @return Category[]
     */
    public function getCategoryChildren(Workspace $workspace, ?Category $parent = null): array;

    /**
     * @throws CategoryException
     */
    public function deleteCategory(Category $category, ?User $user = null): void;

    public function editCategory(Edit $edit, Category $category, ?User $user = null): Category;
}
