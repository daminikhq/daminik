<?php

/** @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection */
declare(strict_types=1);

namespace App\Event\Collection;

use App\Entity\AssetCollection;
use App\Entity\User;
use App\Entity\Workspace;
use App\Enum\UserAction;
use App\Listener\LoggableEventInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\EventDispatcher\Event;

class AssetCollectionConfigChangedEvent extends Event implements AssetCollectionEventInterface, LoggableEventInterface
{
    /**
     * @param array<string, array<string, bool|string|null>> $metadata
     */
    public function __construct(
        private readonly AssetCollection $assetCollection,
        private readonly ?User $user = null,
        private readonly array $metadata = []
    ) {
    }

    public function getAssetCollection(): AssetCollection
    {
        return $this->assetCollection;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getUserAction(): UserAction
    {
        return UserAction::UPDATE_COLLECTION_CONFIG;
    }

    public function getObject(): object|null
    {
        return $this->assetCollection;
    }

    /**
     * @return array<mixed>|null
     */
    public function getMetadata(): array|null
    {
        return $this->metadata;
    }

    public function getActingUser(): ?UserInterface
    {
        return $this->user;
    }

    public function getWorkspace(): ?Workspace
    {
        return $this->assetCollection->getWorkspace();
    }
}
