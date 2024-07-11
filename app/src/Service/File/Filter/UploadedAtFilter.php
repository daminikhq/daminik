<?php

declare(strict_types=1);

namespace App\Service\File\Filter;

use App\Dto\Utility\DateRange;
use App\Util\DateTimeFormatUtil;
use Doctrine\ORM\QueryBuilder;

class UploadedAtFilter extends AbstractFileFilter
{
    public function __construct(private readonly string $dateString)
    {
    }

    public function filter(QueryBuilder $qb): QueryBuilder
    {
        try {
            $parsedDates = DateTimeFormatUtil::parseDateString($this->dateString);
        } catch (\Throwable) {
            return $qb;
        }
        if (!$parsedDates instanceof DateRange) {
            return $qb;
        }

        if ($parsedDates->getAfter() instanceof \DateTimeInterface) {
            $qb
                ->andWhere('f.createdAt > :after')
                ->setParameter('after', $parsedDates->getAfter());
        }
        if ($parsedDates->getBefore() instanceof \DateTimeInterface) {
            $qb
                ->andWhere('f.createdAt < :before')
                ->setParameter('before', $parsedDates->getBefore());
        }

        return $qb;
    }
}
