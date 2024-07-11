<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[ORM\UniqueConstraint(
    name: 'slug_idx',
    columns: ['workspace_id', 'slug']
)]
class Category implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Workspace $workspace = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $creator = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    private ?self $parent = null;

    /**
     * @var Collection<int, Category>
     */
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
    private Collection $children;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt;

    /**
     * @var Collection<int, FileCategory>
     */
    #[ORM\OneToMany(mappedBy: 'category', targetEntity: FileCategory::class, orphanRemoval: true)]
    private Collection $fileCategories;

    #[ORM\Column(nullable: true, options: ['default' => 0])]
    private ?int $assetCount = 0;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->children = new ArrayCollection();
        $this->fileCategories = new ArrayCollection();
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

    public function setWorkspace(?Workspace $workspace): self
    {
        $this->workspace = $workspace;

        return $this;
    }

    public function getCreator(): ?User
    {
        return $this->creator;
    }

    public function setCreator(?User $creator): self
    {
        $this->creator = $creator;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(self $child): self
    {
        if (!$this->children->contains($child)) {
            $this->children->add($child);
            $child->setParent($this);
        }

        return $this;
    }

    /** @noinspection PhpUnused */
    public function removeChild(self $child): self
    {
        // set the owning side to null (unless already changed)
        if ($this->children->removeElement($child) && $child->getParent() === $this) {
            $child->setParent(null);
        }

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
     * @return Collection<int, FileCategory>
     */
    public function getFileCategories(): Collection
    {
        return $this->fileCategories;
    }

    /** @noinspection PhpUnused */
    public function addFileCategory(FileCategory $fileCategory): self
    {
        if (!$this->fileCategories->contains($fileCategory)) {
            $this->fileCategories->add($fileCategory);
            $fileCategory->setCategory($this);
        }

        return $this;
    }

    public function getAssetCount(): ?int
    {
        return $this->assetCount;
    }

    public function setAssetCount(?int $assetCount): Category
    {
        $this->assetCount = $assetCount;

        return $this;
    }

    /** @noinspection PhpUnused */
    public function removeFileCategory(FileCategory $fileCategory): self
    {
        // set the owning side to null (unless already changed)
        if ($this->fileCategories->removeElement($fileCategory) && $fileCategory->getCategory() === $this) {
            $fileCategory->setCategory(null);
        }

        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->slug;
    }
}
