<?php

declare(strict_types=1);

namespace App\Dto\Admin\Form;

use App\Dto\AbstractDto;
use App\Enum\UserRole;
use App\Enum\UserStatus;

class UserEdit extends AbstractDto
{
    private UserStatus $status = UserStatus::ACTIVE;
    private UserRole $role = UserRole::USER;
    private bool $blockUserWorkspaces = false;
    private ?string $adminNotice = null;

    public function getStatus(): UserStatus
    {
        return $this->status;
    }

    public function setStatus(UserStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getRole(): UserRole
    {
        return $this->role;
    }

    public function setRole(UserRole $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function isBlockUserWorkspaces(): bool
    {
        return $this->blockUserWorkspaces;
    }

    public function setBlockUserWorkspaces(bool $blockUserWorkspaces): self
    {
        $this->blockUserWorkspaces = $blockUserWorkspaces;

        return $this;
    }

    public function getAdminNotice(): ?string
    {
        return $this->adminNotice;
    }

    public function setAdminNotice(?string $adminNotice): self
    {
        $this->adminNotice = $adminNotice;

        return $this;
    }
}
