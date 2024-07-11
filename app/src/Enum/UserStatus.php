<?php

declare(strict_types=1);

namespace App\Enum;

use App\Entity\User;

enum UserStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case BLOCKED = 'blocked';

    /**
     * @return array<string, string>
     */
    public static function getChoices(): array
    {
        $cases = array_column(self::cases(), 'value');
        $keys = array_map(static fn (string $value): string => 'choice.status.'.$value, $cases);

        return array_combine($keys, $cases);
    }

    public static function fromUser(User $user): self
    {
        return (null === $user->getStatus()) ? self::ACTIVE : self::tryFrom($user->getStatus()) ?? self::ACTIVE;
    }
}
