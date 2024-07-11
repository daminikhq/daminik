<?php

namespace App\Repository;

use App\Entity\FileTag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FileTag>
 *
 * @method FileTag|null find($id, $lockMode = null, $lockVersion = null)
 * @method FileTag|null findOneBy(array $criteria, array $orderBy = null)
 * @method FileTag[]    findAll()
 * @method FileTag[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FileTagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FileTag::class);
    }
}
