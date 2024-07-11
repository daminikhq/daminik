<?php

namespace App\Repository;

use App\Entity\Membership;
use App\Entity\Workspace;
use App\Enum\MembershipStatus;
use App\Enum\SortParam;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\OrderBy;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Membership>
 *
 * @method Membership|null find($id, $lockMode = null, $lockVersion = null)
 * @method Membership|null findOneBy(array $criteria, array $orderBy = null)
 * @method Membership[]    findAll()
 * @method Membership[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MembershipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Membership::class);
    }

    public function getMembershipQuery(
        Workspace $workspace,
        SortParam $sortParam,
        bool $hideRobots = true,
    ): Query {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.workspace = :workspace')
            ->setParameter('workspace', $workspace)
            ->orderBy($this->getOrderBy($sortParam));

        if ($hideRobots) {
            $qb->andWhere('m.status != :robot')
                ->setParameter('robot', MembershipStatus::ROBOT->value);
        }

        return $qb->getQuery();
    }

    private function getOrderBy(SortParam $sortParam): OrderBy
    {
        return match ($sortParam) {
            SortParam::UPLOADED_ASC => new OrderBy('m.createdAt', 'ASC'),
            SortParam::UPLOADED_DESC => new OrderBy('m.createdAt', 'DESC'),
            SortParam::MODIFIED_ASC => new OrderBy('m.updatedAt', 'ASC'),
            SortParam::MODIFIED_DESC => new OrderBy('m.updatedAt', 'DESC'),
        };
    }
}
