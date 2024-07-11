<?php

declare(strict_types=1);

namespace App\GraphQL\Loader;

use App\Dto\Api\Tag;
use App\Dto\Api\TagCollection;
use App\Entity\Workspace;
use App\Repository\TagRepository;
use App\Service\Workspace\WorkspaceIdentifier;
use App\Util\Mapper\Api\TagMapper;

readonly class TagLoader
{
    public function __construct(
        private WorkspaceIdentifier $workspaceIdentifier,
        private TagRepository $tagRepository
    ) {
    }

    public function loadTags(): ?TagCollection
    {
        $workspace = $this->workspaceIdentifier->getWorkspace();
        if (!$workspace instanceof Workspace) {
            return null;
        }
        $tags = $this->tagRepository->findBy(['workspace' => $workspace], ['slug' => 'DESC']);
        $collection = new TagCollection();
        foreach ($tags as $tag) {
            $collection->addTag(TagMapper::mapEntityToDto($tag));
        }

        return $collection;
    }

    public function loadTagBySlug(string $slug): ?Tag
    {
        $workspace = $this->workspaceIdentifier->getWorkspace();
        if (!$workspace instanceof Workspace) {
            return null;
        }
        $tag = $this->tagRepository->findOneBy(['workspace' => $workspace, 'slug' => $slug]);
        if (null === $tag) {
            return null;
        }

        return TagMapper::mapEntityToDto($tag);
    }
}
