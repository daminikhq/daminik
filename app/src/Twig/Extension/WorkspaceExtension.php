<?php

namespace App\Twig\Extension;

use App\Twig\Runtime\WorkspaceExtensionRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class WorkspaceExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('workspaceicon', [WorkspaceExtensionRuntime::class, 'workspaceIcon'], [
                'is_safe' => ['html'],
            ]),
        ];
    }
}
