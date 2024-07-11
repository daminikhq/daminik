<?php

declare(strict_types=1);

namespace App\Enum;

use App\Entity\Workspace;

enum WorkspaceStatus: string
{
    case ACTIVE = 'active';
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

    public static function fromWorkspace(Workspace $workspace): WorkspaceStatus
    {
        return (null === $workspace->getStatus()) ? self::ACTIVE : self::tryFrom($workspace->getStatus()) ?? self::ACTIVE;
    }
}
