<?php

declare(strict_types=1);

namespace App\Service\Workspace;

use App\Dto\NewWorkspace;
use App\Entity\User;
use App\Entity\Workspace;
use App\Exception\WorkspaceException;
use App\Exception\WorkspaceExistsException;

interface CreatorInterface
{
    /**
     * @throws WorkspaceException
     * @throws WorkspaceExistsException
     */
    public function createWorkspace(User $user, NewWorkspace $newWorkspace): Workspace;

    public function getGlobalWorkspace(): Workspace;
}
