<?php

namespace App\Repository;

use App\Entity\AssetCollection;
use App\Entity\Workspace;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AssetCollection>
 *
 * @method AssetCollection|null find($id, $lockMode = null, $lockVersion = null)
 * @method AssetCollection|null findOneBy(array $criteria, array $orderBy = null)
 * @method AssetCollection[]    findAll()
 * @method AssetCollection[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AssetCollectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AssetCollection::class);
    }

    public function getWorkspaceQueryBuilder(Workspace $workspace): QueryBuilder
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.workspace = :workspace')
            ->setParameter('workspace', $workspace);
    }

    /**
     * @return AssetCollection[]
     */
    public function findTagsForAutocomplete(Workspace $workspace, ?string $query, int $limit): array
    {
        $qb = $this->createQueryBuilder('c')
            ->select('c, COUNT(fileAssetCollections.id) as filecount')
            ->leftJoin('c.fileAssetCollections', 'fileAssetCollections')
            ->andWhere('c.workspace = :workspace')
            ->setParameter('workspace', $workspace)
            ->setMaxResults($limit)
            ->groupBy('c')
            ->addOrderBy('filecount', 'DESC')
            ->addOrderBy('c.createdAt', 'DESC');
        if (null !== $query) {
            $query = strtolower($query);
            $qb->andWhere('c.slug LIKE :query')
                ->setParameter('query', '%'.$query.'%');
        }
        $result = $qb
            ->getQuery()
            ->getResult();

        if (!is_array($result)) {
            return [];
        }
        $tags = [];
        foreach ($result as $singleResult) {
            if (is_array($singleResult) && array_key_exists(0, $singleResult)) {
                $tags[] = $singleResult[0];
            }
        }

        return $tags;
    }
}
