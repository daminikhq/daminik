<?php

declare(strict_types=1);

namespace App\Dto\Utility;

use App\Enum\SortParam;

readonly class DefaultRequestValues
{
    public function __construct(
        private string $sort = SortParam::UPLOADED_DESC->value,
        private int $page = 1,
        private int $limit = 30,
        private ?string $view = null,
        private ?string $tags = null
    ) {
    }

    public function getSort(): string
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

    public function getView(): ?string
    {
        return $this->view;
    }

    public function getTags(): ?string
    {
        return $this->tags;
    }
}
