<?php

declare(strict_types=1);

namespace App\Dto\Utility;

use App\Dto\Filter\BooleanFilter;
use App\Enum\SortParam;
use App\Enum\ViewParam;

readonly class SortFilterPaginateArguments
{
    /**
     * @param BooleanFilter[] $mimeTypes
     * @param string[]        $tags
     * @param string[]        $uploadedBy
     */
    public function __construct(
        private SortParam $sort,
        private int $page = 1,
        private int $limit = 30,
        private ?ViewParam $view = null,
        private ?string $search = null,
        private array $mimeTypes = [],
        private array $tags = [],
        private array $uploadedBy = [],
        private ?string $uploadedAt = null,
        private bool $paginator = false,
    ) {
    }

    public function getSort(): SortParam
    {
        return $this->sort;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getView(): ?ViewParam
    {
        return $this->view;
    }

    public function getSearch(): ?string
    {
        return $this->search;
    }

    /**
     * @return BooleanFilter[]
     */
    public function getMimeTypes(): array
    {
        return $this->mimeTypes;
    }

    /**
     * @return string[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @return string[]
     */
    public function getUploadedBy(): array
    {
        return $this->uploadedBy;
    }

    public function getUploadedAt(): ?string
    {
        return $this->uploadedAt;
    }

    public function isPaginator(): bool
    {
        return $this->paginator;
    }

    /**
     * @return array{
     *     page: int,
     *     view: ViewParam|null,
     *     mimeTypes: array<BooleanFilter>,
     *     s: string|null,
     *     tags: array<string>,
     *     uploadedby: array<string>,
     *     uploadedat: string|null,
     *     paginator: string|null
     *         }
     */
    public function asViewParameters(): array
    {
        return [
            'page' => $this->page,
            'view' => $this->view,
            'mimeTypes' => $this->mimeTypes,
            's' => $this->search,
            'tags' => $this->tags,
            'uploadedby' => $this->uploadedBy,
            'uploadedat' => $this->uploadedAt,
            'paginator' => $this->paginator ? '1' : null,
        ];
    }
}
