<?php

namespace App\Twig\Runtime;

use Symfony\Component\HttpKernel\KernelInterface;
use Twig\Extension\RuntimeExtensionInterface;

readonly class EditorConfigExtensionRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private string $projectDir,
        private KernelInterface $kernel
    ) {
    }

    public function hasEditorLicense(): bool
    {
        if ('dev' === $this->kernel->getEnvironment()) {
            return true;
        }
        $filePath = $this->projectDir.'/config/pesdk_html5_license';

        return file_exists($filePath);
    }

    public function editorLicense(): string
    {
        if ('dev' === $this->kernel->getEnvironment()) {
            return '';
        }
        $filePath = $this->projectDir.'/config/pesdk_html5_license';
        if (file_exists($filePath)) {
            return (string) file_get_contents($filePath);
        }

        return '';
    }
}
