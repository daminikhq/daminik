<?php

namespace App\Security\Voter;

use App\Entity\Membership;
use App\Entity\User;
use App\Entity\Workspace;
use App\Enum\MembershipStatus;
use App\Repository\MembershipRepository;
use App\Util\User\RoleUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class MembershipVoter extends Voter
{
    public const MEMBERSHIP_EDIT = 'MEMBERSHIP_EDIT';
    public const MEMBERSHIP_DELETE = 'MEMBERSHIP_DELETE';

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly MembershipRepository $membershipRepository,
        private readonly Security $security,
        private readonly RoleUtil $workspaceRoleUtil
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array(
            needle: $attribute,
            haystack: (new \ReflectionClass($this))->getConstants(),
            strict: true
        ) && ($subject instanceof Membership);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if (!$subject instanceof Membership) {
            return false;
        }

        $workspace = $subject->getWorkspace();
        if (!$workspace instanceof Workspace) {
            return false;
        }

        $user = $token->getUser();

        // if the user is anonymous, do not grant access
        if (!$user instanceof User) {
            $this->logger->debug('Voter User', [
                'class' => is_object($user) ? $user::class : null,
                'token' => $token->getUserIdentifier(),
            ]);

            return false;
        }

        $userMembership = $this->membershipRepository->findOneBy(['user' => $user, 'workspace' => $subject->getWorkspace()]);
        if (null === $userMembership) {
            return false;
        }

        return match ($attribute) {
            self::MEMBERSHIP_EDIT => $this->canEditMembership(membership: $subject, userMembership: $userMembership),
            self::MEMBERSHIP_DELETE => $this->canDeleteMembership($subject, $userMembership),
            default => false
        };
    }

    private function canEditMembership(Membership $membership, Membership $userMembership): bool
    {
        if ($membership->getStatus() === MembershipStatus::ROBOT->value) {
            return false;
        }

        if (!$membership->getWorkspace() instanceof Workspace || $membership->getWorkspace() !== $userMembership->getWorkspace()) {
            return false;
        }
        if (!$membership->getUser() instanceof User || $membership->getUser() === $userMembership->getUser()) {
            return false;
        }

        if (!$this->security->isGranted(WorkspaceVoter::EDIT_MEMBERSHIP, $membership->getWorkspace())) {
            return false;
        }

        if (
            $this->workspaceRoleUtil->isWorkspaceOwner($membership)
        ) {
            return $this->workspaceRoleUtil->isWorkspaceOwner($userMembership);
        }

        return $this->workspaceRoleUtil->isWorkspaceAdmin($userMembership);
    }

    private function canDeleteMembership(Membership $membership, Membership $userMembership): bool
    {
        if ($membership->getStatus() === MembershipStatus::ROBOT->value) {
            return false;
        }

        if (!$membership->getUser() instanceof User || $membership->getUser() === $userMembership->getUser()) {
            return false;
        }

        return $this->canEditMembership(membership: $membership, userMembership: $userMembership);
    }
}
