<?php

namespace App\Repository;

use App\Entity\FileUserMetaData;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FileUserMetaData>
 *
 * @method FileUserMetaData|null find($id, $lockMode = null, $lockVersion = null)
 * @method FileUserMetaData|null findOneBy(array $criteria, array $orderBy = null)
 * @method FileUserMetaData[]    findAll()
 * @method FileUserMetaData[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FileUserMetaDataRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FileUserMetaData::class);
    }
}
