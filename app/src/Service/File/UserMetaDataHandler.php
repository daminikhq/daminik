<?php

declare(strict_types=1);

namespace App\Service\File;

use App\Entity\File;
use App\Entity\FileUserMetaData;
use App\Entity\User;
use App\Exception\UserNotFoundException;
use App\Repository\FileUserMetaDataRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class UserMetaDataHandler
{
    private ?User $user = null;

    public function __construct(
        private readonly Security $security,
        private readonly FileUserMetaDataRepository $metaDataRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function isFavorite(File $file): bool
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return false;
        }
        $fileUserData = $this->metaDataRepository->findOneBy(['file' => $file, 'user' => $user]);
        if (null === $fileUserData) {
            return false;
        }

        return $fileUserData->isFavorite();
    }

    private function getUser(): ?User
    {
        if ($this->user instanceof User) {
            return $this->user;
        }
        $user = $this->security->getUser();
        if ($user instanceof User) {
            $this->user = $user;

            return $user;
        }

        return null;
    }

    /**
     * @throws UserNotFoundException
     */
    public function markAsFavorite(File $file, ?User $user = null): bool
    {
        if (!$user instanceof User) {
            $user = $this->getUser();
        }
        if (!$user instanceof User) {
            throw new UserNotFoundException();
        }
        $fileUserData = $this->metaDataRepository->findOneBy(['file' => $file, 'user' => $user]);
        if (null === $fileUserData) {
            $fileUserData = (new FileUserMetaData())
                ->setFile($file)
                ->setUser($user)
                ->setFavorite(false);
        }
        $fileUserData->setFavorite(!$fileUserData->isFavorite());
        $this->entityManager->persist($fileUserData);

        return $fileUserData->isFavorite();
    }
}
