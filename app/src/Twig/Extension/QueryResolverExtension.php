<?php

namespace App\Twig\Extension;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * QueryResolverExtension.
 *
 * extends Twigs path method to merge filter parameters with previous request
 */
class QueryResolverExtension extends AbstractExtension
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * @param array<string|int, mixed> $parameters
     *
     * @return array<string|int, mixed>
     */
    private function resolve(array $parameters = []): array
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request instanceof Request) {
            $requestParameters = $request->query->all();

            return array_merge($requestParameters, $parameters);
        }

        return $parameters;
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('path', $this->getPath(...)),
            new TwigFunction('url', $this->getUrl(...)),
        ];
    }

    /**
     * @param array<string, string> $parameters
     */
    public function getUrl(string $name, array $parameters = [], bool $schemeRelative = false, bool $resolve = false): string
    {
        $parameters = $resolve ? $this->resolve($parameters) : $parameters;

        return $this->urlGenerator->generate($name, $parameters, $schemeRelative ? UrlGeneratorInterface::NETWORK_PATH : UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * @param array<string, string> $parameters
     */
    public function getPath(string $name, array $parameters = [], bool $relative = false, bool $resolve = false): string
    {
        $parameters = $resolve ? $this->resolve($parameters) : $parameters;

        $nameIsWorkspaceFileRoute = str_starts_with($name, 'workspace_file');
        $currentRoute = $this->requestStack->getMainRequest()?->attributes->get('_route');
        if ($nameIsWorkspaceFileRoute) {
            $context = $this->requestStack->getMainRequest()?->query->get('context');
            if (is_string($currentRoute) && !str_starts_with($currentRoute, 'workspace_file')) {
                $context ??= $this->requestStack->getMainRequest()?->getRequestUri();
            }

            if (null !== $context && '/' !== $context) {
                if (str_contains($context, '?')) {
                    $splitContext = explode('?', $context);
                    if (count($splitContext) > 1) {
                        parse_str($splitContext[1], $result);
                        if (array_key_exists('paginator', $result)) {
                            if (array_key_exists('page', $result)) {
                                unset($result['page']);
                            }
                            unset($result['paginator']);
                            if ([] !== $result) {
                                $splitContext[1] = http_build_query($result);
                            } else {
                                unset($splitContext[1]);
                            }
                        }
                        $context = implode('?', $splitContext);
                    }
                }
                if ('/' !== $context) {
                    $parameters['context'] = $context;
                }
            }
        }

        return $this->urlGenerator->generate($name, array_filter($parameters), $relative ? UrlGeneratorInterface::RELATIVE_PATH : UrlGeneratorInterface::ABSOLUTE_PATH);
    }
}
