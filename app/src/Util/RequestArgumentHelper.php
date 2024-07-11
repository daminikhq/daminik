<?php

declare(strict_types=1);

namespace App\Util;

use App\Dto\Filter\BooleanFilter;
use App\Dto\Utility\DefaultRequestValues;
use App\Dto\Utility\SortFilterPaginateArguments;
use App\Enum\SortParam;
use App\Enum\ViewParam;
use App\Service\File\Helper\FilterHelper;
use Symfony\Component\HttpFoundation\Request;

class RequestArgumentHelper
{
    /**
     * @param array<string|int, mixed>|null $queryParams
     */
    public static function extractArguments(?Request $request = null, ?array $queryParams = null, ?DefaultRequestValues $defaultValues = null): SortFilterPaginateArguments
    {
        if (!$request instanceof Request && null === $queryParams) {
            throw new \UnexpectedValueException();
        }

        if (!$defaultValues instanceof DefaultRequestValues) {
            $defaultValues = new DefaultRequestValues();
        }

        return new SortFilterPaginateArguments(
            sort: self::getSort(request: $request, queryParams: $queryParams, defaultValues: $defaultValues),
            page: self::getNumericParam(key: 'page', default: $defaultValues->getPage(), request: $request, queryParams: $queryParams),
            limit: self::getNumericParam(key: 'limit', default: $defaultValues->getLimit(), request: $request, queryParams: $queryParams),
            view: self::getView(request: $request, queryParams: $queryParams, defaultValues: $defaultValues),
            search: self::getStringParam(key: 's', request: $request, queryParams: $queryParams),
            mimeTypes: self::getMimeTypeFilters(request: $request, queryParams: $queryParams),
            tags: self::getStringArrayParam(key: 'tags', request: $request, queryParams: $queryParams),
            uploadedBy: self::getStringArrayParam(key: 'uploadedby', request: $request, queryParams: $queryParams),
            uploadedAt: self::getStringParam(key: 'uploadedat', request: $request, queryParams: $queryParams),
            paginator: self::hasParam(key: 'paginator', request: $request, queryParams: $queryParams),
        );
    }

    /**
     * @param array<string|int, mixed>|null $queryParams
     */
    private static function getStringParam(string $key, ?Request $request = null, ?array $queryParams = null): ?string
    {
        if (!$request instanceof Request && null === $queryParams) {
            return null;
        }
        $param = null;
        if ($request instanceof Request) {
            $param = $request->get(key: $key);
        } elseif (null !== $queryParams && array_key_exists(key: $key, array: $queryParams)) {
            $param = $queryParams[$key];
        }

        return is_string(value: $param) && ('' !== $param && '0' !== $param) ? $param : null;
    }

    /**
     * @param array<string|int, mixed>|null $queryParams
     */
    private static function getNumericParam(string $key, int $default, ?Request $request = null, ?array $queryParams = null): int
    {
        if (!$request instanceof Request && null === $queryParams) {
            return $default;
        }
        $param = null;
        if ($request instanceof Request) {
            $param = $request->get(key: $key);
        } elseif (null !== $queryParams && array_key_exists(key: $key, array: $queryParams)) {
            $param = $queryParams[$key];
        }

        return is_numeric(value: $param) && $param > 0 ? (int) $param : $default;
    }

    /**
     * @param array<string|int, mixed>|null $queryParams
     *
     * @return string[]
     */
    private static function getStringArrayParam(string $key, ?Request $request = null, ?array $queryParams = null): array
    {
        if (!$request instanceof Request && null === $queryParams) {
            return [];
        }
        $params = null;
        if ($request instanceof Request) {
            $params = $request->get(key: $key);
        } elseif (null !== $queryParams && array_key_exists(key: $key, array: $queryParams)) {
            $params = $queryParams[$key];
        }

        return is_string(value: $params) && ('' !== $params && '0' !== $params) ? explode(separator: ',', string: trim(string: strip_tags(string: strtolower(string: $params)))) : [];
    }

    /**
     * @param array<string|int, mixed>|null $queryParams
     *
     * @noinspection PhpSameParameterValueInspection
     */
    private static function hasParam(string $key, ?Request $request = null, ?array $queryParams = null, ?string $value = null): bool
    {
        if (!$request instanceof Request && null === $queryParams) {
            return false;
        }
        if (is_array($queryParams) && array_key_exists($key, $queryParams) && null !== $queryParams[$key]) {
            return $queryParams[$key] === $value;
        }
        if (null !== $value && $request instanceof Request) {
            return $request->get(key: $key) === $value;
        }

        return $request instanceof Request && null !== $request->get(key: $key);
    }

    /**
     * @param array<string|int, mixed>|null $queryParams
     */
    public static function getSort(?Request $request = null, ?array $queryParams = null, ?DefaultRequestValues $defaultValues = null): SortParam
    {
        if (!$request instanceof Request && null === $queryParams) {
            if (null !== $defaultValues?->getSort()) {
                return SortParam::tryFrom($defaultValues->getSort()) ?? SortParam::UPLOADED_DESC;
            }

            return SortParam::UPLOADED_DESC;
        }

        $sortParam = null;
        if ($request instanceof Request) {
            $sortParam = $request->get(key: 'sort');
        } elseif (null !== $queryParams && array_key_exists(key: 'sort', array: $queryParams)) {
            $sortParam = $queryParams['sort'];
        }

        $sort = is_string(value: $sortParam) ? SortParam::tryFrom(value: $sortParam) : null;
        if (!$sort instanceof SortParam) {
            $sort = SortParam::UPLOADED_DESC;
        }

        return $sort;
    }

    /**
     * @param array<string|int, mixed>|null $queryParams
     */
    public static function getView(?Request $request = null, ?array $queryParams = null, ?DefaultRequestValues $defaultValues = null): ?ViewParam
    {
        $viewParam = null;
        if ($request instanceof Request) {
            $viewParam = $request->get(key: 'view', default: $defaultValues?->getView());
        } elseif (null !== $queryParams && array_key_exists(key: 'view', array: $queryParams)) {
            $viewParam = $queryParams['view'];
        }

        return is_string(value: $viewParam) ? ViewParam::tryFrom(value: $viewParam) : null;
    }

    /**
     * @param array<string|int, mixed>|null $queryParams
     *
     * @return BooleanFilter[]
     */
    public static function getMimeTypeFilters(?Request $request = null, ?array $queryParams = null): array
    {
        if (!$request instanceof Request && null === $queryParams) {
            return [];
        }
        $parameter = null;
        if ($request instanceof Request) {
            $parameter = $request->get(key: 'filetype');
        } elseif (null !== $queryParams && array_key_exists(key: 'filetype', array: $queryParams)) {
            $parameter = $queryParams['filetype'];
        }

        return FilterHelper::getMimeTypeFilters(getParameter: $parameter);
    }
}
