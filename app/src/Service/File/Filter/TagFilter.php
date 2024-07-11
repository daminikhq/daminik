<?php

declare(strict_types=1);

namespace App\Service\File\Filter;

use App\Entity\FileTag;
use App\Entity\Tag;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

class TagFilter extends AbstractFileFilter
{
    /**
     * @param Tag[] $tags
     */
    public function __construct(
        private readonly array $tags
    ) {
    }

    public function filter(QueryBuilder $qb): QueryBuilder
    {
        if (count($this->tags) < 1) {
            return $qb;
        }
        $qb
            ->join(
                join: FileTag::class,
                alias: 'ft',
                conditionType: Join::WITH,
                condition: 'ft.file = f.id'
            );
        $orStatements = $qb->expr()->orX();
        $c = 0;
        foreach ($this->tags as $tag) {
            $key = 'tag'.$c;
            $orStatements->add($qb->expr()->andX('ft.tag = :'.$key));
            $qb->setParameter($key, $tag);
            ++$c;
        }
        $qb->andWhere($orStatements);

        return $qb;
    }
}
