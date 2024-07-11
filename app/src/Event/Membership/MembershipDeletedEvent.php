<?php

declare(strict_types=1);

namespace App\Event\Membership;

use App\Enum\UserAction;

readonly class MembershipDeletedEvent extends MembershipEvent
{
    public function getUserAction(): UserAction
    {
        return UserAction::DELETE_MEMBERSHIP;
    }
}
