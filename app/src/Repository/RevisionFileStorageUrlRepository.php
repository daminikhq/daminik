<?php

namespace App\Repository;

use App\Entity\RevisionFileStorageUrl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RevisionFileStorageUrl>
 *
 * @method RevisionFileStorageUrl|null find($id, $lockMode = null, $lockVersion = null)
 * @method RevisionFileStorageUrl|null findOneBy(array $criteria, array $orderBy = null)
 * @method RevisionFileStorageUrl[]    findAll()
 * @method RevisionFileStorageUrl[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RevisionFileStorageUrlRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RevisionFileStorageUrl::class);
    }
}
