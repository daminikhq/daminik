<?php

namespace App\Entity;

use App\Interfaces\AutoCompleteItem;
use App\Repository\AssetCollectionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: AssetCollectionRepository::class)]
#[ORM\UniqueConstraint(
    name: 'slug_idx',
    columns: ['workspace_id', 'slug']
)]
class AssetCollection implements AutoCompleteItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'assetCollections')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Workspace $workspace = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $creator = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[ORM\Column(nullable: true)]
    private ?bool $public = false;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt;

    /**
     * @var Collection<int, FileAssetCollection>
     */
    #[ORM\OneToMany(mappedBy: 'assetCollection', targetEntity: FileAssetCollection::class, orphanRemoval: true)]
    #[ORM\OrderBy(['addedAt' => 'DESC'])]
    private Collection $fileAssetCollections;

    #[ORM\Column(nullable: true, options: ['default' => 0])]
    private ?int $assetCount = 0;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
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

    public function getWorkspace(): ?Workspace
    {
        return $this->workspace;
    }

    public function setWorkspace(?Workspace $workspace): static
    {
        $this->workspace = $workspace;

        return $this;
    }

    public function getCreator(): ?User
    {
        return $this->creator;
    }

    public function setCreator(?User $creator): static
    {
        $this->creator = $creator;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function isPublic(): ?bool
    {
        return $this->public;
    }

    public function setPublic(?bool $public): static
    {
        $this->public = $public;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @return Collection<int, FileAssetCollection>
     */
    public function getFileAssetCollections(): Collection
    {
        return $this->fileAssetCollections;
    }

    /** @noinspection PhpUnused */
    public function addFileAssetCollection(FileAssetCollection $fileAssetCollection): static
    {
        if (!$this->fileAssetCollections->contains($fileAssetCollection)) {
            $this->fileAssetCollections->add($fileAssetCollection);
            $fileAssetCollection->setAssetCollection($this);
        }

        return $this;
    }

    /** @noinspection PhpUnused */
    public function removeFileAssetCollection(FileAssetCollection $fileAssetCollection): static
    {
        // set the owning side to null (unless already changed)
        if ($this->fileAssetCollections->removeElement($fileAssetCollection) && $fileAssetCollection->getAssetCollection() === $this) {
            $fileAssetCollection->setAssetCollection(null);
        }

        return $this;
    }

    public function getAssetCount(): ?int
    {
        return $this->assetCount;
    }

    public function setAssetCount(?int $assetCount): static
    {
        $this->assetCount = $assetCount;

        return $this;
    }

    public function getValue(): string
    {
        return (string) $this->slug;
    }

    public function getText(): string
    {
        return (string) $this->title;
    }
}
