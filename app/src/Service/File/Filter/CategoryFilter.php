<?php

declare(strict_types=1);

namespace App\Service\File\Filter;

use App\Entity\Category;
use App\Entity\FileCategory;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

class CategoryFilter extends AbstractFileFilter
{
    public function __construct(
        private readonly Category $category
    ) {
    }

    public function filter(QueryBuilder $qb): QueryBuilder
    {
        $qb
            ->join(
                join: FileCategory::class,
                alias: 'fc',
                conditionType: Join::WITH,
                condition: 'fc.file = f.id'
            )
            ->andWhere('fc.category = :category')
            ->setParameter('category', $this->category);

        return $qb;
    }
}
