<?php

namespace App\Repository;

use App\Entity\FileSystem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FileSystem>
 *
 * @method FileSystem|null find($id, $lockMode = null, $lockVersion = null)
 * @method FileSystem|null findOneBy(array $criteria, array $orderBy = null)
 * @method FileSystem[]    findAll()
 * @method FileSystem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FileSystemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FileSystem::class);
    }
}
