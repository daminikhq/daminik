<?php

namespace App\Entity;

use App\Enum\UserRole;
use App\Interfaces\AutoCompleteItem;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['username'], message: 'There is already an account with this username')]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface, AutoCompleteItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    /**
     * @var array<int, string>
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string|null The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isVerified = false;

    /**
     * @var Collection<int, Membership>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Membership::class, orphanRemoval: true)]
    private Collection $memberships;

    #[ORM\ManyToOne]
    private ?Workspace $lastUsedWorkspace = null;

    /**
     * @var Collection<int, Invitation>
     */
    #[ORM\ManyToMany(targetEntity: Invitation::class, mappedBy: 'invitees')]
    private Collection $invitations;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(length: 180, unique: true, nullable: true)]
    private ?string $username = null;

    #[ORM\Column(length: 2, nullable: true)]
    private ?string $locale = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adminNotice = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $source = null;

    #[ORM\ManyToOne(inversedBy: 'users')]
    private ?RegistrationCode $registrationCode = null;

    #[ORM\ManyToOne]
    private ?Invitation $initialInvitation = null;

    /**
     * @var Collection<int, ApiAccessToken>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: ApiAccessToken::class, orphanRemoval: true)]
    private Collection $apiAccessTokens;

    #[ORM\Column(nullable: true)]
    private ?bool $bot = false;

    #[ORM\Column(nullable: true)]
    private ?int $uploadedMB = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->memberships = new ArrayCollection();
        $this->invitations = new ArrayCollection();
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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return array<int, string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = UserRole::USER->value;

        return array_unique($roles);
    }

    /**
     * @param array<int, string> $roles
     *
     * @return $this
     */
    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /** @noinspection PhpUnused */
    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;

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
            $membership->setUser($this);
        }

        return $this;
    }

    /** @noinspection PhpUnused */
    public function removeMembership(Membership $membership): self
    {
        // set the owning side to null (unless already changed)
        if ($this->memberships->removeElement($membership) && $membership->getUser() === $this) {
            $membership->setUser(null);
        }

        return $this;
    }

    public function getLastUsedWorkspace(): ?Workspace
    {
        return $this->lastUsedWorkspace;
    }

    public function setLastUsedWorkspace(?Workspace $lastUsedWorkspace): self
    {
        $this->lastUsedWorkspace = $lastUsedWorkspace;

        return $this;
    }

    /**
     * @return Collection<int, Invitation>
     */
    public function getInvitations(): Collection
    {
        return $this->invitations;
    }

    /** @noinspection PhpUnused */
    public function addInvitation(Invitation $invitation): self
    {
        if (!$this->invitations->contains($invitation)) {
            $this->invitations->add($invitation);
            $invitation->addInvitee($this);
        }

        return $this;
    }

    /** @noinspection PhpUnused */
    public function removeInvitation(Invitation $invitation): self
    {
        if ($this->invitations->removeElement($invitation)) {
            $invitation->removeInvitee($this);
        }

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

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

    public function getValue(): string
    {
        return (string) $this->username;
    }

    public function getText(): string
    {
        return (string) $this->username;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): User
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): User
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): User
    {
        $this->status = $status;

        return $this;
    }

    public function getAdminNotice(): ?string
    {
        return $this->adminNotice;
    }

    public function setAdminNotice(?string $adminNotice): User
    {
        $this->adminNotice = $adminNotice;

        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): static
    {
        $this->source = $source;

        return $this;
    }

    public function getRegistrationCode(): ?RegistrationCode
    {
        return $this->registrationCode;
    }

    public function setRegistrationCode(?RegistrationCode $registrationCode): static
    {
        $this->registrationCode = $registrationCode;

        return $this;
    }

    public function getInitialInvitation(): ?Invitation
    {
        return $this->initialInvitation;
    }

    public function setInitialInvitation(?Invitation $initialInvitation): static
    {
        $this->initialInvitation = $initialInvitation;

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
            $apiAccessToken->setUser($this);
        }

        return $this;
    }

    public function removeApiAccessToken(ApiAccessToken $apiAccessToken): static
    {
        // set the owning side to null (unless already changed)
        if ($this->apiAccessTokens->removeElement($apiAccessToken) && $apiAccessToken->getUser() === $this) {
            $apiAccessToken->setUser(null);
        }

        return $this;
    }

    public function isBot(): ?bool
    {
        return $this->bot;
    }

    public function setBot(?bool $bot): static
    {
        $this->bot = $bot;

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
