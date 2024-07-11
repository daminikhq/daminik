<?php

declare(strict_types=1);

namespace App\Service\Collection;

use App\Dto\Collection\Config;
use App\Dto\Collection\Create;
use App\Dto\Utility\SortFilterPaginateArguments;
use App\Entity\AssetCollection;
use App\Entity\File;
use App\Entity\FileAssetCollection;
use App\Entity\User;
use App\Entity\Workspace;
use App\Enum\SortParam;
use App\Enum\UserAction;
use App\Event\Collection\AssetCollectionConfigChangedEvent;
use App\Event\Collection\FileAssetAddedToCollectionEvent;
use App\Event\Collection\FileAssetRemovedFromCollectionEvent;
use App\Repository\AssetCollectionRepository;
use App\Repository\FileAssetCollectionRepository;
use App\Service\DatabaseLogger\DatabaseLoggerInterface;
use App\Util\Mapper\Paginator\CollectionEntityToDtoMapper;
use App\Util\Mapper\Paginator\ItemMapperException;
use App\Util\Paginator;
use App\Util\Paginator\PaginatorException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

readonly class CollectionHandler implements CollectionHandlerInterface
{
    public function __construct(
        private AssetCollectionRepository $collectionRepository,
        private SluggerInterface $slugger,
        private EntityManagerInterface $entityManager,
        private FileAssetCollectionRepository $fileAssetCollectionRepository,
        private EventDispatcherInterface $dispatcher,
        private DatabaseLoggerInterface $databaseLogger,
        private CollectionEntityToDtoMapper $collectionEntityToDtoMapper,
        private CacheInterface $cache
    ) {
    }

    /**
     * @return AssetCollection[]
     */
    public function getWorkspaceCollections(Workspace $workspace): array
    {
        return $this->collectionRepository->findBy(['workspace' => $workspace], ['createdAt' => 'DESC']);
    }

    public function createCollection(Create $create, Workspace $workspace, User $user, ?File $file = null): AssetCollection
    {
        $title = $create->getTitle();
        if (null === $title) {
            throw new \RuntimeException();
        }
        $finalSlug = $this->getSlug($title, $workspace);
        $assetColletion = (new AssetCollection())
            ->setPublic(false)
            ->setWorkspace($workspace)
            ->setCreator($user)
            ->setTitle($title)
            ->setSlug($finalSlug);

        $this->entityManager->persist($assetColletion);

        $this->databaseLogger->log(UserAction::CREATE_COLLECTION, $assetColletion);

        if ($file instanceof File) {
            $this->addFileToCollection($file, $assetColletion, $user);
            $this->entityManager->flush();
        }

        return $assetColletion;
    }

    public function getCollectionBySlug(string $slug, Workspace $workspace): ?AssetCollection
    {
        return $this->collectionRepository->findOneBy(['slug' => $slug, 'workspace' => $workspace]);
    }

    private function addFileToCollection(File $file, AssetCollection $assetColletion, User $user): void
    {
        $fileCollections = $this->getFileCollections($file);
        if (in_array($assetColletion, $fileCollections)) {
            return;
        }
        $fileCollections[] = $assetColletion;
        $this->updateFileCollections($file, $fileCollections, $user);
    }

    /**
     * @param AssetCollection[] $collections
     */
    public function updateFileCollections(File $file, array $collections, User $user, bool $overWrite = true): void
    {
        if ($overWrite) {
            foreach ($file->getFileAssetCollections() as $fileAssetCollection) {
                if (!in_array(needle: $fileAssetCollection->getAssetCollection(), haystack: $collections, strict: true)) {
                    $file->removeFileAssetCollection($fileAssetCollection);
                    $this->dispatcher->dispatch(new FileAssetRemovedFromCollectionEvent($fileAssetCollection, $file));
                    $this->databaseLogger->log(UserAction::REMOVE_FILE_FROM_COLLECTION, $file, ['collection' => $fileAssetCollection->getAssetCollection()]);
                }
            }
        }

        foreach ($collections as $assetCollection) {
            $fileAssetCollection = $this->fileAssetCollectionRepository->findOneBy([
                'file' => $file,
                'assetCollection' => $assetCollection,
            ]);
            if (null === $fileAssetCollection) {
                $fileAssetCollection = (new FileAssetCollection())
                    ->setFile($file)
                    ->setAssetCollection($assetCollection)
                    ->setAddedBy($user)
                    ->setAddedAt(new \DateTimeImmutable());
                $this->entityManager->persist($fileAssetCollection);
                $file->addFileAssetCollection($fileAssetCollection);
                $this->dispatcher->dispatch(new FileAssetAddedToCollectionEvent($fileAssetCollection));
                $this->databaseLogger->log(UserAction::ADD_FILE_TO_COLLECTION, $file, ['collection' => $assetCollection]);
            }
        }
    }

    /**
     * @return AssetCollection[]
     */
    public function getFileCollections(File $file): array
    {
        $assetCollections = [];
        foreach ($file->getFileAssetCollections() as $fileAssetCollection) {
            $assetCollections[] = $fileAssetCollection->getAssetCollection();
        }

        return array_filter($assetCollections);
    }

    /**
     * @throws PaginatorException
     */
    public function filterAndPaginateCollections(Workspace $workspace, SortFilterPaginateArguments $sortFilterPaginateArguments): Paginator
    {
        $queryBuilder = $this->collectionRepository->getWorkspaceQueryBuilder($workspace);
        $queryBuilder = $this->addSort($queryBuilder, $sortFilterPaginateArguments->getSort());

        $paginator = (new Paginator())->paginate(query: $queryBuilder->getQuery(), page: $sortFilterPaginateArguments->getPage(), limit: $sortFilterPaginateArguments->getLimit());
        try {
            $paginator->mapItems($this->collectionEntityToDtoMapper);
        } catch (ItemMapperException $e) {
            throw new PaginatorException(message: $e->getMessage(), code: $e->getCode(), previous: $e);
        }

        return $paginator;
    }

    private function addSort(QueryBuilder $queryBuilder, SortParam $sortParam): QueryBuilder
    {
        return match ($sortParam) {
            SortParam::UPLOADED_ASC => $queryBuilder->orderBy('c.createdAt', 'ASC'),
            SortParam::UPLOADED_DESC => $queryBuilder->orderBy('c.createdAt', 'DESC'),
            SortParam::MODIFIED_ASC => $queryBuilder->orderBy('c.updatedAt', 'ASC'),
            SortParam::MODIFIED_DESC => $queryBuilder->orderBy('c.updatedAt', 'DESC'),
        };
    }

    private function getSlug(string $title, Workspace $workspace, ?string $slug = null): string
    {
        if (null === $slug) {
            $slug = $this->slugger->slug(strtolower($title))->toString();
        }
        $finalSlug = $slug;
        $slugIsUnique = false;
        $slugCount = 1;
        while (!$slugIsUnique) {
            $checkCollection = $this->collectionRepository->findOneBy(['workspace' => $workspace, 'slug' => $finalSlug]);
            if (null === $checkCollection) {
                $slugIsUnique = true;
            } else {
                $finalSlug = sprintf('%s-%s', $slug, $slugCount);
                ++$slugCount;
            }
        }

        return $finalSlug;
    }

    public function updateCollectionConfig(Config $config, AssetCollection $collection, User $user): AssetCollection
    {
        if (
            null === $config->getSlug()
            || null === $config->getTitle()
            || null === $config->getPublic()
            || !$collection->getWorkspace() instanceof Workspace
        ) {
            /*
             * Die Exception wird hier geworfen, weil die Werte in der Theorie null sein könnten.
             * In der Praxis wurde das Config-Objekt aber schon durch den Validator geschickt, der
             * eine nettere Fehlermeldung geliefert hat. Und eine Collection ohne Workspace sollte
             * es auch nicht bis hierher geschafft haben.
             */
            throw new \RuntimeException();
        }

        $changes = [];
        if ($collection->getTitle() !== $config->getTitle()) {
            $changes['title'] = [
                'old' => $collection->getTitle(),
                'new' => $config->getTitle(),
            ];
            $collection->setTitle($config->getTitle());
        }

        if ($collection->getSlug() !== $config->getSlug()) {
            $newSlug = $this->getSlug(title: $config->getTitle(), workspace: $collection->getWorkspace(), slug: $config->getSlug());
            $changes['slug'] = [
                'old' => $collection->getSlug(),
                'new' => $newSlug,
            ];
            $collection->setSlug($newSlug);
        }

        if ($collection->isPublic() !== $config->getPublic()) {
            $changes['public'] = [
                'old' => $collection->isPublic(),
                'new' => $config->getPublic(),
            ];
            $collection->setPublic($config->getPublic());
        }

        $this->dispatcher->dispatch(new AssetCollectionConfigChangedEvent($collection, $user, $changes));

        return $collection;
    }

    public function deleteCollection(AssetCollection $collection, ?User $user = null): void
    {
        $this->databaseLogger->log(UserAction::DELETE_COLLECTION, $collection);

        $this->removeCollection($collection);
        $this->entityManager->flush();
    }

    private function removeCollection(AssetCollection $collection): void
    {
        $this->entityManager->remove($collection);
    }

    /**
     * @return AssetCollection[]
     *
     * @throws InvalidArgumentException
     */
    public function getForAutoComplete(Workspace $workspace, ?string $query = null, int $limit = 10, bool $cached = true): array
    {
        if ($cached) {
            $cacheKey = sprintf('autocomplete-collection-%s-%s', $workspace->getSlug(), $query);

            return $this->cache->get($cacheKey, function (ItemInterface $item) use ($workspace, $query, $limit) {
                $item->expiresAfter(new \DateInterval('PT30S'));

                return $this->getForAutoComplete($workspace, $query, $limit, false);
            });
        }

        return $this->collectionRepository->findTagsForAutocomplete($workspace, $query, $limit);
    }
}
