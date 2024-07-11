<?php

namespace App\Util;

use App\Util\Mapper\Paginator\ItemMapperException;
use App\Util\Mapper\Paginator\ItemMapperInterface;
use App\Util\Paginator\PaginatorException;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator as OrmPaginator;

class Paginator
{
    private int $total;
    private int $pages;
    private int $limit;
    private int $page;

    /**
     * @var array<mixed>
     *
     * @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection
     */
    private array $items = [];

    /**
     * @throws PaginatorException
     */
    public function paginate(Query $query, int $page = 1, int $limit = 30): self
    {
        $this->page = $page;
        $this->limit = $limit;
        $paginator = new OrmPaginator($query);

        $paginator
            ->getQuery()
            ->setFirstResult($limit * ($page - 1))
            ->setMaxResults($limit);

        $this->total = $paginator->count();
        $this->pages = (int) ceil($paginator->count() / $paginator->getQuery()->getMaxResults());
        $result = $paginator->getQuery()->getResult();
        if (!is_array($result)) {
            throw new PaginatorException();
        }
        $this->items = (array) $paginator->getQuery()->getResult();

        return $this;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getPages(): int
    {
        return $this->pages;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @return mixed[]
     *
     * @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @throws ItemMapperException
     */
    public function mapItems(ItemMapperInterface $mapper): void
    {
        $mappedItems = [];
        foreach ($this->items as $item) {
            $mappedItems[] = $mapper->map($item);
        }
        $this->items = $mappedItems;
    }

    public function getPage(): int
    {
        return $this->page;
    }
}
