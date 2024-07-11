<?php

declare(strict_types=1);

namespace App\Util;

use App\Enum\SearchableRoute;

class SearchRouteHelper
{
    public static function getValidSearchRoute(mixed $route): string
    {
        $searchRoute = null;
        if (is_string($route)) {
            $searchRoute = SearchableRoute::tryFrom($route);
        }
        if (!$searchRoute instanceof SearchableRoute) {
            $searchRoute = SearchableRoute::INDEX;
        }

        return $searchRoute->value;
    }
}
