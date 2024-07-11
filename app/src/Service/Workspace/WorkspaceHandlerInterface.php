<?php

declare(strict_types=1);

namespace App\Service\Workspace;

use App\Dto\Admin\Form\WorkspaceEdit;
use App\Dto\Utility\SortFilterPaginateArguments;
use App\Dto\WorkspaceAdmin;
use App\Entity\User;
use App\Entity\Workspace;
use App\Exception\File\MissingWorkspaceException;
use App\Exception\FileHandlerException;
use App\Util\Paginator;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface WorkspaceHandlerInterface
{
    /**
     * @throws FileHandlerException
     * @throws MissingWorkspaceException
     */
    public function update(Workspace $workspace, WorkspaceAdmin $adminFormData, User $user, ?UploadedFile $logo = null): void;

    /**
     * @throws MissingWorkspaceException
     * @throws FileHandlerException
     */
    public function removeIcon(Workspace $workspace, User $user, bool $log = true): void;

    public function filterAndPaginateWorkspaces(SortFilterPaginateArguments $sortFilterPaginateArguments, bool $withoutGlobal = true): Paginator;

    public function updateStatus(Workspace $workspace, WorkspaceEdit $edit): void;
}
