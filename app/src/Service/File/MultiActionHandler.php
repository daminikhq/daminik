<?php

declare(strict_types=1);

namespace App\Service\File;

use App\Dto\Response\FormResponse;
use App\Entity\AssetCollection;
use App\Entity\Category;
use App\Entity\File;
use App\Entity\User;
use App\Enum\FormStatus;
use App\Enum\MultiAction;
use App\Service\Category\CategoryHandlerInterface;
use App\Service\Collection\CollectionHandlerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouterInterface;

readonly class MultiActionHandler implements MultiActionHandlerInterface
{
    public function __construct(
        private DeleterInterface $deleter,
        private CollectionHandlerInterface $collectionHandler,
        private CategoryHandlerInterface $categoryHandler,
        private LoggerInterface $logger,
        private RouterInterface $router
    ) {
    }

    public function handleMultiAction(\App\Dto\File\MultiAction $multiAction): FormResponse
    {
        return match ($multiAction->getAction()) {
            MultiAction::DELETE => $this->delete(files: $multiAction->getFiles()),
            MultiAction::UNDELETE => $this->unDelete(files: $multiAction->getFiles()),
            MultiAction::CATEGORY_ADD => $this->addToCategory(files: $multiAction->getFiles(), category: $multiAction->getCategory(), user: $multiAction->getUser()),
            MultiAction::COLLECTION_ADD => $this->addToCollection(files: $multiAction->getFiles(), collection: $multiAction->getCollection(), user: $multiAction->getUser()),
            MultiAction::COLLECTION_REMOVE => $this->removeFromCollection(files: $multiAction->getFiles(), collection: $multiAction->getCollection(), user: $multiAction->getUser()),
        };
    }

    /**
     * @param File[] $files
     */
    private function delete(array $files): FormResponse
    {
        foreach ($files as $file) {
            try {
                $this->deleter->delete($file);
            } catch (\Throwable $e) {
                $this->logger->critical('Could not delete file', [
                    'exception' => $e::class,
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                ]);

                return (new FormResponse())
                    ->setStatus(FormStatus::ERROR)
                    ->setMessage('Could not delete file');
            }
        }

        return (new FormResponse())
            ->setStatus(FormStatus::OK);
    }

    /**
     * @param File[] $files
     */
    private function addToCollection(array $files, ?AssetCollection $collection, User $user): FormResponse
    {
        if (!$collection instanceof AssetCollection) {
            return (new FormResponse())
                ->setStatus(FormStatus::ERROR)
                ->setMessage('Error adding file to collection');
        }
        foreach ($files as $file) {
            $fileCollections = $this->collectionHandler->getFileCollections($file);
            if (!in_array($collection, $fileCollections)) {
                $fileCollections[] = $collection;
                $this->collectionHandler->updateFileCollections(file: $file, collections: $fileCollections, user: $user);
            }
        }

        return (new FormResponse())
            ->setStatus(FormStatus::OK)
            ->setRedirectTo($this->router->generate('workspace_collection_collection', ['slug' => $collection->getSlug()]));
    }

    /**
     * @param File[] $files
     */
    private function unDelete(array $files): FormResponse
    {
        foreach ($files as $file) {
            try {
                if (null !== $file->getDeletedAt()) {
                    $this->deleter->unDeleteFile($file);
                }
            } catch (\Throwable $e) {
                $this->logger->critical('Could not undelete file', [
                    'exception' => $e::class,
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                ]);

                return (new FormResponse())
                    ->setStatus(FormStatus::ERROR)
                    ->setMessage('Could not undelete file');
            }
        }

        return (new FormResponse())
            ->setStatus(FormStatus::OK);
    }

    /**
     * @param File[] $files
     */
    private function addToCategory(array $files, ?Category $category, User $user): FormResponse
    {
        if (!$category instanceof Category) {
            return (new FormResponse())
                ->setStatus(FormStatus::ERROR)
                ->setMessage('Error moving file to category');
        }
        foreach ($files as $file) {
            $this->categoryHandler->updateFileCategory($file, $category, $user);
        }

        return (new FormResponse())
            ->setStatus(FormStatus::OK)
            ->setRedirectTo($this->router->generate('workspace_folder_index', ['slug' => $category->getSlug()]));
    }

    /**
     * @param File[] $files
     */
    private function removeFromCollection(array $files, ?AssetCollection $collection, User $user): FormResponse
    {
        if (!$collection instanceof AssetCollection) {
            return (new FormResponse())
                ->setStatus(FormStatus::OK)
                ->setRedirectTo($this->router->generate('workspace_collection_index'));
        }
        foreach ($files as $file) {
            $fileCollections = $this->collectionHandler->getFileCollections($file);
            $fileCollections = array_udiff($fileCollections, [$collection], static fn (AssetCollection $a, AssetCollection $b) => $a->getId() <=> $b->getId());
            $this->collectionHandler->updateFileCollections(file: $file, collections: $fileCollections, user: $user);
        }

        return (new FormResponse())
            ->setStatus(FormStatus::OK)
            ->setRedirectTo($this->router->generate('workspace_collection_collection', ['slug' => $collection->getSlug()]));
    }
}
