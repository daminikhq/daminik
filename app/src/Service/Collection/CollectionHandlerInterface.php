<?php

declare(strict_types=1);

namespace App\Service\Collection;

use App\Dto\Collection\Config;
use App\Dto\Collection\Create;
use App\Dto\Utility\SortFilterPaginateArguments;
use App\Entity\AssetCollection;
use App\Entity\File;
use App\Entity\User;
use App\Entity\Workspace;
use App\Interfaces\AutoCompleteQueriable;
use App\Util\Paginator;

interface CollectionHandlerInterface extends AutoCompleteQueriable
{
    /**
     * @return AssetCollection[]
     */
    public function getWorkspaceCollections(Workspace $workspace): array;

    public function createCollection(Create $create, Workspace $workspace, User $user, ?File $file = null): AssetCollection;

    public function getCollectionBySlug(string $slug, Workspace $workspace): ?AssetCollection;

    /**
     * @param AssetCollection[] $collections
     */
    public function updateFileCollections(File $file, array $collections, User $user, bool $overWrite = true): void;

    /**
     * @return AssetCollection[]
     */
    public function getFileCollections(File $file): array;

    public function filterAndPaginateCollections(Workspace $workspace, SortFilterPaginateArguments $sortFilterPaginateArguments): Paginator;

    public function updateCollectionConfig(Config $config, AssetCollection $collection, User $user): AssetCollection;

    public function deleteCollection(AssetCollection $collection, ?User $user = null): void;
}
