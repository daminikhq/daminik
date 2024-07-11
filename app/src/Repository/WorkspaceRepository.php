<?php

namespace App\Repository;

use App\Entity\Workspace;
use App\Enum\SortParam;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\OrderBy;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Workspace>
 *
 * @method Workspace|null find($id, $lockMode = null, $lockVersion = null)
 * @method Workspace|null findOneBy(array $criteria, array $orderBy = null)
 * @method Workspace[]    findAll()
 * @method Workspace[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WorkspaceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Workspace::class);
    }

    public function getWorkspaceQuery(SortParam $sortParam, bool $withoutGlobal = true): Query
    {
        $qb = $this->createQueryBuilder('w')
            ->orderBy($this->getOrderBy($sortParam));
        if ($withoutGlobal) {
            $qb->andWhere($qb->expr()->neq('w.slug', $qb->expr()->literal('global')));
        }

        return $qb->getQuery();
    }

    private function getOrderBy(SortParam $sortParam): OrderBy
    {
        return match ($sortParam) {
            SortParam::UPLOADED_ASC => new OrderBy('w.createdAt', 'ASC'),
            SortParam::UPLOADED_DESC => new OrderBy('w.createdAt', 'DESC'),
            SortParam::MODIFIED_ASC => new OrderBy('w.updatedAt', 'ASC'),
            SortParam::MODIFIED_DESC => new OrderBy('w.updatedAt', 'DESC'),
        };
    }
}
