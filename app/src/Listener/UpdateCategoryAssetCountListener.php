<?php

declare(strict_types=1);

namespace App\Listener;

use App\Entity\Category;
use App\Entity\File;
use App\Event\Category\CategoryEditedEvent;
use App\Event\Category\CategoryEventInterface;
use App\Event\Category\FileAddedToCategoryEvent;
use App\Event\Category\FileCategoryEventInterface;
use App\Event\Category\FileRemovedFromCategoryEvent;
use App\Message\CategoryUpdatedMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

readonly class UpdateCategoryAssetCountListener implements EventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private MessageBusInterface $bus
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FileAddedToCategoryEvent::class => 'updateCategoryCountFromFileCategory',
            FileRemovedFromCategoryEvent::class => 'updateCategoryCountFromFileCategory',
            CategoryEditedEvent::class => 'updateCategoryCountFromCategory',
        ];
    }

    public function updateCategoryCountFromCategory(CategoryEventInterface $categoryEvent): void
    {
        $category = $categoryEvent->getCategory();
        if (null === $category->getId()) {
            return;
        }
        $this->bus->dispatch(new CategoryUpdatedMessage($category->getId()), [
            new DelayStamp(2000),
        ]);
    }

    public function updateCategoryCountFromFileCategory(FileCategoryEventInterface $event): void
    {
        $fileCategory = $event->getFileCategory();
        $category = $fileCategory->getCategory();
        $file = $fileCategory->getFile();
        if (!$file instanceof File && $event instanceof FileRemovedFromCategoryEvent) {
            $file = $event->getFile();
        }
        if (!$category instanceof Category || null === $category->getId()) {
            $this->logger->info('No category found', [
                'asset' => $fileCategory->getFile()?->getFilenameSlug(),
            ]);

            return;
        }
        if (!$file instanceof File || null === $file->getId()) {
            $this->logger->info('No file found', [
                'collection' => $category->getSlug(),
            ]);

            return;
        }
        $this->logger->info(
            $event instanceof FileAddedToCategoryEvent ? 'Asset added to category' : 'Asset removed from category',
            [
                'asset' => $file->getFilenameSlug(),
                'category' => $category->getSlug(),
            ]
        );
        $this->bus->dispatch(new CategoryUpdatedMessage($category->getId(), $file->getId()), [
            new DelayStamp(2000),
        ]);
    }
}
