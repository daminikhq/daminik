<?php

namespace App\Twig\Components;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class AssetViewBackButton
{
    public string $path = 'workspace_index';
    /**
     * @var array<string|int, mixed>
     */
    public array $parameters = [];

    public function __construct(
        RouterInterface $router,
        RequestStack $requestStack
    ) {
        $contextString = $requestStack->getMainRequest()?->get('context');
        if (!is_string($contextString)) {
            return;
        }
        $cs = explode('?', $contextString);
        $matched = $router->match($cs[0]);
        if (!array_key_exists('_route', $matched)) {
            return;
        }
        $this->path = $matched['_route'];
        unset($matched['_route']);
        if (array_key_exists('_controller', $matched)) {
            unset($matched['_controller']);
        }

        if (count($cs) > 1) {
            parse_str($cs[1], $result);
            $matched = array_merge($matched, $result);
        }

        $this->parameters = $matched;
    }
}
