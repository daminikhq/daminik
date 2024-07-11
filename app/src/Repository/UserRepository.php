<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Workspace;
use App\Enum\SortParam;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\OrderBy;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
    }

    /**
     * @return User[]
     *
     * @noinspection PhpUnused
     */
    public function findWorkspaceUsers(Workspace $workspace): array
    {
        $qb = $this->createQueryBuilder('u');
        $qb->join('u.memberships', 'm')
            ->andWhere('m.workspace = :workspace')
            ->setParameter('workspace', $workspace)
            ->orderBy('u.username', 'ASC');

        $query = $qb->getQuery();
        $result = $query->getResult();
        if (is_array($result)) {
            return $result;
        }

        return [];
    }

    /**
     * @param int[] $ids
     *
     * @return User[]
     */
    public function findForAutocomplete(array $ids, ?string $query, int $limit): array
    {
        if (count($ids) < 1) {
            return [];
        }
        $qb = $this->createQueryBuilder('u');
        $qb->andWhere('u.id in (:ids)')
            ->setParameter('ids', $ids);
        if ($limit > 0) {
            $qb->setMaxResults($limit);
        }
        if (null !== $query) {
            $query = strtolower($query);
            $qb->andWhere('u.username LIKE :query')
                ->setParameter('query', '%'.$query.'%');
        }
        $result = $qb->getQuery()->getResult();
        if (!is_array($result)) {
            return [];
        }

        return $result;
    }

    public function getUserQuery(SortParam $sortParam): Query
    {
        $qb = $this->createQueryBuilder('u')
            ->orderBy($this->getOrderBy($sortParam));

        return $qb->getQuery();
    }

    private function getOrderBy(SortParam $sortParam): OrderBy
    {
        return match ($sortParam) {
            SortParam::UPLOADED_ASC => new OrderBy('u.createdAt', 'ASC'),
            SortParam::UPLOADED_DESC => new OrderBy('u.createdAt', 'DESC'),
            SortParam::MODIFIED_ASC => new OrderBy('u.updatedAt', 'ASC'),
            SortParam::MODIFIED_DESC => new OrderBy('u.updatedAt', 'DESC'),
        };
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findWorkspaceRobot(Workspace $workspace): ?User
    {
        $qb = $this->createQueryBuilder('u');
        $qb->join('u.memberships', 'm')
            ->andWhere('m.workspace = :workspace')
            ->andWhere('u.bot = :bot')
            ->setParameter('bot', true)
            ->setParameter('workspace', $workspace);

        $query = $qb->getQuery();
        $result = $query->getOneOrNullResult();
        if ($result instanceof User) {
            return $result;
        }

        return null;
    }
}
