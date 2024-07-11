<?php

declare(strict_types=1);

namespace App\Service\File\Helper;

use App\Dto\Filter\BooleanFilter;
use App\Enum\MimeType;

class FilterHelper
{
    /**
     * @return BooleanFilter[]
     */
    public static function getMimeTypeFilters(mixed $getParameter = null): array
    {
        if (null === $getParameter) {
            return [];
        }

        $filters = [];

        if (
            is_string($getParameter)
        ) {
            $filter = self::getMimeTypeFilter($getParameter);
            if ($filter instanceof BooleanFilter) {
                $filters[$getParameter] = $filter;
            }
        }
        if (is_array($getParameter)) {
            foreach ($getParameter as $parameter) {
                if (
                    is_string($parameter)
                ) {
                    $filter = self::getMimeTypeFilter($parameter);
                    if ($filter instanceof BooleanFilter) {
                        $filters[$parameter] = $filter;
                    }
                }
            }
        }

        return $filters;
    }

    /** @noinspection PhpSameParameterValueInspection */
    private static function getMimeTypeFilter(string $mimeTypeName, bool $value = true): ?BooleanFilter
    {
        $mimeType = MimeType::tryFromName($mimeTypeName);
        if ($mimeType instanceof MimeType) {
            return new BooleanFilter($mimeType->value, $value, mb_strtolower($mimeType->name));
        }

        return null;
    }

    /**
     * @param array<string, BooleanFilter>|null $mimeTypes
     *
     * @return array<string, BooleanFilter>|null
     *                                           This reverses all mimetype filters in case all of them are set to make sure the filters
     *                                           are not set in the filter view (Hm.)
     */
    public static function inverseMimeTypeFilter(?array $mimeTypes = null): ?array
    {
        if (null === $mimeTypes) {
            return null;
        }
        $inversed = [];
        foreach ($mimeTypes as $key => $filter) {
            if (true === $filter->getValue()) {
                $inversed[$key] = (clone $filter)->setValue(false);
            } else {
                return $mimeTypes;
            }
        }

        return $inversed;
    }
}
