<?php

namespace App\MessageHandler\Filesize;

use App\Message\Filesize\UpdateUserUploadSizeMessage;
use App\Repository\RevisionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class UpdateUserUploadSizeMessageHandler
{
    public function __construct(
        private RevisionRepository $revisionRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function __invoke(UpdateUserUploadSizeMessage $message): void
    {
        $uploader = $this->userRepository->find($message->getUserId());
        if (null === $uploader) {
            return;
        }
        $filesize = $this->revisionRepository->getFilesizeByUploader($uploader);
        $userFilesize = (int) ($filesize / 1000000);
        $uploader->setUploadedMB($userFilesize);
        $this->entityManager->flush();
    }
}
