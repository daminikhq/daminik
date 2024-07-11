<?php

declare(strict_types=1);

namespace App\Service\File;

use App\Dto\Filter\FilterMonth;
use App\Dto\Utility\DateRange;
use App\Dto\Utility\DefaultRequestValues;
use App\Dto\Utility\FilterOptions;
use App\Dto\Utility\SortFilterPaginateArguments;
use App\Entity\File;
use App\Entity\Workspace;
use App\Enum\MimeType;
use App\Enum\SortParam;
use App\Exception\FileHandlerException;
use App\Repository\FileRepository;
use App\Repository\TagRepository;
use App\Repository\UserRepository;
use App\Service\Category\CategoryHandlerInterface;
use App\Service\Collection\CollectionHandlerInterface;
use App\Service\File\Filter\AbstractFileFilter;
use App\Service\File\Filter\MimeTypeFilter;
use App\Service\File\Filter\SearchFilter;
use App\Service\File\Filter\TagFilter;
use App\Service\File\Filter\UploadedAtFilter;
use App\Service\File\Filter\UploadedByFilter;
use App\Service\Filesystem\FilesystemRegistryInterface;
use App\Util\ContextHelper;
use App\Util\DateTimeFormatUtil;
use App\Util\Paginator;
use App\Util\Paginator\PaginatorException;
use App\Util\RequestArgumentHelper;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

