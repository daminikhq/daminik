<?php

namespace App\Repository;

use App\Entity\FileAssetCollection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FileAssetCollection>
 *
 * @method FileAssetCollection|null find($id, $lockMode = null, $lockVersion = null)
 * @method FileAssetCollection|null findOneBy(array $criteria, array $orderBy = null)
 * @method FileAssetCollection[]    findAll()
 * @method FileAssetCollection[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FileAssetCollectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FileAssetCollection::class);
    }

    //    /**
    //     * @return FileAssetCollection[] Returns an array of FileAssetCollection objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('f.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?FileAssetCollection
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
