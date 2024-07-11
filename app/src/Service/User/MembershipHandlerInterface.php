<?php

namespace App\Service\User;

use App\Dto\User\UserAdminEdit;
use App\Dto\Utility\SortFilterPaginateArguments;
use App\Entity\Membership;
use App\Entity\User;
use App\Entity\Workspace;
use App\Enum\UserRole;
use App\Exception\UserHandler\CantRemoveLastOwnerException;
use App\Exception\UserHandler\WorkspaceNotSetException;
use App\Util\Paginator;
use App\Util\Paginator\PaginatorException;

interface MembershipHandlerInterface
{
    /**
     * @throws PaginatorException
     */
    public function filterAndPaginateMemberships(Workspace $workspace, SortFilterPaginateArguments $sortFilterPaginateArguments): Paginator;

    public function getMembership(User $user, Workspace $workspace): ?Membership;

    public function getHighestWorkspaceRole(Membership $membership): UserRole;

    /**
     * @throws WorkspaceNotSetException
     * @throws CantRemoveLastOwnerException
     */
    public function updateMembership(Membership $membership, UserAdminEdit $userAdminDto): void;

    /**
     * @throws WorkspaceNotSetException
     * @throws CantRemoveLastOwnerException
     */
    public function deleteMembership(Membership $membership): void;

    /**
     * @param User|null $user If this parameter is set this user will be ignored when counting the owner accounts
     *
     * @throws CantRemoveLastOwnerException
     * @throws WorkspaceNotSetException
     */
    public function checkWorkspaceForOwner(?Workspace $workspace, ?User $user = null): void;
}
