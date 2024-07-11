<?php

/** @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection */
declare(strict_types=1);

namespace App\Service\File\Filter;

use App\Exception\FileHandlerException;
use Doctrine\ORM\QueryBuilder;

/**
 * ChoiceFilter, Criteria are linked by OR-Logic Gates.
 */
class ChoiceFilter extends AbstractFileFilter
{
    /**
     * @param mixed[] $args
     *
     * @throws FileHandlerException
     */
    protected function resolveArgs(array $args): void
    {
        if (is_array($args[0])) {
            foreach ($args as $filterParameter) {
                if (!is_array($filterParameter)) {
                    throw new FileHandlerException('');
                }

                $this->addCriteria($filterParameter);
            }
        } else {
            $this->addCriteria($args);
        }
    }

    /**
     * @param mixed[] $args
     *
     * @throws FileHandlerException
     */
    public function __construct(array $args)
    {
        $this->resolveArgs($args);
    }

    public function filter(QueryBuilder $qb): QueryBuilder
    {
        $orStatements = $qb->expr()->orX();
        $c = $qb->getParameters()->count();

        foreach ($this->criteria as $criteria) {
            $key = ':s'.$c;
            $criteria = (object) $criteria;
            $operator = $criteria->operator;

            if (property_exists($criteria, 'value')) {
                $criteria->value ??= 'null';
                $orStatements->add($qb->expr()->$operator('f.'.$criteria->key, $key));
                $qb->setParameter($key, $criteria->value);
            } else {
                $orStatements->add($qb->expr()->$operator('f.'.$criteria->key));
            }

            ++$c;
        }

        $qb->andWhere($orStatements);

        return $qb;
    }
}
