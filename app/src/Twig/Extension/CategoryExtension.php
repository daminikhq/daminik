<?php

namespace App\Twig\Extension;

use App\Twig\Runtime\CategoryExtensionRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CategoryExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('workspace_has_categories', [CategoryExtensionRuntime::class, 'workspaceHasCategories']),
            new TwigFunction('workspace_categories', [CategoryExtensionRuntime::class, 'workspaceCategories']),
            new TwigFunction('workspace_has_folders', [CategoryExtensionRuntime::class, 'workspaceHasCategories']),
            new TwigFunction('workspace_folders', [CategoryExtensionRuntime::class, 'workspaceCategories']),
        ];
    }
}
