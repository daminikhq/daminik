<?php

namespace App\Twig\Extension;

use App\Twig\Runtime\HashidExtensionRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class HashidExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('hashid', [HashidExtensionRuntime::class, 'encode']),
        ];
    }
}
