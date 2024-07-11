<?php

declare(strict_types=1);

namespace App\Util\Mapper\Paginator;

use App\Dto\DatabaseLogger\Category as CategoryDto;
use App\Dto\DatabaseLogger\CategoryChanges;
use App\Dto\DatabaseLogger\Changes;
use App\Dto\DatabaseLogger\Collection as CollectionDto;
use App\Dto\DatabaseLogger\CollectionChanges;
use App\Dto\DatabaseLogger\File as FileDto;
use App\Dto\DatabaseLogger\FileCategoryChange;
use App\Dto\DatabaseLogger\FileChanges;
use App\Dto\DatabaseLogger\FileCollection;
use App\Dto\DatabaseLogger\Invitation as InvitationDto;
use App\Dto\DatabaseLogger\LogEntry as LogEntryDto;
use App\Dto\DatabaseLogger\MembershipChanges;
use App\Dto\DatabaseLogger\MetaDataInterface;
use App\Dto\DatabaseLogger\User as UserDto;
use App\Dto\DatabaseLogger\Workspace as WorkspaceDto;
use App\Dto\DatabaseLogger\WorkspaceChanges;
use App\Entity\AssetCollection;
use App\Entity\Category;
use App\Entity\File;
use App\Entity\Invitation;
use App\Entity\LogEntry;
use App\Entity\Membership;
use App\Entity\User;
use App\Entity\Workspace;
use App\Enum\UserAction;
use App\Service\DatabaseLogger\DatabaseLoggerException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;

