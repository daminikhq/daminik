<?php

namespace App\Entity;

use App\Interfaces\AutoCompleteItem;
use App\Repository\TagRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: TagRepository::class)]
#[ORM\UniqueConstraint(
    name: 'slug_idx',
    columns: ['workspace_id', 'slug']
)]
class Tag implements AutoCompleteItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Workspace $workspace = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $creator = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt;

    /**
     * @var Collection<int, FileTag>
     */
    #[ORM\OneToMany(mappedBy: 'tag', targetEntity: FileTag::class)]
    private Collection $fileTags;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->fileTags = new ArrayCollection();
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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @return Collection<int, FileTag>
     */
    public function getFileTags(): Collection
    {
        return $this->fileTags;
    }

    /** @noinspection PhpUnused */
    public function addFileTag(FileTag $fileTag): self
    {
        if (!$this->fileTags->contains($fileTag)) {
            $this->fileTags->add($fileTag);
            $fileTag->setTag($this);
        }

        return $this;
    }

    /** @noinspection PhpUnused */
    public function removeFileTag(FileTag $fileTag): self
    {
        // set the owning side to null (unless already changed)
        if ($this->fileTags->removeElement($fileTag) && $fileTag->getTag() === $this) {
            $fileTag->setTag(null);
        }

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
