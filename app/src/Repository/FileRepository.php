<?php

namespace App\Repository;

use App\Entity\AssetCollection;
use App\Entity\File;
use App\Entity\User;
use App\Entity\Workspace;
use App\Enum\FileType;
use App\Service\File\Filter\AbstractFileFilter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<File>
 *
 * @method File|null find($id, $lockMode = null, $lockVersion = null)
 * @method File|null findOneBy(array $criteria, array $orderBy = null)
 * @method File[]    findAll()
 * @method File[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, File::class);
    }

    /**
     * @return File[]
     */
    public function findDeletedBefore(\DateTimeInterface $before): array
    {
        $qb = $this->createQueryBuilder('f')
            ->andWhere('f.deletedAt IS NOT NULL')
            ->andWhere('f.deletedAt < :before')
            ->setParameter('before', $before);

        $result = $qb->getQuery()->getResult();
        if (is_array($result)) {
            return $result;
        }

        return [];
    }

    /**
     * @param array<AbstractFileFilter> $filters
     */
    public function filterFiles(QueryBuilder $scope, array $filters): QueryBuilder
    {
        foreach ($filters as $fileFilter) {
            $scope = $fileFilter->filter($scope);
        }

        return $scope;
    }

    /**
     * @param array<string,string> $sortParam
     */
    public function sortFiles(QueryBuilder $scope, array $sortParam): Query
    {
        foreach ($sortParam as $orderBy => $direction) {
            $scope->orderBy('f.'.$orderBy, $direction);
        }

        return $scope->getQuery();
    }

    public function getScope(?Workspace $workspace = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('f');
        if ($workspace instanceof Workspace) {
            $qb->andWhere('f.workspace = :workspace')
                ->setParameter('workspace', $workspace);
        }

        return $qb;
    }

    public function getCollectionQuery(AssetCollection $collection): Query
    {
        $qb = $this->createQueryBuilder('f');
        $qb->join('f.fileAssetCollections', 'ac')
            ->andWhere('ac.assetCollection = :collection')
            ->setParameter('collection', $collection)
            ->orderBy('ac.addedAt', 'DESC');

        return $qb->getQuery();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findAvatar(User $user, Workspace $workspace): ?File
    {
        $avatar = $this->createQueryBuilder('f')
            ->andWhere('f.uploader = :user')
            ->andWhere('f.workspace = :workspace')
            ->andWhere('f.type = :type')
            ->andWhere('f.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->setParameter('workspace', $workspace)
            ->setParameter('type', FileType::AVATAR->value)
            ->getQuery()
            ->getOneOrNullResult();
        if ($avatar instanceof File) {
            return $avatar;
        }

        return null;
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findWithSlug(Workspace $workspace, string $filenameSlug): ?File
    {
        $qb = $this->createQueryBuilder('f');
        $file = $qb
            ->andWhere('f.workspace = :workspace')
            ->andWhere('f.filenameSlug = :slug OR f.publicFilenameSlug = :slug')
            ->setParameter('workspace', $workspace)
            ->setParameter('slug', $filenameSlug)
            ->getQuery()
            ->getOneOrNullResult();
        if ($file instanceof File) {
            return $file;
        }

        return null;
    }

    /**
     * @return array<int, array{uploader: int, filecount: int}>
     */
    public function findForUserAutocomplete(Workspace $workspace): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->addSelect(['identity(f.uploader) uploader', 'count(f.id) as filecount'])
            ->from(File::class, 'f');
        $qb->andWhere('f.workspace = :workspace')
            ->setParameter('workspace', $workspace)
            ->addOrderBy('filecount', 'DESC')
            ->groupBy('f.uploader');

        $result = $qb->getQuery()->getResult();
        if (!is_array($result)) {
            return [];
        }

        return $result;
    }

    /**
     * @param array<string,string> $sortParam
     */
    public function getNextFile(QueryBuilder $scope, File $file, array $sortParam): QueryBuilder
    {
        $scope->setMaxResults(1);
        $keys = array_keys($sortParam);
        if (count($keys) < 1) {
            return $scope;
        }
        $order = 'ASC' === $sortParam[$keys[0]] ? '>' : '<';
        $scope->andWhere('f.createdAt '.$order.' :filecreatedAt')
            ->setParameter('filecreatedAt', $file->getCreatedAt());

        return $scope;
    }
}
