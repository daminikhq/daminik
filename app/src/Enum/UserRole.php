<?php

declare(strict_types=1);

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum UserRole: string implements TranslatableInterface
{
    case USER = 'ROLE_USER';
    case SUPER_ADMIN = 'ROLE_SUPER_ADMIN';
    case WORKSPACE_OWNER = 'ROLE_WORKSPACE_OWNER';
    case WORKSPACE_ADMIN = 'ROLE_WORKSPACE_ADMIN';
    case WORKSPACE_USER = 'ROLE_WORKSPACE_USER';
    case WORKSPACE_VIEWER = 'ROLE_WORKSPACE_VIEWER';
    case WORKSPACE_ROBOT = 'ROLE_WORKSPACE_ROBOT';

    /**
     * @return array<string, string>
     */
    public static function getGlobalChoices(): array
    {
        $cases = array_column(self::globalCases(), 'value');
        $keys = array_map(static fn (string $value): string => 'role.'.$value, $cases);

        return array_combine($keys, $cases);
    }

    /**
     * @return self[]
     */
    private static function globalCases(): array
    {
        return [self::USER, self::SUPER_ADMIN];
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans(id: 'role.'.$this->name, domain: $locale);
    }
}
