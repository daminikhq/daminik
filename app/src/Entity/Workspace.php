<?php

/** @noinspection PhpUnused */

namespace App\Entity;

use App\Repository\WorkspaceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: WorkspaceRepository::class)]
#[UniqueEntity('slug')]
#[ORM\Index(columns: ['slug'], name: 'slug_idx')]
class Workspace
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $slug = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $createdBy = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt;

    /**
     * @var Collection<int, Membership>
     */
    #[ORM\OneToMany(mappedBy: 'workspace', targetEntity: Membership::class, orphanRemoval: true)]
    private Collection $memberships;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * @var Collection<int, Invitation>
     */
    #[ORM\OneToMany(mappedBy: 'workspace', targetEntity: Invitation::class, orphanRemoval: true)]
    private Collection $invitations;

    /**
     * @var Collection<int, LogEntry>
     */
    #[ORM\OneToMany(mappedBy: 'workspace', targetEntity: LogEntry::class, orphanRemoval: true)]
    private Collection $logEntries;

    #[ORM\OneToOne(inversedBy: 'iconWorkspace', cascade: ['persist', 'remove'], fetch: 'EAGER')]
    private ?File $iconFile = null;

    #[ORM\ManyToOne]
    private ?FileSystem $filesystem = null;

    #[ORM\Column(length: 2, nullable: true)]
    private ?string $locale = null;

    /**
     * @var Collection<int, AssetCollection>
     */
    #[ORM\OneToMany(mappedBy: 'workspace', targetEntity: AssetCollection::class, orphanRemoval: true)]
    private Collection $assetCollections;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adminNotice = null;

    /**
     * @var Collection<int, ApiAccessToken>
     */
    #[ORM\OneToMany(mappedBy: 'workspace', targetEntity: ApiAccessToken::class, orphanRemoval: true)]
    private Collection $apiAccessTokens;

    #[ORM\Column(nullable: true)]
    private ?int $uploadedMB = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->memberships = new ArrayCollection();
        $this->invitations = new ArrayCollection();
        $this->logEntries = new ArrayCollection();
        $this->assetCollections = new ArrayCollection();
        $this->apiAccessTokens = new ArrayCollection();
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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection<int, Membership>
     */
    public function getMemberships(): Collection
    {
        return $this->memberships;
    }

    /** @noinspection PhpUnused */
    public function addMembership(Membership $membership): self
    {
        if (!$this->memberships->contains($membership)) {
            $this->memberships->add($membership);
            $membership->setWorkspace($this);
        }

        return $this;
    }

    /** @noinspection PhpUnused */
    public function removeMembership(Membership $membership): self
    {
        // set the owning side to null (unless already changed)
        if ($this->memberships->removeElement($membership) && $membership->getWorkspace() === $this) {
            $membership->setWorkspace(null);
        }

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, Invitation>
     */
    public function getInvitations(): Collection
    {
        return $this->invitations;
    }

    public function addInvitation(Invitation $invitation): self
    {
        if (!$this->invitations->contains($invitation)) {
            $this->invitations->add($invitation);
            $invitation->setWorkspace($this);
        }

        return $this;
    }

    public function removeInvitation(Invitation $invitation): self
    {
        // set the owning side to null (unless already changed)
        if ($this->invitations->removeElement($invitation) && $invitation->getWorkspace() === $this) {
            $invitation->setWorkspace(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, LogEntry>
     */
    public function getLogEntries(): Collection
    {
        return $this->logEntries;
    }

    public function addLogEntry(LogEntry $logEntry): self
    {
        if (!$this->logEntries->contains($logEntry)) {
            $this->logEntries->add($logEntry);
            $logEntry->setWorkspace($this);
        }

        return $this;
    }

    public function removeLogEntry(LogEntry $logEntry): self
    {
        // set the owning side to null (unless already changed)
        if ($this->logEntries->removeElement($logEntry) && $logEntry->getWorkspace() === $this) {
            $logEntry->setWorkspace(null);
        }

        return $this;
    }

    public function getIconFile(): ?File
    {
        return $this->iconFile;
    }

    public function setIconFile(?File $iconFile): self
    {
        $this->iconFile = $iconFile;

        return $this;
    }

    public function getFilesystem(): ?FileSystem
    {
        return $this->filesystem;
    }

    public function setFilesystem(?FileSystem $filesystem): static
    {
        $this->filesystem = $filesystem;

        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(?string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * @return Collection<int, AssetCollection>
     */
    public function getAssetCollections(): Collection
    {
        return $this->assetCollections;
    }

    public function addAssetCollection(AssetCollection $assetCollection): static
    {
        if (!$this->assetCollections->contains($assetCollection)) {
            $this->assetCollections->add($assetCollection);
            $assetCollection->setWorkspace($this);
        }

        return $this;
    }

    public function removeAssetCollection(AssetCollection $assetCollection): static
    {
        // set the owning side to null (unless already changed)
        if ($this->assetCollections->removeElement($assetCollection) && $assetCollection->getWorkspace() === $this) {
            $assetCollection->setWorkspace(null);
        }

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getAdminNotice(): ?string
    {
        return $this->adminNotice;
    }

    public function setAdminNotice(?string $adminNotice): static
    {
        $this->adminNotice = $adminNotice;

        return $this;
    }

    /**
     * @return Collection<int, ApiAccessToken>
     */
    public function getApiAccessTokens(): Collection
    {
        return $this->apiAccessTokens;
    }

    public function addApiAccessToken(ApiAccessToken $apiAccessToken): static
    {
        if (!$this->apiAccessTokens->contains($apiAccessToken)) {
            $this->apiAccessTokens->add($apiAccessToken);
            $apiAccessToken->setWorkspace($this);
        }

        return $this;
    }

    public function removeApiAccessToken(ApiAccessToken $apiAccessToken): static
    {
        // set the owning side to null (unless already changed)
        if ($this->apiAccessTokens->removeElement($apiAccessToken) && $apiAccessToken->getWorkspace() === $this) {
            $apiAccessToken->setWorkspace(null);
        }

        return $this;
    }

    public function getUploadedMB(): ?int
    {
        return $this->uploadedMB;
    }

    public function setUploadedMB(?int $uploadedMB): static
    {
        $this->uploadedMB = $uploadedMB;

        return $this;
    }
}
