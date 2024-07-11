<?php

declare(strict_types=1);

namespace App\Dto\DatabaseLogger;

class MembershipChanges extends MetaData
{
    /**
     * @param Changes[] $changes
     */
    public function __construct(
        protected User $user,
        protected array $changes = []
    ) {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): MembershipChanges
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @param Changes[] $changes
     */
    public function setChanges(array $changes): MembershipChanges
    {
        $this->changes = $changes;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->user->getTitle();
    }
}
