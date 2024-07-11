<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Workspace;
use App\Enum\UserRole;
use Symfony\Component\Validator\Constraints as Assert;

final class WorkspaceInvitation
{
    #[Assert\NotBlank]
    private ?Workspace $workspace = null;

    #[Assert\Email]
    private ?string $email = null;

    private ?UserRole $role = null;
    private ?string $code = null;

    public function getWorkspace(): ?Workspace
    {
        return $this->workspace;
    }

    public function setWorkspace(?Workspace $workspace): WorkspaceInvitation
    {
        $this->workspace = $workspace;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): WorkspaceInvitation
    {
        $this->email = $email;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): WorkspaceInvitation
    {
        $this->code = $code;

        return $this;
    }

    public function getRole(): ?UserRole
    {
        return $this->role;
    }

    public function setRole(?UserRole $role): WorkspaceInvitation
    {
        $this->role = $role;

        return $this;
    }
}
