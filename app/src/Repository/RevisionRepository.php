<?php

namespace App\Repository;

use App\Entity\Revision;
use App\Entity\User;
use App\Entity\Workspace;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Revision>
 *
 * @method Revision|null find($id, $lockMode = null, $lockVersion = null)
 * @method Revision|null findOneBy(array $criteria, array $orderBy = null)
 * @method Revision[]    findAll()
 * @method Revision[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RevisionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Revision::class);
    }

    /**
     * @return Revision[]
     */
    public function findRevisionsWithoutSizes(): array
    {
        $result = $this->createQueryBuilder('r')
            ->orWhere('r.width IS NULL')
            ->orWhere('r.height IS NULL')
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
        if (!is_array($result)) {
            return [];
        }

        return $result;
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getFilesizeByUploader(User $uploader): int
    {
        $qb = $this->createQueryBuilder('r')
            ->select('SUM(r.fileSize) AS filesizesum')
            ->join('r.file', 'f')
            ->andWhere('f.deletedAt IS NULL')
            ->andWhere('r.uploader = :uploader')
            ->setParameter('uploader', $uploader);
        $query = $qb->getQuery();
        $totalQuantity = $query->getSingleScalarResult();
        if (is_int($totalQuantity)) {
            return $totalQuantity;
        }
        if (is_string($totalQuantity) || is_float($totalQuantity)) {
            return (int) $totalQuantity;
        }

        return 0;
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getFilesizeByWorkspace(Workspace $workspace): int
    {
        $qb = $this->createQueryBuilder('r')
            ->select('SUM(r.fileSize) AS filesizesum')
            ->join('r.file', 'f')
            ->andWhere('f.deletedAt IS NULL')
            ->andWhere('f.workspace = :workspace')
            ->setParameter('workspace', $workspace);
        $query = $qb->getQuery();
        $totalQuantity = $query->getSingleScalarResult();
        if (is_int($totalQuantity)) {
            return $totalQuantity;
        }
        if (is_string($totalQuantity) || is_float($totalQuantity)) {
            return (int) $totalQuantity;
        }

        return 0;
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getFilesizeByUserAndWorkspace(User $uploader, Workspace $workspace): int
    {
        $qb = $this->createQueryBuilder('r')
            ->select('SUM(r.fileSize) AS filesizesum')
            ->join('r.file', 'f')
            ->andWhere('f.deletedAt IS NULL')
            ->andWhere('r.uploader = :uploader')
            ->andWhere('f.workspace = :workspace')
            ->setParameter('uploader', $uploader)
            ->setParameter('workspace', $workspace);
        $query = $qb->getQuery();
        $totalQuantity = $query->getSingleScalarResult();
        if (is_int($totalQuantity)) {
            return $totalQuantity;
        }
        if (is_string($totalQuantity) || is_float($totalQuantity)) {
            return (int) $totalQuantity;
        }

        return 0;
    }
}
