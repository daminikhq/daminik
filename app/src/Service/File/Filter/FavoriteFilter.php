<?php

declare(strict_types=1);

namespace App\Service\File\Filter;

use App\Entity\FileUserMetaData;
use App\Entity\User;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

class FavoriteFilter extends AbstractFileFilter
{
    /** @noinspection AutowireWrongClass */
    public function __construct(
        private readonly User $user
    ) {
    }

    public function filter(QueryBuilder $qb): QueryBuilder
    {
        $qb
            ->join(
                join: FileUserMetaData::class,
                alias: 'fmo',
                conditionType: Join::WITH,
                condition: 'fmo.file = f.id'
            )->andWhere('fmo.user = :fmouser')
            ->andWhere('fmo.favorite = :fmofavorite')
            ->setParameter('fmofavorite', true)
            ->setParameter('fmouser', $this->user);

        return $qb;
    }
}
