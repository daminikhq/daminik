<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Category;
use App\Entity\Membership;
use App\Entity\User;
use App\Util\User\RoleUtil;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CategoryVoter extends Voter
{
    public const DELETE = 'CATEGORY_DELETE';
    public const EDIT = 'CATEGORY_EDIT';

    public function __construct(
        private readonly RoleUtil $workspaceRoleUtil
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array(needle: $attribute, haystack: [self::DELETE, self::EDIT], strict: true)
            && ($subject instanceof Category || null === $subject);
    }

    /** @noinspection DuplicatedCode */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if (!$subject instanceof Category) {
            return false;
        }
        if (self::DELETE === $attribute && $subject->getAssetCount() > 0) {
            return false;
        }
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        $membership = null;
        /** @var Membership $membership */
        foreach ($user->getMemberships() as $membership) {
            if ($membership->getWorkspace() === $subject->getWorkspace()) {
                break;
            }
        }
        if (null === $membership) {
            return false;
        }

        return $this->workspaceRoleUtil->isWorkspaceUser($membership);
    }
}
