<?php

namespace App\Repository;

use App\Entity\RegistrationCode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RegistrationCode>
 */
class RegistrationCodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RegistrationCode::class);
    }

    //    /**
    //     * @return RegistrationCode[] Returns an array of RegistrationCode objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('r.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?RegistrationCode
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /**
     * @return RegistrationCode[]
     */
    public function findActiveCodes(): array
    {
        $now = new \DateTimeImmutable();
        $result = $this->createQueryBuilder('r')
            ->andWhere('r.validUntil IS NULL OR r.validUntil > :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();
        if (is_array($result)) {
            return $result;
        }

        return [];
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findValidCode(string $code): ?RegistrationCode
    {
        $now = new \DateTimeImmutable();
        $result = $this->createQueryBuilder('r')
            ->andWhere('r.validUntil IS NULL OR r.validUntil > :now')
            ->andWhere('r.code = :code')
            ->setParameter('code', $code)
            ->setParameter('now', $now)
            ->getQuery()
            ->getOneOrNullResult();
        if ($result instanceof RegistrationCode) {
            return $result;
        }

        return null;
    }
}
