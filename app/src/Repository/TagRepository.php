<?php

namespace App\Repository;

use App\Entity\Tag;
use App\Entity\Workspace;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tag>
 *
 * @method Tag|null find($id, $lockMode = null, $lockVersion = null)
 * @method Tag|null findOneBy(array $criteria, array $orderBy = null)
 * @method Tag[]    findAll()
 * @method Tag[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tag::class);
    }

    /**
     * @return Tag[]
     */
    public function findForAutocomplete(Workspace $workspace, ?string $query, int $limit): array
    {
        $qb = $this->createQueryBuilder('tag')
            ->select('tag, COUNT(fileTags.id) as filecount')
            ->leftJoin('tag.fileTags', 'fileTags')
            ->andWhere('tag.workspace = :workspace')
            ->setParameter('workspace', $workspace)
            ->setMaxResults($limit)
            ->groupBy('tag')
            ->addOrderBy('filecount', 'DESC')
            ->addOrderBy('tag.createdAt', 'DESC');
        if (null !== $query) {
            $query = strtolower($query);
            $qb->andWhere('tag.slug LIKE :query')
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
