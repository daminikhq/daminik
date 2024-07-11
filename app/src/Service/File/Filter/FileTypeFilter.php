<?php

declare(strict_types=1);

namespace App\Service\File\Filter;

use App\Enum\FileType;
use Doctrine\ORM\QueryBuilder;

class FileTypeFilter extends AbstractFileFilter
{
    public function __construct(
        private readonly FileType $fileType
    ) {
    }

    public function filter(QueryBuilder $qb): QueryBuilder
    {
        $qb->andWhere('f.type = :type')
            ->setParameter('type', $this->fileType->value);

        return $qb;
    }
}
