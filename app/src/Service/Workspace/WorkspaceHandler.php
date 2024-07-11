<?php

declare(strict_types=1);

namespace App\Service\Workspace;

use App\Dto\Admin\Form\WorkspaceEdit;
use App\Dto\File\Upload;
use App\Dto\Utility\SortFilterPaginateArguments;
use App\Dto\WorkspaceAdmin;
use App\Entity\File;
use App\Entity\User;
use App\Entity\Workspace;
use App\Enum\UserAction;
use App\Exception\File\MissingWorkspaceException;
use App\Exception\FileHandlerException;
use App\Message\CompletelyDeleteAssetMessage;
use App\Message\UpdateAssetVisibilityMessage;
use App\Repository\WorkspaceRepository;
use App\Service\DatabaseLogger\DatabaseLoggerInterface;
use App\Service\File\FileHandler;
use App\Service\User\WorkspaceRobotHandler;
use App\Util\Paginator;
use App\Util\Paginator\PaginatorException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class WorkspaceHandler implements WorkspaceHandlerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private FileHandler $fileHandler,
        private DatabaseLoggerInterface $databaseLogger,
        private MessageBusInterface $bus,
        private WorkspaceRepository $workspaceRepository,
        private Paginator $paginator,
        private WorkspaceRobotHandler $workspaceRobotHandler
    ) {
    }

    /**
     * @throws FileHandlerException
     * @throws MissingWorkspaceException
     * @throws NonUniqueResultException
     * @throws ExceptionInterface
     */
    public function update(Workspace $workspace, WorkspaceAdmin $adminFormData, User $user, ?UploadedFile $logo = null): void
    {
        $changedValues = [];
        if (null !== $adminFormData->getName() && $adminFormData->getName() !== $workspace->getName()) {
            $changedValues['name'] = [
                'old' => $workspace->getName(),
                'new' => $adminFormData->getName(),
            ];
            $workspace->setName($adminFormData->getName());
        }
        if ($adminFormData->getLocale() !== $workspace->getLocale()) {
            $changedValues['locale'] = [
                'old' => $workspace->getLocale(),
                'new' => $adminFormData->getLocale(),
            ];
            $workspace->setLocale($adminFormData->getLocale());
        }
        $hasApiAccess = $this->workspaceRobotHandler->hasApiAccess($workspace);
        if ($adminFormData->getApiAccess() !== $hasApiAccess) {
            $changedValues['locale'] = [
                'old' => $hasApiAccess,
                'new' => $adminFormData->getApiAccess(),
            ];
            $this->workspaceRobotHandler->toggleApiAccess($workspace, $adminFormData->getApiAccess());
        }
        $this->entityManager->flush();
        if ($logo instanceof UploadedFile) {
            $oldFilename = null;
            if ($workspace->getIconFile() instanceof File) {
                $oldFilename = $workspace->getIconFile()->getFilename();
                $this->removeIcon(workspace: $workspace, user: $user, log: false);
            }
            $iconFile = $this->fileHandler->saveUploadedFileAsIcon(
                upload: new Upload($user, $workspace),
                uploadedFile: $logo
            );
            $changedValues['logo'] = [
                'old' => $oldFilename,
                'new' => $iconFile->getFilename(),
            ];
        }
        $this->databaseLogger->log(userAction: UserAction::UPDATE_WORKSPACE_CONFIG, object: $workspace, metadata: $changedValues, actingUser: $user, workspace: $workspace);
        $this->entityManager->flush();
    }

    /**
     * @throws MissingWorkspaceException
     * @throws FileHandlerException
     * @throws ExceptionInterface
     */
    public function removeIcon(Workspace $workspace, User $user, bool $log = true): void
    {
        $iconFile = $workspace->getIconFile();
        if (!$iconFile instanceof File) {
            return;
        }
        $this->fileHandler->deleteFile($iconFile);
        $workspace->setIconFile(null);
        $this->entityManager->flush();
        if (null !== $iconFile->getId()) {
            $this->bus->dispatch(new CompletelyDeleteAssetMessage($iconFile->getId()));
        }
        if ($log) {
            $this->databaseLogger->log(userAction: UserAction::UPDATE_WORKSPACE_CONFIG, object: $workspace, metadata: ['logo' => ['old' => $iconFile->getFilename(), 'new' => null]], actingUser: $user, workspace: $workspace);
        }
        $this->entityManager->flush();
    }

    /**
     * @throws PaginatorException
     */
    public function filterAndPaginateWorkspaces(SortFilterPaginateArguments $sortFilterPaginateArguments, bool $withoutGlobal = true): Paginator
    {
        return $this->paginator->paginate(
            query: $this->workspaceRepository->getWorkspaceQuery($sortFilterPaginateArguments->getSort(), $withoutGlobal),
            page: $sortFilterPaginateArguments->getPage(),
            limit: $sortFilterPaginateArguments->getLimit()
        );
    }

    /**
     * @throws ExceptionInterface
     */
    public function updateStatus(Workspace $workspace, WorkspaceEdit $edit): void
    {
        $changed = ($workspace->getStatus() !== $edit->getStatus()->value);

        $workspace->setStatus($edit->getStatus()->value)
            ->setAdminNotice($edit->getAdminNotice());
        $this->entityManager->flush();

        if ($changed) {
            $this->bus->dispatch(new UpdateAssetVisibilityMessage(workspaceId: $workspace->getId()));
        }
    }
}
