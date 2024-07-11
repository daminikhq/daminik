<?php

/** @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection */

namespace App\Entity;

use App\Enum\FileType;
use App\Repository\FileRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: FileRepository::class)]
#[ORM\UniqueConstraint(
    name: 'filename_idx',
    columns: ['workspace_id', 'filename']
)]
#[ORM\UniqueConstraint(
    name: 'filename_slug_idx',
    columns: ['workspace_id', 'filename_slug']
)]
#[ORM\UniqueConstraint(
    name: 'public_filename_slug_idx',
    columns: ['workspace_id', 'public_filename_slug']
)]
#[ORM\Index(columns: ['type'], name: 'type_idx')]
#[ORM\Index(columns: ['public'], name: 'public_idx')]
#[ORM\Index(columns: ['created_at'], name: 'created_at_idx')]
#[ORM\Index(columns: ['updated_at'], name: 'updated_at_idx')]
class File
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $filepath = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $filenameSlug = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $uploader = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Workspace $workspace = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $deletedAt = null;
    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $filename = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $mime = null;

    #[ORM\Column(nullable: true)]
    private ?bool $public = null;

    /**
     * @var Collection<int, Revision>
     */
    #[ORM\OneToMany(mappedBy: 'file', targetEntity: Revision::class, orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $revisions;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?Revision $activeRevision = null;

    /**
     * @var Collection<int, FileTag>
     */
    #[ORM\OneToMany(mappedBy: 'file', targetEntity: FileTag::class, orphanRemoval: true)]
    #[ORM\OrderBy(['title' => 'ASC'])]
    private Collection $fileTags;

    /**
     * @var Collection<int, FileCategory>
     */
    #[ORM\OneToMany(mappedBy: 'file', targetEntity: FileCategory::class, orphanRemoval: true)]
    private Collection $fileCategories;

    #[ORM\OneToOne(mappedBy: 'iconFile', cascade: ['persist', 'remove'])]
    private ?Workspace $iconWorkspace = null;

    /**
     * @var mixed[]|null
     */
    #[ORM\Column(nullable: true)]
    private ?array $aiTags = null;

    /**
     * @var Collection<int, FileAssetCollection>
     */
    #[ORM\OneToMany(mappedBy: 'file', targetEntity: FileAssetCollection::class, orphanRemoval: true)]
    private Collection $fileAssetCollections;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $completelyDeleteStarted = null;

    #[ORM\Column(length: 255, nullable: true, options: ['default' => FileType::ASSET->value])]
    private ?string $type = null;

    #[ORM\ManyToOne]
    private ?User $updatedBy = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $extension = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $publicFilenameSlug = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->revisions = new ArrayCollection();
        $this->fileTags = new ArrayCollection();
        $this->fileCategories = new ArrayCollection();
        $this->fileAssetCollections = new ArrayCollection();
    }

    /** @noinspection PhpUnused */
    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFilepath(): ?string
    {
        if ($this->activeRevision instanceof Revision) {
            return $this->activeRevision->getFilepath();
        }

        return $this->filepath;
    }

    public function setFilepath(string $filepath): self
    {
        $this->filepath = $filepath;

        return $this;
    }

    public function getUploader(): ?User
    {
        return $this->uploader;
    }

    public function setUploader(?User $uploader): self
    {
        $this->uploader = $uploader;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): File
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): File
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getFilename(): ?string
    {
        return $this->filename ?? $this->filepath;
    }

    public function setFilename(?string $filename): self
    {
        $this->filename = $filename;

        return $this;
    }

    public function getMime(): ?string
    {
        return $this->mime;
    }

    public function setMime(?string $mime): self
    {
        $this->mime = $mime;

        return $this;
    }

    public function getDeletedAt(): ?\DateTime
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTime $deletedAt): File
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    public function isPublic(): ?bool
    {
        return $this->public;
    }

    public function setPublic(?bool $public): self
    {
        $this->public = $public;

        return $this;
    }

    public function getFilenameSlug(): ?string
    {
        return $this->filenameSlug;
    }

    public function setFilenameSlug(?string $filenameSlug): File
    {
        $this->filenameSlug = $filenameSlug;

        return $this;
    }

    /**
     * @return Collection<int, Revision>
     */
    public function getRevisions(): Collection
    {
        return $this->revisions;
    }

    /** @noinspection PhpUnused */
    public function addRevision(Revision $revision): self
    {
        if (!$this->revisions->contains($revision)) {
            $this->revisions->add($revision);
            $revision->setFile($this);
        }

        return $this;
    }

    /** @noinspection PhpUnused */
    public function removeRevision(Revision $revision): self
    {
        // set the owning side to null (unless already changed)
        if ($this->revisions->removeElement($revision) && $revision->getFile() === $this) {
            $revision->setFile(null);
        }

        return $this;
    }

    public function getActiveRevision(): ?Revision
    {
        return $this->activeRevision;
    }

    public function setActiveRevision(?Revision $activeRevision): self
    {
        $this->activeRevision = $activeRevision;

        return $this;
    }

    /**
     * @return Collection<int, FileTag>
     */
    public function getFileTags(): Collection
    {
        return $this->fileTags;
    }

    /**
     * @param Collection<int, FileTag> $fileTags
     */
    public function setFileTags(Collection $fileTags): File
    {
        $this->fileTags = $fileTags;

        return $this;
    }

    /** @noinspection PhpUnused */
    public function addFileTag(FileTag $fileTag): self
    {
        if (!$this->fileTags->contains($fileTag)) {
            $this->fileTags->add($fileTag);
            $fileTag->setFile($this);
        }

        return $this;
    }

    /** @noinspection PhpUnused */
    public function removeFileTag(FileTag $fileTag): self
    {
        // set the owning side to null (unless already changed)
        if ($this->fileTags->removeElement($fileTag) && $fileTag->getFile() === $this) {
            $fileTag->setFile(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, FileCategory>
     */
    public function getFileCategories(): Collection
    {
        return $this->fileCategories;
    }

    public function addFileCategory(FileCategory $fileCategory): self
    {
        if (!$this->fileCategories->contains($fileCategory)) {
            $this->fileCategories->add($fileCategory);
            $fileCategory->setFile($this);
        }

        return $this;
    }

    public function removeFileCategory(FileCategory $fileCategory): self
    {
        // set the owning side to null (unless already changed)
        if ($this->fileCategories->removeElement($fileCategory) && $fileCategory->getFile() === $this) {
            $fileCategory->setFile(null);
        }

        return $this;
    }

    public function getIconWorkspace(): ?Workspace
    {
        return $this->iconWorkspace;
    }

    public function setIconWorkspace(?Workspace $iconWorkspace): self
    {
        // unset the owning side of the relation if necessary
        if (!$iconWorkspace instanceof Workspace && $this->iconWorkspace instanceof Workspace) {
            $this->iconWorkspace->setIconFile(null);
        }

        // set the owning side of the relation if necessary
        if ($iconWorkspace instanceof Workspace && $iconWorkspace->getIconFile() !== $this) {
            $iconWorkspace->setIconFile($this);
        }

        $this->iconWorkspace = $iconWorkspace;

        return $this;
    }

    public function getFileSize(): ?int
    {
        return $this->activeRevision?->getFileSize();
    }

    public function getWidth(): ?int
    {
        return $this->activeRevision?->getWidth();
    }

    public function getHeight(): ?int
    {
        return $this->activeRevision?->getHeight();
    }

    /**
     * @return mixed[]|null
     */
    public function getAiTags(): ?array
    {
        return $this->aiTags;
    }

    /**
     * @param mixed[]|null $aiTags
     *
     * @return $this
     */
    public function setAiTags(?array $aiTags): static
    {
        $this->aiTags = $aiTags;

        return $this;
    }

    /**
     * @return Collection<int, FileAssetCollection>
     */
    public function getFileAssetCollections(): Collection
    {
        return $this->fileAssetCollections;
    }

    public function addFileAssetCollection(FileAssetCollection $fileAssetCollection): static
    {
        if (!$this->fileAssetCollections->contains($fileAssetCollection)) {
            $this->fileAssetCollections->add($fileAssetCollection);
            $fileAssetCollection->setFile($this);
        }

        return $this;
    }

    public function removeFileAssetCollection(FileAssetCollection $fileAssetCollection): static
    {
        // set the owning side to null (unless already changed)
        if ($this->fileAssetCollections->removeElement($fileAssetCollection) && $fileAssetCollection->getFile() === $this) {
            $fileAssetCollection->setFile(null);
        }

        return $this;
    }

    public function getCompletelyDeleteStarted(): ?\DateTimeImmutable
    {
        return $this->completelyDeleteStarted;
    }

    public function setCompletelyDeleteStarted(?\DateTimeImmutable $completelyDeleteStarted): static
    {
        $this->completelyDeleteStarted = $completelyDeleteStarted;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getUpdatedBy(): ?User
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?User $updatedBy): static
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    public function getExtension(): ?string
    {
        return $this->extension;
    }

    public function setExtension(?string $extension): static
    {
        $this->extension = $extension;

        return $this;
    }

    public function getPublicFilenameSlug(): ?string
    {
        if (null === $this->publicFilenameSlug) {
            $this->publicFilenameSlug = $this->filenameSlug;
        }

        return $this->publicFilenameSlug;
    }

    public function setPublicFilenameSlug(?string $publicFilenameSlug): static
    {
        $this->publicFilenameSlug = $publicFilenameSlug;

        return $this;
    }
}
