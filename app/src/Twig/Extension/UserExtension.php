<?php

namespace App\Twig\Extension;

use App\Twig\Runtime\UserExtensionRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class UserExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('hasAvatar', [UserExtensionRuntime::class, 'hasAvatar']),
            new TwigFunction('avatarUrl', [UserExtensionRuntime::class, 'avatarUrl']),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('userName', [UserExtensionRuntime::class, 'userName']),
            new TwigFilter('userInitials', [UserExtensionRuntime::class, 'userInitials']),
            new TwigFilter('workspaceRole', [UserExtensionRuntime::class, 'workspaceRole']),
            new TwigFilter('daminikRole', [UserExtensionRuntime::class, 'daminikRole']),
        ];
    }
}
