<?php

namespace App\Twig\Components;

use App\Entity\AssetCollection;
use App\Entity\Category;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Breadcrumb
{
    public ?Category $category = null;
    public ?AssetCollection $collection = null;
    /** @var array<int, array{url?: string|null, title: string}>|null */
    public ?array $array = null;

    public function __construct(
        private readonly RouterInterface $router,
        private readonly TranslatorInterface $translator
    ) {
    }

    /**
     * @return array<int, array{url?: string|null, title: string}>|null
     */
    public function getItems(): ?array
    {
        if ($this->category instanceof Category) {
            return $this->categoryItems();
        }

        if ($this->collection instanceof AssetCollection) {
            return [
                [
                    'title' => $this->translator->trans('file.collections'),
                    'url' => $this->router->generate('workspace_collection_index'),
                ],
                [
                    'title' => $this->collection->getTitle() ?? $this->translator->trans('file.collection'),
                    'url' => $this->router->generate('workspace_collection_collection', ['slug' => $this->collection->getSlug()]),
                ],
            ];
        }

        return $this->array;
    }

    /**
     * @return array<int, array{url?: string|null, title: string}>|null
     */
    private function categoryItems(): ?array
    {
        if (!$this->category instanceof Category) {
            return null;
        }
        $items = [
            [
                'title' => $this->translator->trans('file.folders'),
                'url' => $this->router->generate('workspace_folder_index'),
            ],
        ];

        $categories = $this->addCategory($this->category);

        return array_merge($items, $categories);
    }

    /**
     * @param array<int, array{url?: string|null, title: string}> $categories
     *
     * @return array<int, array{url?: string|null, title: string}>
     */
    private function addCategory(Category $category, array $categories = []): array
    {
        array_unshift($categories, [
            'url' => $this->router->generate('workspace_folder_index', ['slug' => $category->getSlug()]),
            'title' => $category->getTitle() ?? $this->translator->trans('file.folder'),
        ]);
        if ($category->getParent() instanceof Category) {
            return $this->addCategory($category->getParent(), $categories);
        }

        return $categories;
    }
}
