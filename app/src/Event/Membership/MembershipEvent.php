<?php

declare(strict_types=1);

namespace App\Event\Membership;

use App\Entity\Membership;
use App\Entity\User;
use App\Entity\Workspace;
use Symfony\Component\Security\Core\User\UserInterface;

abstract readonly class MembershipEvent implements MembershipEventInterface
{
    /**
     * @param array<string, array<string, string|int|bool|array<string|int, string>|null>> $metaData
     */
    public function __construct(
        private Membership $membership,
        private ?User $user = null,
        private array $metaData = []
    ) {
    }

    public function getObject(): object|null
    {
        return $this->membership;
    }

    public function getMetadata(): array|null
    {
        return $this->metaData;
    }

    public function getActingUser(): ?UserInterface
    {
        return $this->user;
    }

    public function getWorkspace(): ?Workspace
    {
        return $this->membership->getWorkspace();
    }
}
