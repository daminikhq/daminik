<?php

declare(strict_types=1);

namespace App\Service\File;

use App\Dto\Utility\FilterOptions;
use App\Dto\Utility\SortFilterPaginateArguments;
use App\Entity\File;
use App\Entity\Workspace;
use App\Exception\FileHandlerException;
use App\Service\File\Filter\AbstractFileFilter;
use App\Util\Paginator;
use App\Util\Paginator\PaginatorException;
use Symfony\Component\HttpFoundation\Request;

interface FilePaginationHandlerInterface
{
    /**
     * @param array<AbstractFileFilter> $additionalFilters
     *
     * @throws FileHandlerException
     * @throws PaginatorException
     */
    public function filterAndPaginateFiles(Workspace $workspace, SortFilterPaginateArguments $sortFilterPaginateArguments, array $additionalFilters = []): Paginator;

    /**
     * @param array<AbstractFileFilter> $additionalFilters
     *
     * @throws FileHandlerException
     */
    public function getFilterOptions(Workspace $workspace, SortFilterPaginateArguments $sortFilterPaginateArguments, array $additionalFilters = []): FilterOptions;

    public function getNextFileUrl(File $file, ?Request $request): ?string;

    public function getPreviousFileUrl(File $file, ?Request $request): ?string;
}
