<?php

declare(strict_types=1);

namespace App\Service\File\Filter;

use App\Entity\User;
use Doctrine\ORM\QueryBuilder;

class UploadedByFilter extends AbstractFileFilter
{
    /**
     * @param User[] $uploaders
     */
    public function __construct(
        private readonly array $uploaders
    ) {
    }

    public function filter(QueryBuilder $qb): QueryBuilder
    {
        if (count($this->uploaders) < 1) {
            return $qb;
        }
        $orStatements = $qb->expr()->orX();
        $c = 0;
        foreach ($this->uploaders as $uploader) {
            $key = 'uploader'.$c;
            $orStatements->add($qb->expr()->andX('f.uploader = :'.$key));
            $qb->setParameter($key, $uploader);
            ++$c;
        }
        $qb->andWhere($orStatements);

        return $qb;
    }
}
