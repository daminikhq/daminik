<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\FileCategory;
use App\Message\CategoryUpdatedMessage;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final readonly class CategoryUpdatedMessageHandler
{
    public function __construct(
        private CategoryRepository $categoryRepository,
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $bus,
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(CategoryUpdatedMessage $message): void
    {
        $category = $this->categoryRepository->find($message->getCategoryId());
        if (null === $category) {
            return;
        }
        $count = $directCount = $category->getFileCategories()->filter(fn (FileCategory $fileCategory) => !$fileCategory->getFile()?->getDeletedAt() instanceof \DateTime)->count();
        $childrenCount = [];
        $children = $this->categoryRepository->findBy(['parent' => $category]);
        foreach ($children as $child) {
            $count += (int) $child->getAssetCount();
            $childrenCount[] = [
                'slug' => $child->getSlug(),
                'count' => $child->getAssetCount(),
            ];
        }
        $this->logger->debug('AssetCount', [
            'count' => $count,
            'directCount' => $directCount,
            'childrenCount' => $childrenCount,
        ]);
        if (null !== $category->getParent() && null !== $category->getParent()->getId()) {
            $this->bus->dispatch(new CategoryUpdatedMessage($category->getParent()->getId()));
        }
        $category->setAssetCount($count);
        $this->entityManager->flush();
    }
}
