<?php

declare(strict_types=1);

namespace App\Service\File\Filter;

use App\Entity\AssetCollection;
use App\Entity\FileAssetCollection;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

class CollectionFilter extends AbstractFileFilter
{
    public function __construct(
        private readonly AssetCollection $assetCollection
    ) {
    }

    public function filter(QueryBuilder $qb): QueryBuilder
    {
        $qb
            ->join(
                join: FileAssetCollection::class,
                alias: 'fac',
                conditionType: Join::WITH,
                condition: 'fac.file = f.id'
            )
            ->andWhere('fac.assetCollection = :assetcollection')
            ->setParameter('assetcollection', $this->assetCollection);

        return $qb;
    }
}
