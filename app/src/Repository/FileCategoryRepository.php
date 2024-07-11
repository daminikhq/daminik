<?php

namespace App\Repository;

use App\Entity\FileCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FileCategory>
 *
 * @method FileCategory|null find($id, $lockMode = null, $lockVersion = null)
 * @method FileCategory|null findOneBy(array $criteria, array $orderBy = null)
 * @method FileCategory[]    findAll()
 * @method FileCategory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FileCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FileCategory::class);
    }
}
