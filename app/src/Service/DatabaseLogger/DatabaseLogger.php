<?php

declare(strict_types=1);

namespace App\Service\DatabaseLogger;

use App\Dto\DatabaseLogger\MetaDataInterface;
use App\Dto\Utility\SortFilterPaginateArguments;
use App\Entity\AssetCollection;
use App\Entity\Category;
use App\Entity\File;
use App\Entity\Invitation;
use App\Entity\LogEntry;
use App\Entity\Membership;
use App\Entity\User;
use App\Entity\Workspace;
use App\Enum\UserAction;
use App\Repository\LogEntryRepository;
use App\Service\Workspace\WorkspaceIdentifier;
use App\Util\Mapper\Paginator\ItemMapperException;
use App\Util\Mapper\Paginator\LogEntryMapper;
use App\Util\Paginator;
use App\Util\Paginator\PaginatorException;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Proxy;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class DatabaseLogger implements DatabaseLoggerInterface
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private WorkspaceIdentifier $workspaceIdentifier,
        private LogEntryRepository $entryRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        private LogEntryMapper $mapper,
    ) {
    }

    /**
     * @param array<mixed>|MetaDataInterface|null $metadata
     *
     * @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection
     */
    public function log(
        UserAction $userAction,
        ?object $object = null,
        array|MetaDataInterface|null $metadata = null,
        ?UserInterface $actingUser = null,
        ?Workspace $workspace = null
    ): void {
        if (!$workspace instanceof Workspace) {
            $workspace = $this->workspaceIdentifier->getWorkspace();
        }

        if (!$actingUser instanceof UserInterface) {
            $actingUser = $this->tokenStorage->getToken()?->getUser();
        }

        /*
         * This should make it so that we don't log
         * empty objects. I am not happy with this implementation
         * as a random flush here could habe unexpected side-effects.
         * It would probably better to move this to some kind of post-flush
         * event
         */
        $forceFlush = false;
        if (null !== $object && method_exists($object, 'getId') && null === $object->getId()) {
            $forceFlush = true;
            $this->entityManager->flush();
        }

        $this->logger->debug(__METHOD__, [
            'user' => $actingUser,
            'object' => $object,
            'action' => $userAction,
            'forceFlush' => $forceFlush,
        ]);

        try {
            $entityClass = null;
            if (null !== $object) {
                $entityClass = ($object instanceof Proxy ? ClassUtils::getClass($object) : $object::class);
            }

            $entry = (new LogEntry())
                ->setWorkspace($workspace)
                ->setAction($userAction->value)
                ->setUserId($actingUser instanceof User ? $actingUser->getId() : null)
                ->setUserData($this->mapper->mapUserEntityToDto($actingUser)?->toArray())
                ->setEntityClass($entityClass)
                ->setEntityId(null !== $object && method_exists($object, 'getId') ? $object->getId() : null)
                ->setEntityJson($this->mapEntityData($object)?->toArray())
                ->setMetaJson($this->mapMetaData($userAction, $object, $metadata)?->toArray());
            $this->entityManager->persist($entry);
        } catch (\Throwable $e) {
            $this->logger->critical(__METHOD__, [
                'e' => $e::class,
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }

    /**
     * @throws PaginatorException
     */
    public function getEntries(Workspace $workspace, SortFilterPaginateArguments $arguments): Paginator
    {
        $queryBuilder = $this->entryRepository->getWorkspaceQueryBuilder($workspace);
        $queryBuilder->orderBy('e.createdAt', 'DESC');

        $paginator = (new Paginator())->paginate(query: $queryBuilder->getQuery(), page: $arguments->getPage(), limit: $arguments->getLimit());
        try {
            $paginator->mapItems($this->mapper);
        } catch (ItemMapperException $e) {
            throw new PaginatorException(message: $e->getMessage(), code: $e->getCode(), previous: $e);
        }

        return $paginator;
    }

    private function mapEntityData(?object $object): ?MetaDataInterface
    {
        if (null === $object) {
            return null;
        }

        $entityClass = ($object instanceof Proxy ? ClassUtils::getClass($object) : $object::class);

        return match ($entityClass) {
            File::class => $this->mapper->mapFileEntityToDto($object),
            Invitation::class => $this->mapper->mapInvitationEntityToDto($object),
            Category::class => $this->mapper->mapCategoryEntityToDto($object),
            AssetCollection::class => $this->mapper->mapAssetCollectionEntityToDto($object),
            User::class => $this->mapper->mapUserEntityToDto($object),
            Workspace::class => $this->mapper->mapWorkspaceEntityToDto($object),
            default => null,
        };
    }

    /**
     * @param array<int|string, mixed>|MetaDataInterface|null $metadata
     *
     * @throws DatabaseLoggerException
     */
    private function mapMetaData(UserAction $userAction, ?object $object, array|MetaDataInterface|null $metadata): ?MetaDataInterface
    {
        return match ($userAction) {
            UserAction::EDIT_FILE => $this->mapper->mapEditFile($object, $metadata),
            UserAction::CHANGE_FILE_CATEGORY => $this->mapper->mapChangeFileCategory($object, $metadata),
            UserAction::ADD_FILE_TO_COLLECTION, UserAction::REMOVE_FILE_FROM_COLLECTION => $this->mapper->mapFileCollectionChange($object, $metadata),
            UserAction::EDIT_CATEGORY => $this->mapper->mapEditCategory($object, $metadata),
            UserAction::UPDATE_COLLECTION_CONFIG => $this->mapper->mapEditCollection($object, $metadata),
            UserAction::UPDATE_MEMBERSHIP => $this->mapper->mapEditMembership($object, $metadata),
            UserAction::DELETE_MEMBERSHIP => $this->mapper->mapUserEntityToDto($object instanceof Membership ? $object->getUser() : null),
            UserAction::UPDATE_WORKSPACE_CONFIG => $this->mapper->mapEditWorkspace($object, $metadata),
            default => null,
        };
    }
}
