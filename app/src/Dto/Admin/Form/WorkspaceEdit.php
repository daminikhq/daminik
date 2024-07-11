<?php

declare(strict_types=1);

namespace App\Dto\Admin\Form;

use App\Dto\AbstractDto;
use App\Enum\WorkspaceStatus;

class WorkspaceEdit extends AbstractDto
{
    private WorkspaceStatus $status = WorkspaceStatus::ACTIVE;
    private ?string $adminNotice = null;

    public function getStatus(): WorkspaceStatus
    {
        return $this->status;
    }

    public function setStatus(WorkspaceStatus $status): WorkspaceEdit
    {
        $this->status = $status;

        return $this;
    }

    public function getAdminNotice(): ?string
    {
        return $this->adminNotice;
    }

    public function setAdminNotice(?string $adminNotice): WorkspaceEdit
    {
        $this->adminNotice = $adminNotice;

        return $this;
    }
}
