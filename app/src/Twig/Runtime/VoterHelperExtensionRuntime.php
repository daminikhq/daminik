<?php

namespace App\Twig\Runtime;

use App\Entity\Category;
use App\Entity\Membership;
use App\Entity\Workspace;
use App\Security\Voter\CategoryVoter;
use App\Security\Voter\MembershipVoter;
use App\Security\Voter\WorkspaceVoter;
use App\Service\Workspace\WorkspaceIdentifier;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\RuntimeExtensionInterface;

readonly class VoterHelperExtensionRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private WorkspaceIdentifier $workspaceIdentifier,
        private Security $security
    ) {
    }

    public function canUploadAsset(?Workspace $workspace = null): bool
    {
        return $this->checkWorkspaceAttribute(WorkspaceVoter::UPLOAD_ASSET, $workspace);
    }

    public function canEditAsset(?Workspace $workspace = null): bool
    {
        return $this->checkWorkspaceAttribute(WorkspaceVoter::EDIT_ASSET, $workspace);
    }

    public function canCreateCollection(?Workspace $workspace = null): bool
    {
        return $this->checkWorkspaceAttribute(WorkspaceVoter::CREATE_COLLECTION, $workspace);
    }

    public function canSendInvite(?Workspace $workspace = null): bool
    {
        return $this->checkWorkspaceAttribute(WorkspaceVoter::INVITE, $workspace);
    }

    public function canEditAdmins(?Workspace $workspace = null): bool
    {
        return $this->checkWorkspaceAttribute(WorkspaceVoter::ADMINS_EDIT, $workspace);
    }

    public function canEditUsers(?Workspace $workspace = null): bool
    {
        return $this->checkWorkspaceAttribute(WorkspaceVoter::USERS_EDIT, $workspace);
    }

    public function canViewLog(?Workspace $workspace = null): bool
    {
        return $this->checkWorkspaceAttribute(WorkspaceVoter::VIEW_LOG, $workspace);
    }

    public function canCreateCategories(?Workspace $workspace = null): bool
    {
        return $this->checkWorkspaceAttribute(WorkspaceVoter::CREATE_CATEGORIES, $workspace);
    }

    public function canEditMembership(Membership $membership): bool
    {
        return $this->checkMembershipAttribute(MembershipVoter::MEMBERSHIP_EDIT, $membership);
    }

    public function canDeleteMembership(Membership $membership): bool
    {
        return $this->checkMembershipAttribute(MembershipVoter::MEMBERSHIP_DELETE, $membership);
    }

    public function canDeleteCategory(?Category $category = null): bool
    {
        if (!$category instanceof Category) {
            return false;
        }

        return $this->checkCategoryAttribute(CategoryVoter::DELETE, $category);
    }

    public function canEditCategory(?Category $category = null): bool
    {
        if (!$category instanceof Category) {
            return false;
        }

        return $this->checkCategoryAttribute(CategoryVoter::EDIT, $category);
    }

    private function checkWorkspaceAttribute(string $attribute, ?Workspace $workspace): bool
    {
        if (!$workspace instanceof Workspace) {
            $workspace = $this->workspaceIdentifier->getWorkspace();
        }

        if (!$workspace instanceof Workspace) {
            return false;
        }

        return $this->security->isGranted($attribute, $workspace);
    }

    private function checkMembershipAttribute(string $attribute, Membership $membership): bool
    {
        return $this->security->isGranted($attribute, $membership);
    }

    private function checkCategoryAttribute(string $attribute, Category $category): bool
    {
        return $this->security->isGranted($attribute, $category);
    }
}
