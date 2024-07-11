<?php

namespace App\Twig\Extension;

use App\Twig\Runtime\CollectionExtensionRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CollectionExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('workspace_has_collections', [CollectionExtensionRuntime::class, 'workspaceHasCollections']),
            new TwigFunction('workspace_collections', [CollectionExtensionRuntime::class, 'workspaceCollections']),
        ];
    }
}
