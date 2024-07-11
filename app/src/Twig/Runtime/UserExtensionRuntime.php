<?php

namespace App\Twig\Runtime;

use App\Dto\User\Avatar;
use App\Entity\Membership;
use App\Entity\User;
use App\Entity\Workspace;
use App\Enum\UserRole;
use App\Service\User\AvatarHandler;
use App\Service\Workspace\WorkspaceIdentifier;
use App\Util\User\RoleUtil;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\RuntimeExtensionInterface;

class UserExtensionRuntime implements RuntimeExtensionInterface
{
    private ?Avatar $avatar = null;

    public function __construct(
        private readonly AvatarHandler $avatarHandler,
        private readonly RoleUtil $roleUtil,
        private readonly TranslatorInterface $translator,
        private readonly WorkspaceIdentifier $workspaceIdentifier
    ) {
    }

    public function hasAvatar(): bool
    {
        if ($this->avatar instanceof Avatar) {
            return null !== $this->avatar->getUrl();
        }

        $this->avatar = $this->avatarHandler->getAvatar();

        return null !== $this->avatar->getUrl();
    }

    public function avatarUrl(): string
    {
        if ($this->avatar instanceof Avatar) {
            return $this->avatar->getUrl() ?? '';
        }

        $this->avatar = $this->avatarHandler->getAvatar();

        return (string) $this->avatar->getUrl();
    }

    public function userInitials(User $user): string
    {
        $username = $this->userName($user);
        if (mb_strlen($username) <= 2) {
            return strtoupper(trim($username));
        }
        $nameExplosion = explode(' ', $username);

        return strtoupper(
            implode(
                '',
                array_filter([
                    mb_substr(reset($nameExplosion), 0, 1),
                    mb_substr(array_pop($nameExplosion), 0, 1),
                ])
            )
        );
    }

    public function userName(User $user): string
    {
        if (null !== $user->getName()) {
            return $user->getName();
        }
        if (null !== $user->getUsername()) {
            return $user->getUsername();
        }
        /*
         * getUserIdentifier instead of getEmail because it is always the email, but we can assume it is casted to
         * string
         */
        $emailFragments = explode('@', $user->getUserIdentifier());

        return $emailFragments[0];
    }

    public function daminikRole(User $user): string
    {
        $role = $this->roleUtil->getHighestRole($user);

        return $role->trans($this->translator);
    }

    /**
     * @todo Test
     */
    public function workspaceRole(Membership|User $object): string
    {
        $membership = null;
        if ($object instanceof User) {
            $workspace = $this->workspaceIdentifier->getWorkspace();
            if (!$workspace instanceof Workspace) {
                return '';
            }
            foreach ($object->getMemberships() as $membership) {
                if ($membership->getWorkspace() === $workspace) {
                    break;
                }
            }
        } else {
            $membership = $object;
        }
        if (null === $membership) {
            return '';
        }
        $role = $this->roleUtil->getHighestWorkspaceRole($membership);
        if (!$role instanceof UserRole) {
            return '';
        }

        return $role->trans($this->translator);
    }
}
