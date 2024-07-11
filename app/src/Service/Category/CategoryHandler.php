<?php

declare(strict_types=1);

namespace App\Service\Category;

use App\Dto\Category\Create;
use App\Dto\Category\Edit;
use App\Dto\Utility\SortFilterPaginateArguments;
use App\Entity\Category;
use App\Entity\File;
use App\Entity\FileCategory;
use App\Entity\User;
use App\Entity\Workspace;
use App\Enum\SortParam;
use App\Enum\UserAction;
use App\Event\Category\CategoryEditedEvent;
use App\Event\Category\FileAddedToCategoryEvent;
use App\Event\Category\FileRemovedFromCategoryEvent;
use App\Exception\Category\CategoryException;
use App\Repository\CategoryRepository;
use App\Repository\FileCategoryRepository;
use App\Service\DatabaseLogger\DatabaseLoggerInterface;
use App\Util\Paginator;
use App\Util\Paginator\PaginatorException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

readonly class CategoryHandler implements CategoryHandlerInterface
{
    public function __construct(
        private CategoryRepository $categoryRepository,
        private FileCategoryRepository $fileCategoryRepository,
        private EntityManagerInterface $entityManager,
        private SluggerInterface $slugger,
        private EventDispatcherInterface $dispatcher,
        private DatabaseLoggerInterface $databaseLogger,
    ) {
    }

    /**
     * @return Category[]
     */
    public function getCategories(Workspace $workspace): array
    {
        return $this->categoryRepository->findBy(['workspace' => $workspace, 'parent' => null], ['slug' => 'DESC']);
    }

    public function createCategory(Create $create, Workspace $workspace, User $user, ?File $file = null): Category
    {
        $title = $create->getTitle();
        if (null === $title) {
            throw new \RuntimeException();
        }
        $slug = $this->getUniqueSlug($title, $workspace);

        $category = (new Category())
            ->setCreator($user)
            ->setWorkspace($workspace)
            ->setTitle($title)
            ->setSlug($slug)
            ->setParent($create->getParent());
        $this->entityManager->persist($category);

        $this->databaseLogger->log(userAction: UserAction::CREATE_CATEGORY, object: $category, actingUser: $user);

        if ($file instanceof File) {
            $this->updateFileCategory($file, $category, $user);
        }
        $this->entityManager->flush();

        return $category;
    }

    public function editCategory(Edit $edit, Category $category, ?User $user = null): Category
    {
        $workspace = $category->getWorkspace();
        if (!$workspace instanceof Workspace) {
            return $category;
        }
        $title = $category->getTitle();
        $changes = [];
        if (null !== $edit->getTitle() && $edit->getTitle() !== $title) {
            $changes['title'] = [
                'old' => $title,
                'new' => $edit->getTitle(),
            ];
            $title = $edit->getTitle();
            $slug = $this->getUniqueSlug($title, $workspace);
            $category->setTitle($title)
                ->setSlug($slug);
        }
        $oldParent = $category->getParent();
        $newParent = $edit->getParent();
        if ($newParent !== $oldParent && $newParent !== $category && $this->doesNotCreateLoop($newParent, $category)) {
            $changes['parent'] = [
                'old' => $oldParent,
                'new' => $newParent,
            ];
            $category->setParent($newParent);
            if ($oldParent instanceof Category) {
                $this->dispatcher->dispatch(new CategoryEditedEvent($oldParent));
            }
        }

        $this->dispatcher->dispatch(new CategoryEditedEvent($category));

        if ([] !== $changes) {
            $this->databaseLogger->log(
                userAction: UserAction::EDIT_CATEGORY,
                object: $category,
                metadata: $changes,
                actingUser: $user
            );
        }

        return $category;
    }

    private function getUniqueSlug(string $title, Workspace $workspace, int $count = 0): string
    {
        $slug = $this->slugger->slug(strtolower($title))->toString();
        if ($count > 0) {
            $slug = sprintf('%s-%s', $slug, $count);
        }
        $checkCategory = $this->categoryRepository->findOneBy(['slug' => $slug, 'workspace' => $workspace]);
        if (null === $checkCategory) {
            return $slug;
        }

        return $this->getUniqueSlug($title, $workspace, $count + 1);
    }

    /**
     * @param Category[] $newCategories
     */
    public function updateFileCategories(File $file, array $newCategories, User $user): File
    {
        $logOldCategories = $logNewCategories = [];
        /** @var FileCategory $fileCategory */
        foreach ($file->getFileCategories() as $fileCategory) {
            if (!in_array($fileCategory->getCategory(), $newCategories, true)) {
                $logOldCategories[] = $fileCategory->getCategory();
                $file->removeFileCategory($fileCategory);
                $this->dispatcher->dispatch(new FileRemovedFromCategoryEvent($fileCategory, $file));
            }
        }

        foreach ($newCategories as $category) {
            $fileCategory = $this->fileCategoryRepository->findOneBy(['file' => $file, 'category' => $category]);
            if (null === $fileCategory) {
                $fileCategory = (new FileCategory())
                    ->setFile($file)
                    ->setCategory($category)
                    ->setCreator($user);
                $this->entityManager->persist($fileCategory);
                $logNewCategories[] = $fileCategory->getCategory();
            }
            $file->addFileCategory($fileCategory);
            $this->dispatcher->dispatch(new FileAddedToCategoryEvent($fileCategory));
        }
        $logOldCategories = array_filter($logOldCategories);
        $logNewCategories = array_filter($logNewCategories);
        if (count($logNewCategories) < 1 && count($logOldCategories) < 1) {
            return $file;
        }
        $this->databaseLogger->log(UserAction::CHANGE_FILE_CATEGORY, $file, [
            'old' => [] !== $logOldCategories ? $logOldCategories[0] : null,
            'new' => [] !== $logNewCategories ? $logNewCategories[0] : null,
        ]);

        return $file;
    }

    /**
     * @return Category[]
     */
    public function getFileCategories(File $file): array
    {
        $categories = [];
        /** @var FileCategory $fileCategory */
        foreach ($file->getFileCategories() as $fileCategory) {
            $category = $fileCategory->getCategory();
            if (null !== $category && !in_array($category, $categories, true)) {
                $categories[] = $category;
            }
        }

        return $categories;
    }

    /**
     * Diese Funktion gibt die erste Kategorie zurück.
     * Aktuell ist der Wunsch, dass jede Datei immer nur in einer Kategorie/einem "Ordner"
     * zu finden ist, da die Anwendungsfälle aber durchaus vorhanden sind, dass eine Datei
     * auch in mehreren Kategorien liegen kann, ist das auch vorgesehen.
     */
    public function getFileCategory(File $file): ?Category
    {
        $categories = $this->getFileCategories($file);
        if ([] !== $categories) {
            return $categories[0];
        }

        return null;
    }

    public function updateFileCategory(File $file, ?Category $category, User $user): File
    {
        $categories = $category instanceof Category ? [$category] : [];

        return $this->updateFileCategories($file, $categories, $user);
    }

    /**
     * @throws PaginatorException
     */
    public function filterAndPaginateCategories(Workspace $workspace, SortFilterPaginateArguments $sortFilterPaginateArguments, ?Category $parent = null): Paginator
    {
        $queryBuilder = $this->categoryRepository->getWorkspaceQueryBuilder($workspace, $parent);
        $queryBuilder = $this->addSort($queryBuilder, $sortFilterPaginateArguments->getSort());

        return (new Paginator())->paginate(query: $queryBuilder->getQuery(), page: $sortFilterPaginateArguments->getPage(), limit: $sortFilterPaginateArguments->getLimit());
    }

    private function addSort(QueryBuilder $queryBuilder, SortParam $sortParam): QueryBuilder
    {
        return match ($sortParam) {
            SortParam::UPLOADED_ASC => $queryBuilder->orderBy('c.createdAt', 'ASC'),
            SortParam::UPLOADED_DESC => $queryBuilder->orderBy('c.createdAt', 'DESC'),
            SortParam::MODIFIED_ASC => $queryBuilder->orderBy('c.updatedAt', 'ASC'),
            SortParam::MODIFIED_DESC => $queryBuilder->orderBy('c.updatedAt', 'DESC'),
        };
    }

    public function getCategoryBySlug(string $slug, Workspace $workspace): ?Category
    {
        return $this->categoryRepository->findOneBy(['workspace' => $workspace, 'slug' => $slug]);
    }

    public function getCategoryChildren(Workspace $workspace, ?Category $parent = null): array
    {
        return $this->categoryRepository->findBy(['workspace' => $workspace, 'parent' => $parent], ['title' => 'ASC']);
    }

    /**
     * @throws CategoryException
     */
    public function deleteCategory(Category $category, ?User $user = null): void
    {
        $this->databaseLogger->log(userAction: UserAction::DELETE_CATEGORY, object: $category, actingUser: $user);

        $this->removeCategory($category);
        $this->entityManager->flush();
    }

    /**
     * @throws CategoryException
     */
    private function removeCategory(Category $category): void
    {
        if ($category->getAssetCount() > 0) {
            throw new CategoryException();
        }
        foreach ($category->getChildren() as $child) {
            $this->removeCategory($child);
        }
        $this->entityManager->remove($category);
    }

    public function doesNotCreateLoop(?Category $newParent, Category $category): bool
    {
        if (!$newParent instanceof Category || !$newParent->getParent() instanceof Category) {
            return true;
        }
        if ($newParent === $category || $newParent->getParent() === $category) {
            return false;
        }

        return $this->doesNotCreateLoop($newParent->getParent(), $category);
    }
}
