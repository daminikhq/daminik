<?php

namespace App\Twig\Runtime;

use App\Entity\File;
use App\Entity\Workspace;
use App\Service\File\Helper\UrlHelperInterface;
use Twig\Extension\RuntimeExtensionInterface;

readonly class WorkspaceExtensionRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private UrlHelperInterface $urlHelper
    ) {
    }

    public function workspaceIcon(mixed $workspace, int $size = 32): string
    {
        if (!$workspace instanceof Workspace) {
            return '';
        }

        $iconFile = $workspace->getIconFile();
        if (!$iconFile instanceof File) {
            return '';
        }

        $workspaceIcon = $this->urlHelper->getWorkspaceIcon($iconFile);
        if (null === $workspaceIcon) {
            return '';
        }

        return
            sprintf(
                '<!--suppress HtmlUnknownTarget -->
            <img src="%s" alt="%s" class="workspace-icon" width="%s" height="%s">',
                $workspaceIcon,
                $workspace->getName(),
                $size,
                $size
            );
    }
}
