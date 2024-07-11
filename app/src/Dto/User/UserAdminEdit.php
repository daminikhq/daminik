<?php

declare(strict_types=1);

namespace App\Dto\User;

use App\Dto\AbstractDto;
use App\Enum\UserRole;

class UserAdminEdit extends AbstractDto
{
    private UserRole $role = UserRole::WORKSPACE_USER;

    public function getRole(): UserRole
    {
        return $this->role;
    }

    public function setRole(UserRole $role): UserAdminEdit
    {
        $this->role = $role;

        return $this;
    }
}
