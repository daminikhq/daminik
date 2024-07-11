<?php

declare(strict_types=1);

namespace App\Util\User;

use App\Entity\Membership;
use App\Entity\User;
use App\Enum\UserRole;

class RoleUtil
{
    public function isWorkspaceOwner(Membership $membership): bool
    {
        return in_array(needle: UserRole::WORKSPACE_OWNER->value, haystack: $membership->getRoles(), strict: true);
    }

    public function isWorkspaceAdmin(Membership $membership): bool
    {
        if ($this->isWorkspaceOwner($membership)) {
            return true;
        }

        return in_array(needle: UserRole::WORKSPACE_ADMIN->value, haystack: $membership->getRoles(), strict: true);
    }

    public function isWorkspaceUser(Membership $membership): bool
    {
        if ($this->isWorkspaceAdmin($membership)) {
            return true;
        }

        return in_array(needle: UserRole::WORKSPACE_USER->value, haystack: $membership->getRoles(), strict: true);
    }

    private function isWorkspaceRobot(Membership $membership): bool
    {
        return in_array(needle: UserRole::WORKSPACE_ROBOT->value, haystack: $membership->getRoles(), strict: true);
    }

    private function isWorkspaceViewer(Membership $membership): bool
    {
        if ($this->isWorkspaceUser($membership)) {
            return true;
        }

        return in_array(needle: UserRole::WORKSPACE_VIEWER->value, haystack: $membership->getRoles(), strict: true);
    }

    public function getHighestWorkspaceRole(Membership $membership): ?UserRole
    {
        if ($this->isWorkspaceOwner($membership)) {
            return UserRole::WORKSPACE_OWNER;
        }
        if ($this->isWorkspaceAdmin($membership)) {
            return UserRole::WORKSPACE_ADMIN;
        }
        if ($this->isWorkspaceUser($membership)) {
            return UserRole::WORKSPACE_USER;
        }
        if ($this->isWorkspaceRobot($membership)) {
            return UserRole::WORKSPACE_ROBOT;
        }
        if ($this->isWorkspaceViewer($membership)) {
            return UserRole::WORKSPACE_VIEWER;
        }

        return null;
    }

    public function getHighestRole(User $user): UserRole
    {
        if (in_array(UserRole::SUPER_ADMIN->value, $user->getRoles())) {
            return UserRole::SUPER_ADMIN;
        }

        return UserRole::USER;
    }
}
