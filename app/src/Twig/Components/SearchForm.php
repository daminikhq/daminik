<?php

namespace App\Twig\Components;

use App\Dto\Filter\SearchFormDto;
use App\Form\SearchFormType;
use App\Util\SearchRouteHelper;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class SearchForm
{
    private ?FormView $form = null;
    public ?FormView $filterForm = null;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly FormFactoryInterface $formFactory
    ) {
    }

    public function getSearchForm(): FormView
    {
        if ($this->form instanceof FormView) {
            return $this->form;
        }

        $mainRequest = $this->requestStack->getMainRequest();
        $s = $mainRequest?->get('s');
        $s = is_string($s) ? $s : null;

        $parameters = $mainRequest?->query->all() ?? [];
        if (array_key_exists('s', $parameters)) {
            unset($parameters['s']);
        }
        if (array_key_exists('page', $parameters)) {
            unset($parameters['page']);
        }
        $parameterString = http_build_query($parameters);

        $route = $mainRequest?->attributes->get('_route');
        $slug = $mainRequest?->attributes->get('slug');
        $route = SearchRouteHelper::getValidSearchRoute($route);
        if (!is_string($slug)) {
            $slug = null;
        }

        $searchDto = new SearchFormDto(
            s: $s,
            parameters: $parameterString,
            route: $route,
            slug: $slug,
        );

        $this->form = $this->formFactory->create(
            SearchFormType::class,
            $searchDto,
            ['inFilterForm' => $this->filterForm instanceof FormView]
        )->createView();

        return $this->form;
    }
}
