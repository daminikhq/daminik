<?php

namespace App\Twig\Runtime;

use App\Entity\AssetCollection;
use App\Entity\Workspace;
use App\Service\Collection\CollectionHandlerInterface;
use App\Service\Workspace\WorkspaceIdentifier;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\RuntimeExtensionInterface;

class CollectionExtensionRuntime implements RuntimeExtensionInterface
{
    private ?bool $hasCollections = null;
    /** @var AssetCollection[]|null */
    private ?array $collections = null;

    public function __construct(
        private readonly WorkspaceIdentifier $workspaceIdentifier,
        private readonly CollectionHandlerInterface $collectionHandler,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function workspaceHasCollections(): bool
    {
        if (null !== $this->hasCollections) {
            return $this->hasCollections;
        }
        $this->hasCollections = [] !== $this->workspaceCollections();

        return $this->hasCollections;
    }

    /**
     * @return AssetCollection[]
     */
    public function workspaceCollections(): array
    {
        if (null !== $this->collections) {
            return $this->collections;
        }
        $workspace = $this->workspaceIdentifier->getWorkspace();
        $this->collections = $workspace instanceof Workspace ? $this->collectionHandler->getWorkspaceCollections($workspace) : [];

        return $this->collections;
    }

    public function isCollection(): bool
    {
        return 'workspace_collection_collection' === $this->requestStack->getMainRequest()?->attributes->get('_route');
    }
}
