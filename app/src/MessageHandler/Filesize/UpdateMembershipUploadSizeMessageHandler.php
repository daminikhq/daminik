<?php

namespace App\MessageHandler\Filesize;

use App\Message\Filesize\UpdateMembershipUploadSizeMessage;
use App\Repository\MembershipRepository;
use App\Repository\RevisionRepository;
use App\Repository\UserRepository;
use App\Repository\WorkspaceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class UpdateMembershipUploadSizeMessageHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private WorkspaceRepository $workspaceRepository,
        private RevisionRepository $revisionRepository,
        private MembershipRepository $membershipRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function __invoke(UpdateMembershipUploadSizeMessage $message): void
    {
        $uploader = $this->userRepository->find($message->getUserId());
        if (null === $uploader) {
            return;
        }
        $workspace = $this->workspaceRepository->find($message->getWorkspaceId());
        if (null === $workspace) {
            return;
        }

        $membership = $this->membershipRepository->findOneBy(['user' => $uploader, 'workspace' => $workspace]);
        if (null === $membership) {
            return;
        }
        $filesize = $this->revisionRepository->getFilesizeByUserAndWorkspace($uploader, $workspace);
        $userFilesize = (int) ($filesize / 1000000);
        $membership->setUploadedMB($userFilesize);
        $this->entityManager->flush();
    }
}
