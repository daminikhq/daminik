<?php

declare(strict_types=1);

namespace App\Dto\Filter;

class SearchFormDto
{
    public function __construct(
        protected ?string $s = null,
        protected ?string $parameters = null,
        protected string $route = 'workspace_index',
        protected ?string $slug = null,
    ) {
    }

    public function getS(): ?string
    {
        return $this->s;
    }

    public function getParameters(): ?string
    {
        return $this->parameters;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }
}