readonly class FilePaginationHandler implements FilePaginationHandlerInterface
{
    public function __construct(
        private FilesystemRegistryInterface $filesystemRegistry,
        private FileRepository $fileRepository,
        private Paginator $paginator,
        private TagRepository $tagRepository,
        private UserRepository $userRepository,
        private SluggerInterface $slugger,
        private RouterInterface $router,
        private Security $security,
        private CollectionHandlerInterface $collectionHandler,
        private CategoryHandlerInterface $categoryHandler,
    ) {
    }

    /**
     * @param array<AbstractFileFilter> $additionalFilters
     *
     * @throws FileHandlerException
     * @throws PaginatorException
     */
    public function filterAndPaginateFiles(
        Workspace $workspace,
        SortFilterPaginateArguments $sortFilterPaginateArguments,
        array $additionalFilters = []
    ): Paginator {
        $filters = $this->getFilters(
            additionalFilters: $additionalFilters,
            sortFilterPaginateArguments: $sortFilterPaginateArguments,
            workspace: $workspace
        );

        $this->filesystemRegistry->getWorkspaceFilesystem($workspace);
        $orderBy = $this->getOrderBy([$sortFilterPaginateArguments->getSort()]);

        $scope = $this->fileRepository->getScope($workspace);
        $files = $this->fileRepository->filterFiles($scope, $filters);
        $query = $this->fileRepository->sortFiles($files, $orderBy);

        return $this->paginator->paginate(
            query: $query,
            page: $sortFilterPaginateArguments->getPage(),
            limit: $sortFilterPaginateArguments->getLimit()
        );
    }

    /**
     * @param array<int,SortParam|null> $sortParams
     *
     * @return string[]
     */
    private function getOrderBy(array $sortParams): array
    {
        $orderBys = [];

        foreach ($sortParams as $sortParam) {
            $orderBy = match ($sortParam) {
                SortParam::UPLOADED_ASC => ['createdAt' => 'ASC'],
                default => ['createdAt' => 'DESC'],
            };
            $orderBys += $orderBy;
        }

        return $orderBys;
    }

    /**
     * @param array<AbstractFileFilter> $additionalFilters
     *
     * @throws FileHandlerException
     */
    public function getFilterOptions(
        Workspace $workspace,
        SortFilterPaginateArguments $sortFilterPaginateArguments,
        array $additionalFilters = [],
    ): FilterOptions {
        $filters = $this->getFilters(
            additionalFilters: $additionalFilters,
            sortFilterPaginateArguments: $sortFilterPaginateArguments,
            workspace: $workspace,
            hiddenFilterTypes: [MimeTypeFilter::class, UploadedAtFilter::class],
        );
        $filtered = [] !== $sortFilterPaginateArguments->getTags();

        if ([] != $sortFilterPaginateArguments->getMimeTypes()) {
            $filtered = true;
        }

        if (null !== $sortFilterPaginateArguments->getUploadedAt()) {
            $filtered = true;
        }

        if ([] !== $sortFilterPaginateArguments->getUploadedBy()) {
            $filtered = true;
        }

        $mimeTypes = $this->queryFilteredMimeTypes($workspace, $filters);

        $months = $this->queryFilteredMonths($workspace, $filters);

        return new FilterOptions(filtered: $filtered, mimeTypes: $mimeTypes, months: $months);
    }

    /**
     * @param array<AbstractFileFilter> $additionalFilters
     * @param array<int, string>        $hiddenFilterTypes
     *
     * @return array<AbstractFileFilter>
     *
     * @throws FileHandlerException
     */
    private function getFilters(
        array $additionalFilters,
        SortFilterPaginateArguments $sortFilterPaginateArguments,
        Workspace $workspace,
        array $hiddenFilterTypes = [],
    ): array {
        $filters = $additionalFilters;
        if (null !== $sortFilterPaginateArguments->getSearch()) {
            $filters[] = new SearchFilter($sortFilterPaginateArguments->getSearch());
        }

        if (!in_array(MimeTypeFilter::class, $hiddenFilterTypes) && [] !== $sortFilterPaginateArguments->getMimeTypes()) {
            $filters[] = new MimeTypeFilter($sortFilterPaginateArguments->getMimeTypes());
        }

        $tags = [];
        foreach ($sortFilterPaginateArguments->getTags() as $tagString) {
            $tags[] = $this->tagRepository->findOneBy(['slug' => $this->slugger->slug($tagString)->toString(), 'workspace' => $workspace]);
        }
        $tags = array_filter($tags);
        if ([] !== $tags) {
            $filters[] = new TagFilter($tags);
        }

        $uploaders = [];
        foreach ($sortFilterPaginateArguments->getUploadedBy() as $username) {
            $uploaders[] = $this->userRepository->findOneBy(['username' => $username]);
        }
        $uploaders = array_filter($uploaders);
        if ([] !== $uploaders) {
            $filters[] = new UploadedByFilter($uploaders);
        }

        if (!in_array(UploadedAtFilter::class, $hiddenFilterTypes) && null !== $sortFilterPaginateArguments->getUploadedAt()) {
            $filters[] = new UploadedAtFilter($sortFilterPaginateArguments->getUploadedAt());
        }

        return $filters;
    }

    /**
     * @param AbstractFileFilter[] $filters
     *
     * @return MimeType[]
     */
    private function queryFilteredMimeTypes(Workspace $workspace, array $filters): array
    {
        $scope = $this->fileRepository->getScope($workspace);
        $files = $this->fileRepository->filterFiles($scope, $filters);
        $files->resetDQLPart('orderBy');
        $files
            ->select('f.mime, count(f.mime) as c')
            ->groupBy('f.mime');
        $rawMimeTypes = $files->getQuery()->setQueryCacheLifetime(180)->getResult();
        $mimeTypes = [];
        if (is_array($rawMimeTypes)) {
            foreach ($rawMimeTypes as $rawMimeType) {
                if (is_array($rawMimeType) && array_key_exists('mime', $rawMimeType)) {
                    $mimeType = MimeType::tryFrom($rawMimeType['mime']);
                    if ($mimeType instanceof MimeType) {
                        $mimeTypes[] = $mimeType;
                    }
                }
            }
        }

        return $mimeTypes;
    }

    /**
     * @param AbstractFileFilter[] $filters
     *
     * @return FilterMonth[]
     */
    private function queryFilteredMonths(Workspace $workspace, array $filters): array
    {
        $scope = $this->fileRepository->getScope($workspace);
        $files = $this->fileRepository->filterFiles($scope, $filters);
        $files->resetDQLPart('orderBy');
        $files
            ->orderBy('yearMonth', 'DESC')
            ->select(['DATE_FORMAT(f.createdAt, \'%Y-%m\') as yearMonth'])
            ->groupBy('yearMonth');
        $rawYearMonths = $files->getQuery()->setQueryCacheLifetime(600)->getResult();
        if (!is_array($rawYearMonths)) {
            return [];
        }
        $months = [];

        foreach ($rawYearMonths as $rawYearMonth) {
            $dateString = $rawYearMonth['yearMonth'];
            $dateRange = DateTimeFormatUtil::parseDateString($dateString);
            if ($dateRange instanceof DateRange && $dateRange->getDateTime() instanceof \DateTimeInterface) {
                $months[] = new FilterMonth(
                    $dateString,
                    $dateRange->getDateTime()
                );
            }
        }

        return $months;
    }

    /**
     * @throws FileHandlerException
     */
    public function getNextFileUrl(File $file, ?Request $request = null, bool $previous = false): ?string
    {
        $workspace = $file->getWorkspace();
        if (!$workspace instanceof Workspace) {
            throw new \UnexpectedValueException();
        }

        $contextString = $request?->get('context');
        if (!is_string($contextString)) {
            $contextString = '/';
        }
        $cs = explode('?', $contextString);
        $matched = $this->router->match($cs[0]);

        $queryParams = [];
        if (count($cs) > 1) {
            parse_str($cs[1], $queryParams);
            if (array_key_exists('page', $queryParams)) {
                unset($queryParams['page']);
            }
            if (array_key_exists('paginator', $queryParams)) {
                unset($queryParams['paginator']);
            }
        }

        $sortFilterPaginateArguments = RequestArgumentHelper::extractArguments(
            queryParams: $queryParams,
            defaultValues: new DefaultRequestValues(
                page: 1,
                limit: 1
            )
        );

        $contextAction = array_key_exists('_route', $matched) && is_string($matched['_route']) ? $matched['_route'] : 'workspace_index';

        $user = ContextHelper::needsUser($contextAction) ? $this->security->getUser() : null;

        $collection = null;
        if (ContextHelper::needsCollection($contextAction) && array_key_exists('slug', $matched)) {
            $collection = $this->collectionHandler->getCollectionBySlug($matched['slug'], $workspace);
        }

        $category = null;
        if (ContextHelper::needsCategory($contextAction) && array_key_exists('slug', $matched)) {
            $category = $this->categoryHandler->getCategoryBySlug($matched['slug'], $workspace);
        }

        $filters = $this->getFilters(
            additionalFilters: ContextHelper::getContextFilters(
                route: $contextAction,
                user: $user,
                collection: $collection,
                category: $category
            ),
            sortFilterPaginateArguments: $sortFilterPaginateArguments,
            workspace: $workspace
        );

        $sortParam = $sortFilterPaginateArguments->getSort();
        if ($previous) {
            $sortParam = $sortParam->swap();
        }
        $orderBy = $this->getOrderBy([$sortParam]);

        $scope = $this->fileRepository->getScope($workspace);
        $files = $this->fileRepository->filterFiles($scope, $filters);
        $nextFile = $this->fileRepository->getNextFile($files, $file, $orderBy);
        $query = $this->fileRepository->sortFiles($nextFile, $orderBy);
        $result = $query->getResult();

        if (is_array($result) && [] !== $result) {
            $parameters = [
                'filename' => $result[0]->getFilename(),
            ];
            if (is_string($contextString) && '' !== trim($contextString) && '/' !== trim($contextString)) {
                $parameters['context'] = $contextString;
            }

            return $this->router->generate(
                'workspace_file_edit',
                $parameters
            );
        }

        return null;
    }

    /**
     * @throws FileHandlerException
     */
    public function getPreviousFileUrl(File $file, ?Request $request = null): ?string
    {
        return $this->getNextFileUrl($file, $request, true);
    }
}
