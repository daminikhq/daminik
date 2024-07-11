<?php

declare(strict_types=1);

namespace App\Service\File\Filter;

use Doctrine\ORM\QueryBuilder;

class SearchFilter extends AbstractFileFilter
{
    public function __construct(string $search)
    {
        $this->addCriteria([$search]);
    }

    /**
     * @param string[] $criteria
     */
    protected function addCriteria(array $criteria): void
    {
        $this->criteria = $criteria;
    }

    public function filter(QueryBuilder $qb): QueryBuilder
    {
        $searches = [];
        foreach ($this->criteria as $search) {
            if (is_string($search)) {
                $searches += array_filter(explode(' ', trim($search)));
            }
        }

        $orStatements = $qb->expr()->orX();
        $c = 0;
        foreach ($searches as $search) {
            $key = ':s'.$c;
            $orStatements->add($qb->expr()->like('f.title', $key));
            $orStatements->add($qb->expr()->like('f.description', $key));
            $orStatements->add($qb->expr()->like('f.filename', $key));
            $orStatements->add($qb->expr()->like('searchFileTags.title', $key));
            $orStatements->add($qb->expr()->like('searchTag.title', $key));
            $orStatements->add($qb->expr()->like('searchTag.slug', $key));
            $orStatements->add($qb->expr()->like('searchCategory.title', $key));
            $orStatements->add($qb->expr()->like('searchCategory.slug', $key));
            $qb->setParameter($key, '%'.$search.'%');
            ++$c;
        }
        $qb
            ->andWhere($orStatements)
            ->leftJoin('f.fileTags', 'searchFileTags')
            ->leftJoin('searchFileTags.tag', 'searchTag')
            ->leftJoin('f.fileCategories', 'searchFileCategories')
            ->leftJoin('searchFileCategories.category', 'searchCategory')
            ->orderBy('f.createdAt', 'DESC');

        return $qb;
    }
}
