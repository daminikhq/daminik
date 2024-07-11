<?php

namespace App\Twig\Extension;

use App\Twig\Runtime\ConfigExtensionRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ConfigExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('has_ai_tagging', [ConfigExtensionRuntime::class, 'hasAiTagging']),
        ];
    }
}
