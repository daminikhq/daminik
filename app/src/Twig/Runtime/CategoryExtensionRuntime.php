<?php

namespace App\Twig\Runtime;

use App\Entity\Category;
use App\Entity\Workspace;
use App\Service\Category\CategoryHandlerInterface;
use App\Service\Workspace\WorkspaceIdentifier;
use Twig\Extension\RuntimeExtensionInterface;

class CategoryExtensionRuntime implements RuntimeExtensionInterface
{
    private ?bool $hasCategories = null;
    /** @var Category[]|null */
    private ?array $categories = null;

    public function __construct(
        protected readonly WorkspaceIdentifier $workspaceIdentifier,
        protected readonly CategoryHandlerInterface $categoryHandler
    ) {
    }

    public function workspaceHasCategories(): bool
    {
        if (null !== $this->hasCategories) {
            return $this->hasCategories;
        }
        $this->hasCategories = [] !== $this->workspaceCategories();

        return $this->hasCategories;
    }

    /**
     * @return Category[]
     */
    public function workspaceCategories(): array
    {
        if (null !== $this->categories) {
            return $this->categories;
        }
        $workspace = $this->workspaceIdentifier->getWorkspace();
        $this->categories = $workspace instanceof Workspace ? $this->categoryHandler->getCategories($workspace) : [];

        return $this->categories;
    }
}
