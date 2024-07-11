<?php

namespace App\Twig\Extension;

use App\Twig\Runtime\MembershipExtensionRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class MembershipExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('highestRole', [MembershipExtensionRuntime::class, 'highestRole']),
        ];
    }
}
