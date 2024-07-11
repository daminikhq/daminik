<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\AssetCollection;
use App\Entity\Membership;
use App\Entity\User;
use App\Util\User\RoleUtil;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CollectionVoter extends Voter
{
    public const DELETE = 'COLLECTION_DELETE';
    public const EDIT = 'COLLECTION_EDIT';
    public const VIEW = 'COLLECTION_VIEW';

    public function __construct(
        private readonly RoleUtil $workspaceRoleUtil
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array(needle: $attribute, haystack: [self::DELETE, self::EDIT, self::VIEW], strict: true)
            && ($subject instanceof AssetCollection || null === $subject);
    }

    /** @noinspection DuplicatedCode */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if (!$subject instanceof AssetCollection) {
            return false;
        }

        $public = self::VIEW === $attribute && true === $subject->isPublic();
        if ($public) {
            return true;
        }

        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        $membership = null;
        $found = false;
        /** @var Membership $membership */
        foreach ($user->getMemberships() as $membership) {
            if ($membership->getWorkspace() === $subject->getWorkspace()) {
                $found = true;
                break;
            }
        }
        if (!$found || null === $membership) {
            return false;
        }

        return $this->workspaceRoleUtil->isWorkspaceUser($membership);
    }
}
