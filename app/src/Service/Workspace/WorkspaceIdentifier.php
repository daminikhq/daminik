<?php

declare(strict_types=1);

namespace App\Service\Workspace;

use App\Entity\Workspace;
use App\Repository\WorkspaceRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class WorkspaceIdentifier
{
    private ?Workspace $workspace = null;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly WorkspaceRepository $workspaceRepository,
        private readonly CreatorInterface $workspaceCreator
    ) {
    }

    public function getWorkspace(): ?Workspace
    {
        if ($this->workspace instanceof Workspace) {
            return $this->workspace;
        }
        $request = $this->requestStack->getMainRequest();
        if (!$request instanceof Request) {
            return null;
        }
        $subdomain = $request->attributes->get('subdomain');
        if (!is_string($subdomain)) {
            $httpHost = $request->server->get('HTTP_HOST');
            if (is_string($httpHost) && str_contains($httpHost, '.')) {
                $hostExplosion = array_filter(explode('.', $httpHost));
                if ([] !== $hostExplosion) {
                    $subdomain = $hostExplosion[0];
                }
            }
        }
        if (!is_string($subdomain)) {
            return null;
        }
        $this->workspace = $this->workspaceRepository->findOneBy(['slug' => $subdomain]);

        return $this->workspace;
    }

    public function getGlobalWorkspace(): Workspace
    {
        return $this->workspaceCreator->getGlobalWorkspace();
    }
}
