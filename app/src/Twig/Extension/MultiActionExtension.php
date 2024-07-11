<?php

namespace App\Twig\Extension;

use App\Twig\Runtime\MultiActionExtensionRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MultiActionExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('is_bin', [MultiActionExtensionRuntime::class, 'isBin']),
            new TwigFunction('get_context_actions', [MultiActionExtensionRuntime::class, 'getContextActions']),
        ];
    }
}
