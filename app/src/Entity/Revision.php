<?php

namespace App\Entity;

use App\Repository\RevisionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: RevisionRepository::class)]
class Revision
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'revisions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?File $file = null;

    #[ORM\Column(length: 511)]
    private ?string $filepath = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $uploader = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $mime = null;

    #[ORM\Column(nullable: true)]
    private ?int $width = null;

    #[ORM\Column(nullable: true)]
    private ?int $height = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt;

    #[ORM\Column(nullable: true)]
    private ?int $counter = null;

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $sha1 = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(nullable: true)]
    private ?array $rawExif = [];

    #[ORM\Column(nullable: true)]
    private ?int $fileSize = null;

    /**
     * @var Collection<int, RevisionFileStorageUrl>
     */
    #[ORM\OneToMany(mappedBy: 'revision', targetEntity: RevisionFileStorageUrl::class, orphanRemoval: true)]
    private Collection $storageUrls;

    #[ORM\Column(length: 7, nullable: true)]
    private ?string $accentColor = null;

    #[ORM\ManyToOne]
    private ?FileSystem $fileSystem = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->storageUrls = new ArrayCollection();
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

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function setFile(?File $file): self
    {
        $this->file = $file;

        return $this;
    }

    public function getFilepath(): ?string
    {
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

    public function getMime(): ?string
    {
        return $this->mime;
    }

    public function setMime(?string $mime): self
    {
        $this->mime = $mime;

        return $this;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function setWidth(?int $width): self
    {
        $this->width = $width;

        return $this;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function setHeight(?int $height): self
    {
        $this->height = $height;

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

    public function getCounter(): ?int
    {
        return $this->counter;
    }

    public function setCounter(?int $counter): self
    {
        $this->counter = $counter;

        return $this;
    }

    public function getSha1(): ?string
    {
        return $this->sha1;
    }

    public function setSha1(?string $sha1): self
    {
        $this->sha1 = $sha1;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getRawExif(): ?array
    {
        return $this->rawExif;
    }

    /**
     * @param array<string, mixed>|null $rawExif
     *
     * @return $this
     */
    public function setRawExif(?array $rawExif): self
    {
        $this->rawExif = $rawExif;

        return $this;
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function setFileSize(?int $fileSize): self
    {
        $this->fileSize = $fileSize;

        return $this;
    }

    /**
     * @return Collection<int, RevisionFileStorageUrl>
     */
    public function getStorageUrls(): Collection
    {
        return $this->storageUrls;
    }

    /** @noinspection PhpUnused */
    public function addStorageUrl(RevisionFileStorageUrl $storageUrl): static
    {
        if (!$this->storageUrls->contains($storageUrl)) {
            $this->storageUrls->add($storageUrl);
            $storageUrl->setRevision($this);
        }

        return $this;
    }

    public function removeStorageUrl(RevisionFileStorageUrl $storageUrl): static
    {
        // set the owning side to null (unless already changed)
        if ($this->storageUrls->removeElement($storageUrl) && $storageUrl->getRevision() === $this) {
            $storageUrl->setRevision(null);
        }

        return $this;
    }

    public function getAccentColor(): ?string
    {
        return $this->accentColor;
    }

    public function setAccentColor(?string $accentColor): static
    {
        $this->accentColor = $accentColor;

        return $this;
    }

    public function getFileSystem(): ?FileSystem
    {
        return $this->fileSystem;
    }

    public function setFileSystem(?FileSystem $fileSystem): static
    {
        $this->fileSystem = $fileSystem;

        return $this;
    }
}
