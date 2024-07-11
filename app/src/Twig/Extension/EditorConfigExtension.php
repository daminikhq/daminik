<?php

namespace App\Twig\Extension;

use App\Twig\Runtime\EditorConfigExtensionRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class EditorConfigExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('editor_license', [EditorConfigExtensionRuntime::class, 'editorLicense']),
            new TwigFunction('has_editor_license', [EditorConfigExtensionRuntime::class, 'hasEditorLicense']),
        ];
    }
}
