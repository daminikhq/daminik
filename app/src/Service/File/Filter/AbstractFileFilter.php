<?php

/** @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection */
declare(strict_types=1);

namespace App\Service\File\Filter;

use Doctrine\ORM\QueryBuilder;

abstract class AbstractFileFilter
{
    abstract public function filter(QueryBuilder $qb): QueryBuilder;

    /**
     * @var mixed[]
     */
    protected array $criteria = [];

    /**
     * @param mixed[] $criteria
     */
    protected function addCriteria(array $criteria): void
    {
        if (count($criteria) > 2) {
            $this->criteria[] = ['key' => $criteria[0], 'operator' => $criteria[1], 'value' => $criteria[2]];
        } else {
            $this->criteria[] = ['key' => $criteria[0], 'operator' => $criteria[1]];
        }
    }
}
