<?php

/** @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection */

namespace App\Entity;

use App\Repository\LogEntryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: LogEntryRepository::class)]
#[ORM\Index(columns: ['created_at'], name: 'created_at_idx')]
#[ORM\Index(columns: ['user_id'], name: 'user_id_idx')]
#[ORM\Index(columns: ['entity_class'], name: 'entity_class_idx')]
#[ORM\Index(columns: ['entity_id'], name: 'entity_id_idx')]
class LogEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?int $userId = null;

    /**
     * @var array<int|string, mixed>
     */
    #[ORM\Column(nullable: true)]
    private ?array $userData = [];

    #[ORM\ManyToOne(inversedBy: 'logEntries')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Workspace $workspace = null;

    #[ORM\Column(length: 255)]
    private ?string $action = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $entityClass = null;

    #[ORM\Column(nullable: true)]
    private ?int $entityId = null;

    /**
     * @var array<mixed>|null
     */
    #[ORM\Column(nullable: true)]
    private ?array $entityJson = [];

    /**
     * @var array<mixed>|null
     */
    #[ORM\Column(nullable: true)]
    private ?array $metaJson = [];

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    /** @noinspection PhpUnused */
    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getWorkspace(): ?Workspace
    {
        return $this->workspace;
    }

    public function setWorkspace(?Workspace $workspace): self
    {
        $this->workspace = $workspace;

        return $this;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): self
    {
        $this->action = $action;

        return $this;
    }

    public function getEntityClass(): ?string
    {
        return $this->entityClass;
    }

    public function setEntityClass(?string $entityClass): self
    {
        $this->entityClass = $entityClass;

        return $this;
    }

    public function getEntityId(): ?int
    {
        return $this->entityId;
    }

    public function setEntityId(?int $entityId): self
    {
        $this->entityId = $entityId;

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function getMetaJson(): array
    {
        if (null === $this->metaJson) {
            $this->metaJson = [];
        }

        return $this->metaJson;
    }

    /**
     * @param mixed[]|null $metaJson
     *
     * @return $this
     */
    public function setMetaJson(?array $metaJson): self
    {
        if (null === $metaJson) {
            $metaJson = [];
        }

        $this->metaJson = $metaJson;

        return $this;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getUserData(): array
    {
        if (null === $this->userData) {
            $this->userData = [];
        }

        return $this->userData;
    }

    /**
     * @param array<int|string, mixed>|null $userData
     */
    public function setUserData(?array $userData): LogEntry
    {
        if (null === $userData) {
            $userData = [];
        }

        $this->userData = $userData;

        return $this;
    }

    /**
     * @return array<mixed>
     */
    public function getEntityJson(): array
    {
        if (null === $this->entityJson) {
            $this->entityJson = [];
        }

        return $this->entityJson;
    }

    /**
     * @param array<mixed> $entityJson
     */
    public function setEntityJson(?array $entityJson): LogEntry
    {
        if (null === $entityJson) {
            $entityJson = [];
        }
        $this->entityJson = $entityJson;

        return $this;
    }
}
