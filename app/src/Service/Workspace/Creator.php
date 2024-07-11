<?php

declare(strict_types=1);

namespace App\Service\Workspace;

use App\Dto\NewWorkspace;
use App\Entity\Membership;
use App\Entity\User;
use App\Entity\Workspace;
use App\Enum\MembershipStatus;
use App\Enum\UserRole;
use App\Exception\WorkspaceException;
use App\Exception\WorkspaceExistsException;
use App\Repository\WorkspaceRepository;
use Doctrine\ORM\EntityManagerInterface;

readonly class Creator implements CreatorInterface
{
    public function __construct(
        private WorkspaceRepository $workspaceRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @throws WorkspaceException
     * @throws WorkspaceExistsException
     */
    public function createWorkspace(User $user, NewWorkspace $newWorkspace): Workspace
    {
        $workspace = $this->workspaceRepository->findOneBy(['slug' => $newWorkspace->getSlug()]);
        if (null !== $workspace) {
            throw new WorkspaceExistsException();
        }

        /*
         * Sollte vom Validator abgefangen sein, wenn es aber doch mal so weit kommt,
         * werfen wir hier eine Excepion
         */
        if (null === $newWorkspace->getName() || null === $newWorkspace->getSlug()) {
            throw new WorkspaceException();
        }

        $workspace = (new Workspace())
            ->setName($newWorkspace->getName())
            ->setSlug($newWorkspace->getSlug())
            ->setLocale($newWorkspace->getLocale())
            ->setCreatedBy($user);

        $this->entityManager->persist($workspace);

        $membership = (new Membership())
            ->setUser($user)
            ->setWorkspace($workspace)
            ->setStatus(MembershipStatus::ACTIVE->value)
            ->setRoles([UserRole::WORKSPACE_OWNER->value, UserRole::WORKSPACE_ADMIN->value]);

        $this->entityManager->persist($membership);

        return $workspace;
    }

    public function getGlobalWorkspace(): Workspace
    {
        $workspace = $this->workspaceRepository->findOneBy(['slug' => 'global']);
        if (null === $workspace) {
            $workspace = (new Workspace())
                ->setName('Global Workspace')
                ->setSlug('global');

            $this->entityManager->persist($workspace);
            $this->entityManager->flush();
        }

        return $workspace;
    }
}
