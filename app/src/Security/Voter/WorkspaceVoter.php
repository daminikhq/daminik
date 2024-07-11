<?php

namespace App\Security\Voter;

use App\Entity\Membership;
use App\Entity\User;
use App\Entity\Workspace;
use App\Enum\UserStatus;
use App\Repository\WorkspaceRepository;
use App\Util\User\RoleUtil;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class WorkspaceVoter extends Voter
{
    /*
     * Note: Please add new helper functions in Twig\Runtime\VoterHelperExtensionRuntime
     * and Twig\Extension\VoterHelperExtension when adding new capabilites
     */

    public const VIEW = 'WORKSPACE_VIEW';
    public const UPLOAD_ASSET = 'WORKSPACE_UPLOAD_ASSET'; // can_upload_asset
    public const EDIT_ASSET = 'WORKSPACE_EDIT_ASSET'; // can_edit_asset
    public const CREATE_COLLECTION = 'WORKSPACE_CREATE_COLLECTION'; // can_create_collection
    public const INVITE = 'WORKSPACE_INVITE'; // can_send_invite
    public const OWNERS_EDIT = 'WORKSPACE_OWNERS_EDIT';
    public const ADMINS_EDIT = 'WORKSPACE_ADMINS_EDIT'; // can_edit_admins
    public const USERS_EDIT = 'WORKSPACE_USERS_EDIT'; // can_edit_users
    public const VIEW_LOG = 'WORKSPACE_VIEW_LOG'; // can_view_log
    public const CREATE_CATEGORIES = 'WORKSPACE_CREATE_CATEGORIES'; // can_create_categories
    public const EDIT_MEMBERSHIP = 'WORKSPACE_EDIT_MEMBERSHIP';
    public const CONFIG = 'WORKSPACE_CONFIG';
    public const VIEW_ASSET = 'WORKSPACE_VIEW_ASSET';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly WorkspaceRepository $workspaceRepository,
        private readonly LoggerInterface $logger,
        private readonly RoleUtil $workspaceRoleUtil,
        private readonly ?int $maxUpload = null)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array(
            needle: $attribute,
            haystack: (new \ReflectionClass($this))->getConstants(),
            strict: true
        ) && ($subject instanceof Workspace || null === $subject);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if (null === $subject) {
            $subdomain = $this->requestStack->getMainRequest()?->attributes->get('subdomain');
            $this->logger->debug('Voter Subdomain', [
                'subdomain' => $subdomain,
            ]);
            if (is_string($subdomain)) {
                $subject = $this->workspaceRepository->findOneBy(['slug' => $subdomain]);
            }
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

        $userStatus = UserStatus::fromUser($user);
        if (UserStatus::BLOCKED === $userStatus) {
            return false;
        }

        if (!$subject instanceof Workspace) {
            return false;
        }

        if (self::VIEW_ASSET === $attribute && 'global' === $subject->getSlug()) {
            return true;
        }

        $membership = null;
        $workspaceRoles = [];
        /** @var Membership $membership */
        foreach ($user->getMemberships() as $membership) {
            if ($membership->getWorkspace() === $subject) {
                $workspaceRoles = $membership->getRoles();
                break;
            }
        }

        if (null === $membership || count($workspaceRoles) < 1) {
            return false;
        }

        return match ($attribute) {
            self::OWNERS_EDIT => $this->workspaceRoleUtil->isWorkspaceOwner($membership),
            self::ADMINS_EDIT, self::CONFIG, self::VIEW_LOG, self::INVITE, self::USERS_EDIT, self::EDIT_MEMBERSHIP => $this->workspaceRoleUtil->isWorkspaceAdmin($membership),
            self::EDIT_ASSET, self::CREATE_CATEGORIES, self::CREATE_COLLECTION => $this->workspaceRoleUtil->isWorkspaceUser($membership),
            self::UPLOAD_ASSET => $this->canUploadAsset($membership),
            self::VIEW, self::VIEW_ASSET => true,
            default => false,
        };
    }

    private function canUploadAsset(Membership $membership): bool
    {
        if (null !== $this->maxUpload && $membership->getUser()?->getUploadedMB() > $this->maxUpload) {
            return false;
        }

        return $this->workspaceRoleUtil->isWorkspaceUser($membership);
    }
}
