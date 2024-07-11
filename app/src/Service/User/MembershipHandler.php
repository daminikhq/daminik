<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Dto\User\UserAdminEdit;
use App\Dto\Utility\SortFilterPaginateArguments;
use App\Entity\Membership;
use App\Entity\User;
use App\Entity\Workspace;
use App\Enum\UserRole;
use App\Event\Membership\MembershipDeletedEvent;
use App\Event\Membership\MembershipUpdatedEvent;
use App\Exception\UserHandler\CantRemoveLastOwnerException;
use App\Exception\UserHandler\WorkspaceNotSetException;
use App\Repository\MembershipRepository;
use App\Util\Paginator;
use App\Util\Paginator\PaginatorException;
use App\Util\User\RoleUtil;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

readonly class MembershipHandler implements MembershipHandlerInterface
{
    public function __construct(
        private Paginator $paginator,
        private MembershipRepository $membershipRepository,
        private RoleUtil $workspaceRoleUtil,
        private EntityManagerInterface $entityManager,
        private EventDispatcherInterface $dispatcher
    ) {
    }

    /**
     * @throws PaginatorException
     */
    public function filterAndPaginateMemberships(Workspace $workspace, SortFilterPaginateArguments $sortFilterPaginateArguments): Paginator
    {
        return $this->paginator->paginate(
            query: $this->membershipRepository->getMembershipQuery($workspace, $sortFilterPaginateArguments->getSort()),
            page: $sortFilterPaginateArguments->getPage(),
            limit: $sortFilterPaginateArguments->getLimit()
        );
    }

    public function getMembership(User $user, Workspace $workspace): ?Membership
    {
        foreach ($user->getMemberships() as $membership) {
            if ($membership->getWorkspace() === $workspace) {
                return $membership;
            }
        }

        return null;
    }

    public function getHighestWorkspaceRole(Membership $membership): UserRole
    {
        return $this->workspaceRoleUtil->getHighestWorkspaceRole($membership) ?? UserRole::WORKSPACE_USER;
    }

    /**
     * @throws WorkspaceNotSetException
     * @throws CantRemoveLastOwnerException
     */
    public function updateMembership(Membership $membership, UserAdminEdit $userAdminDto): void
    {
        if (
            UserRole::WORKSPACE_OWNER !== $userAdminDto->getRole()
            && in_array(UserRole::WORKSPACE_OWNER->value, $membership->getRoles(), true)
        ) {
            $this->checkWorkspaceForOwner($membership->getWorkspace(), $membership->getUser());
        }

        $oldRoles = $membership->getRoles();

        $membership->setRoles([$userAdminDto->getRole()->value]);
        $newRoles = $membership->getRoles();
        if ($oldRoles !== $newRoles) {
            $this->dispatcher->dispatch(new MembershipUpdatedEvent($membership, null, [
                'roles' => [
                    'old' => $oldRoles,
                    'new' => $newRoles,
                ],
            ]));
        }
        $this->entityManager->flush();
    }

    /**
     * @throws WorkspaceNotSetException
     * @throws CantRemoveLastOwnerException
     */
    public function deleteMembership(Membership $membership): void
    {
        $this->checkWorkspaceForOwner($membership->getWorkspace(), $membership->getUser());
        $this->dispatcher->dispatch(new MembershipDeletedEvent($membership));
        $this->entityManager->remove($membership);
        $this->entityManager->flush();
    }

    /**
     * @param User|null $user If this parameter is set this user will be ignored when counting the owner accounts
     *
     * @throws CantRemoveLastOwnerException
     * @throws WorkspaceNotSetException
     */
    public function checkWorkspaceForOwner(?Workspace $workspace, ?User $user = null): void
    {
        if (!$workspace instanceof Workspace) {
            throw new WorkspaceNotSetException();
        }
        foreach ($workspace->getMemberships() as $membership) {
            if (
                (!$user instanceof User || $user !== $membership->getUser())
                && in_array(UserRole::WORKSPACE_OWNER->value, $membership->getRoles(), true)
            ) {
                return;
            }
        }
        throw new CantRemoveLastOwnerException();
    }
}
