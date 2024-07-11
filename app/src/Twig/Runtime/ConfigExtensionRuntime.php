<?php

namespace App\Twig\Runtime;

use App\Service\Ai\Imagga\Client;
use Twig\Extension\RuntimeExtensionInterface;

class ConfigExtensionRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private readonly Client $imaggaClient
    ) {
        // Inject dependencies if needed
    }

    public function hasAiTagging(): bool
    {
        return $this->imaggaClient->hasConfig();
    }
}
