<?php

declare(strict_types=1);

namespace App\Dto\Api\Rest;

use App\Dto\AbstractDto;
use App\Dto\Api\Asset;
use OpenApi\Attributes as OA;

#[OA\Schema(
    type: 'object'
)]
class AssetsResponse extends AbstractDto
{
    /** @var Asset[] */
    protected array $assets = [];

    public function __construct(
        #[OA\Property(
            example: 1
        )]
        protected int $page,
        #[OA\Property(
            example: 1
        )]
        protected int $pages,
        #[OA\Property(
            example: 0
        )]
        protected int $total
    ) {
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function setPage(int $page): AssetsResponse
    {
        $this->page = $page;

        return $this;
    }

    public function getPages(): int
    {
        return $this->pages;
    }

    public function setPages(int $pages): AssetsResponse
    {
        $this->pages = $pages;

        return $this;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function setTotal(int $total): AssetsResponse
    {
        $this->total = $total;

        return $this;
    }

    /**
     * @return Asset[]
     */
    public function getAssets(): array
    {
        return $this->assets;
    }

    public function addAsset(Asset $asset): self
    {
        if (!in_array($asset, $this->assets, true)) {
            $this->assets[] = $asset;
        }

        return $this;
    }

    /**
     * @param Asset[] $assets
     */
    public function setAssets(array $assets): AssetsResponse
    {
        $this->assets = $assets;

        return $this;
    }
}
