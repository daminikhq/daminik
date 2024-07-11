<?php

namespace App\MessageHandler\Filesize;

use App\Message\Filesize\UpdateWorkspaceUploadSizeMessage;
use App\Repository\RevisionRepository;
use App\Repository\WorkspaceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class UpdateWorkspaceUploadSizeMessageHandler
{
    public function __construct(
        private WorkspaceRepository $workspaceRepository,
        private RevisionRepository $revisionRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function __invoke(UpdateWorkspaceUploadSizeMessage $message): void
    {
        $workspace = $this->workspaceRepository->find($message->getWorkspaceId());
        if (null === $workspace) {
            return;
        }
        $filesize = $this->revisionRepository->getFilesizeByWorkspace($workspace);
        $userFilesize = (int) ($filesize / 1000000);
        $workspace->setUploadedMB($userFilesize);
        $this->entityManager->flush();
    }
}