readonly class LogEntryMapper implements ItemMapperInterface
{
    public function __construct(
        private SerializerInterface $serializer,
        private LoggerInterface $logger
    ) {
    }

    public function map(mixed $origin): LogEntryDto
    {
        assert(assertion: $origin instanceof LogEntry, description: new ItemMapperException());
        assert(assertion: null !== $origin->getAction(), description: new ItemMapperException());

        $userAction = UserAction::from($origin->getAction());

        $userDto = $this->mapDataArrayToDto(
            dataArray: $origin->getUserData(),
            type: UserDto::class,
            requiredKeys: ['userId']
        );
        $createdAt = $origin->getCreatedAt();

        assert(assertion: ($userDto instanceof UserDto || !$userDto instanceof MetaDataInterface), description: new ItemMapperException());
        assert(assertion: $createdAt instanceof \DateTimeImmutable, description: new ItemMapperException());

        $entityData = $this->mapEntityData($origin->getEntityClass(), $origin->getEntityJson());
        $metaData = $this->mapMetaDataArray($userAction, $origin->getMetaJson());

        return new LogEntryDto(
            userAction: $userAction,
            createdAt: $createdAt,
            user: $userDto,
            entityData: $entityData,
            metaData: $metaData
        );
    }

    /**
     * @param array<string|int, mixed> $dataArray
     * @param array<int, string>       $requiredKeys
     */
    public function mapDataArrayToDto(array $dataArray, string $type, array $requiredKeys = []): ?MetaDataInterface
    {
        foreach ($requiredKeys as $requiredKey) {
            if (!array_key_exists($requiredKey, $dataArray)) {
                return null;
            }
        }

        try {
            return $this->serializer->deserialize($this->serializer->serialize($dataArray, 'json'), $type, 'json');
        } catch (\Throwable $e) {
            $this->logger->debug(__METHOD__, [
                'e' => $e::class,
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
                'dataArray' => $dataArray,
                'type' => $type,
                'requiredKeys' => $requiredKeys,
            ]);

            return null;
        }
    }

    public function mapUserEntityToDto(?object $user): ?UserDto
    {
        if (!$user instanceof User || null === $user->getId()) {
            return null;
        }

        return new UserDto(
            userId: $user->getId(),
            username: $user->getUsername(),
            name: $user->getName()
        );
    }

    public function mapInvitationEntityToDto(?object $object): ?InvitationDto
    {
        if (!$object instanceof Invitation) {
            return null;
        }

        return new InvitationDto($object->getInviteeEmail(), $object->getRole());
    }

    /**
     * @param array<int|string, mixed>|MetaDataInterface|null $metadata
     */
    public function mapFileEntityToDto(?object $object, array|MetaDataInterface|null $metadata = null): ?FileDto
    {
        if (!$object instanceof File) {
            if ($metadata instanceof FileDto) {
                return $metadata;
            }

            return null;
        }

        return new FileDto(
            fileId: $object->getId(),
            fileName: $object->getFilename(),
            mimeType: $object->getMime(),
            title: $object->getTitle(),
            description: $object->getDescription(),
            public: $object->isPublic()
        );
    }

    /**
     * @noinspection DuplicatedCode
     *
     * @param array<int|string, mixed>|MetaDataInterface|null $metadata
     *
     * @throws DatabaseLoggerException
     */
    public function mapEditCategory(?object $object, array|MetaDataInterface|null $metadata): ?CategoryChanges
    {
        if (!$object instanceof Category) {
            return null;
        }
        $categoryDto = $this->mapCategoryEntityToDto($object);
        if (!$categoryDto instanceof CategoryDto) {
            return null;
        }

        return new CategoryChanges($categoryDto, $this->getChanges($metadata));
    }

    /**
     * @param array<int|string, mixed>|MetaDataInterface|null $metadata
     *
     * @throws DatabaseLoggerException
     */
    public function mapEditFile(?object $object, array|MetaDataInterface|null $metadata): ?FileChanges
    {
        if (!$object instanceof File) {
            return null;
        }
        $fileDto = $this->mapFileEntityToDto($object);
        if (!$fileDto instanceof FileDto) {
            return null;
        }

        return new FileChanges($fileDto, $this->getChanges($metadata));
    }

    /**
     * @param array<int|string, mixed>|MetaDataInterface|null $metadata
     *
     * @throws DatabaseLoggerException
     */
    public function mapEditCollection(?object $object, array|MetaDataInterface|null $metadata): ?CollectionChanges
    {
        if (!$object instanceof AssetCollection) {
            return null;
        }
        $collectionDto = $this->mapAssetCollectionEntityToDto($object);
        if (!$collectionDto instanceof CollectionDto) {
            return null;
        }

        return new CollectionChanges($collectionDto, $this->getChanges($metadata));
    }

    /**
     * @param array<int|string, mixed>|MetaDataInterface|null $metadata
     */
    public function mapChangeFileCategory(?object $object, array|MetaDataInterface|null $metadata = null): ?FileCategoryChange
    {
        if (!$object instanceof File || !is_array($metadata)) {
            return null;
        }
        $oldCategory = $newCategory = null;
        if (array_key_exists('old', $metadata) && $metadata['old'] instanceof Category) {
            $oldCategory = $this->mapCategoryEntityToDto($metadata['old']);
        }
        if (array_key_exists('new', $metadata) && $metadata['new'] instanceof Category) {
            $newCategory = $this->mapCategoryEntityToDto($metadata['new']);
        }

        $file = $this->mapFileEntityToDto($object);
        if (!$file instanceof FileDto) {
            return null;
        }

        return new FileCategoryChange(
            $file,
            $oldCategory,
            $newCategory
        );
    }

    public function mapCategoryEntityToDto(?object $category): ?CategoryDto
    {
        if (!$category instanceof Category || null === $category->getId()) {
            return null;
        }
        $parentDto = $category->getParent() instanceof Category ? $this->mapCategoryEntityToDto($category->getParent()) : null;

        return (new CategoryDto(
            categoryId: $category->getId(),
            title: $category->getTitle(),
            slug: $category->getSlug()
        ))
            ->setParent($parentDto);
    }

    /**
     * @param array<int|string, mixed>|MetaDataInterface|null $metadata
     *
     * @throws DatabaseLoggerException
     */
    public function mapEditMembership(?object $object, array|MetaDataInterface|null $metadata): ?MembershipChanges
    {
        if (!$object instanceof Membership || !$object->getUser() instanceof User) {
            return null;
        }
        $userDto = $this->mapUserEntityToDto($object->getUser());
        if (!$userDto instanceof UserDto) {
            return null;
        }

        return new MembershipChanges($userDto, $this->getChanges($metadata));
    }

    /**
     * @param array<int|string, mixed>|MetaDataInterface|null $metadata
     */
    public function mapFileCollectionChange(?object $object, array|MetaDataInterface|null $metadata): ?FileCollection
    {
        if (
            !$object instanceof File
            || !is_array($metadata)
            || !array_key_exists('collection', $metadata)
            || !$metadata['collection'] instanceof AssetCollection
        ) {
            return null;
        }

        $file = $this->mapFileEntityToDto($object);
        if (!$file instanceof FileDto) {
            return null;
        }
        $collection = $this->mapAssetCollectionEntityToDto($metadata['collection']);
        if (!$collection instanceof CollectionDto) {
            return null;
        }

        return new FileCollection($file, $collection);
    }

    public function mapAssetCollectionEntityToDto(?object $object): ?CollectionDto
    {
        if (!$object instanceof AssetCollection) {
            return null;
        }

        return new CollectionDto(
            $object->getId(),
            $object->getTitle(),
            $object->getSlug(),
        );
    }

    /**
     * @param array<int|string, mixed>|MetaDataInterface|null $metadata
     *
     * @return Changes[]
     *
     * @throws DatabaseLoggerException
     */
    private function getChanges(array|MetaDataInterface|null $metadata): array
    {
        $changes = [];
        if (is_array($metadata)) {
            foreach ($metadata as $key => $value) {
                $key = (string) $key;
                if (
                    is_array($value)
                ) {
                    $change = new Changes(
                        field: $key,
                        oldValue: array_key_exists('old', $value) ? $this->mapChangeValue($value['old']) : null,
                        newValue: array_key_exists('new', $value) ? $this->mapChangeValue($value['new']) : null,
                    );
                    $changes[] = $change;
                }
            }
        }

        return $changes;
    }

    /**
     * @throws DatabaseLoggerException
     */
    private function mapChangeValue(mixed $changeValue): ?string
    {
        if (null === $changeValue) {
            return null;
        }

        if (is_string($changeValue)) {
            if ('' !== trim($changeValue)) {
                return trim($changeValue);
            }

            return null;
        }

        if (is_numeric($changeValue)) {
            return (string) $changeValue;
        }

        try {
            return json_encode($changeValue, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            throw new DatabaseLoggerException(message: $e->getMessage(), code: $e->getCode(), previous: $e);
        }
    }

    /**
     * @param array<string|int, mixed> $metaDataArray
     */
    private function mapMetaDataArray(UserAction $userAction, array $metaDataArray = []): ?MetaDataInterface
    {
        if (count($metaDataArray) < 1) {
            return null;
        }

        return match ($userAction) {
            UserAction::CREATE_INVITATION => $this->mapDataArrayToDto(dataArray: $metaDataArray, type: Invitation::class),
            UserAction::UPLOAD_FILE, UserAction::DELETE_FILE, UserAction::UNDELETE_FILE, UserAction::COMPLETELY_DELETE_FILE => $this->mapDataArrayToDto(dataArray: $metaDataArray, type: FileDto::class),
            UserAction::EDIT_FILE => $this->mapDataArrayToDto(dataArray: $metaDataArray, type: FileChanges::class, requiredKeys: ['file']),
            UserAction::CHANGE_FILE_CATEGORY => $this->mapDataArrayToDto(dataArray: $metaDataArray, type: FileCategoryChange::class, requiredKeys: ['file']),
            UserAction::ADD_FILE_TO_COLLECTION, UserAction::REMOVE_FILE_FROM_COLLECTION => $this->mapDataArrayToDto(dataArray: $metaDataArray, type: FileCollection::class, requiredKeys: ['file', 'collection']),
            UserAction::CREATE_COLLECTION, UserAction::DELETE_COLLECTION => $this->mapDataArrayToDto(dataArray: $metaDataArray, type: CollectionDto::class),
            UserAction::UPDATE_COLLECTION_CONFIG => $this->mapDataArrayToDto(dataArray: $metaDataArray, type: CollectionChanges::class, requiredKeys: ['collection']),
            UserAction::UPDATE_MEMBERSHIP => $this->mapDataArrayToDto(dataArray: $metaDataArray, type: MembershipChanges::class, requiredKeys: ['user']),
            UserAction::DELETE_MEMBERSHIP => $this->mapDataArrayToDto(dataArray: $metaDataArray, type: UserDto::class, requiredKeys: ['userId']),
            UserAction::CREATE_CATEGORY, UserAction::DELETE_CATEGORY => $this->mapDataArrayToDto(dataArray: $metaDataArray, type: CategoryDto::class),
            UserAction::EDIT_CATEGORY => $this->mapDataArrayToDto(dataArray: $metaDataArray, type: CategoryChanges::class, requiredKeys: ['category']),
            UserAction::UPDATE_WORKSPACE_CONFIG => $this->mapDataArrayToDto(dataArray: $metaDataArray, type: WorkspaceChanges::class, requiredKeys: ['workspace']),
            default => null,
        };
    }

    /**
     * @param array<string|int, mixed> $dataArray
     */
    private function mapEntityData(?string $class, array $dataArray): ?MetaDataInterface
    {
        if (null === $class || count($dataArray) < 1) {
            return null;
        }

        return match ($class) {
            User::class => $this->mapDataArrayToDto(dataArray: $dataArray, type: UserDto::class),
            File::class => $this->mapDataArrayToDto(dataArray: $dataArray, type: FileDto::class),
            Category::class => $this->mapDataArrayToDto(dataArray: $dataArray, type: CategoryDto::class),
            AssetCollection::class => $this->mapDataArrayToDto(dataArray: $dataArray, type: CollectionDto::class),
            Invitation::class => $this->mapDataArrayToDto(dataArray: $dataArray, type: InvitationDto::class),
            Workspace::class => $this->mapDataArrayToDto(dataArray: $dataArray, type: WorkspaceDto::class),
            default => null,
        };
    }

    public function mapWorkspaceEntityToDto(?object $object = null): ?WorkspaceDto
    {
        if (!$object instanceof Workspace) {
            return null;
        }

        return new WorkspaceDto(
            $object->getName(),
        );
    }

    /**
     * @param array<int|string, mixed>|MetaDataInterface|null $metadata
     *
     * @throws DatabaseLoggerException
     */
    public function mapEditWorkspace(?object $object, array|MetaDataInterface|null $metadata): ?WorkspaceChanges
    {
        if (
            !$object instanceof Workspace
            || !is_array($metadata)
        ) {
            return null;
        }

        $workspace = $this->mapWorkspaceEntityToDto($object);
        if (!$workspace instanceof WorkspaceDto) {
            return null;
        }

        return new WorkspaceChanges($workspace, $this->getChanges($metadata));
    }
}
