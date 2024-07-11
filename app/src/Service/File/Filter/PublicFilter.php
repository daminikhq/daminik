<?php

declare(strict_types=1);

namespace App\Service\File\Filter;

use App\Enum\Visibility;
use Doctrine\ORM\QueryBuilder;

class PublicFilter extends AbstractFileFilter
{
    public function __construct(
        private readonly Visibility $visibility
    ) {
    }

    public function filter(QueryBuilder $qb): QueryBuilder
    {
        return match ($this->visibility) {
            Visibility::ALL => $qb,
            Visibility::PUBLIC => $this->public($qb, true),
            Visibility::PRIVATE => $this->public($qb, false),
        };
    }

    private function public(QueryBuilder $qb, bool $public): QueryBuilder
    {
        $qb->andWhere('f.public = :public')
            ->setParameter('public', $public);

        return $qb;
    }
}
