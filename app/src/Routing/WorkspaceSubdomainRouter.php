<?php

declare(strict_types=1);

namespace App\Routing;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

final readonly class WorkspaceSubdomainRouter implements RouterInterface, WarmableInterface
{
    public function __construct(
        private RouterInterface $router,
        private RequestStack $requestStack
    ) {
    }

    public function setContext(RequestContext $context): void
    {
        $this->router->setContext($context);
    }

    public function getContext(): RequestContext
    {
        return $this->router->getContext();
    }

    public function getRouteCollection(): RouteCollection
    {
        return $this->router->getRouteCollection();
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function generate(string $name, array $parameters = [], int $referenceType = self::ABSOLUTE_PATH): string
    {
        if (
            str_starts_with($name, 'workspace_')
            && (
                !array_key_exists('subdomain', $parameters)
                || !is_string($parameters['subdomain'])
                || '' === trim($parameters['subdomain'])
            )) {
            $parameters['subdomain'] = $this->requestStack->getMainRequest()?->attributes->get('subdomain');
        }

        return $this->router->generate($name, $parameters, $referenceType);
    }

    /**
     * @return array<string, mixed>
     */
    public function match(string $pathinfo): array
    {
        return $this->router->match($pathinfo);
    }

    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        return [];
    }
}
