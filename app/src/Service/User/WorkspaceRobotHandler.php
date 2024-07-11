<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Entity\ApiAccessToken;
use App\Entity\Membership;
use App\Entity\User;
use App\Entity\Workspace;
use App\Enum\MembershipStatus;
use App\Enum\UserRole;
use App\Enum\UserSource;
use App\Enum\UserStatus;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;

class WorkspaceRobotHandler
{
    /**
     * @var array<int, ?User>
     */
    private array $botUsers = [];

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @throws NonUniqueResultException
     */
    public function hasApiAccess(Workspace $workspace): bool
    {
        $robotUser = $this->getRobotUser($workspace);

        return $robotUser?->getStatus() === UserStatus::ACTIVE->value;
    }

    /**
     * @throws NonUniqueResultException
     */
    public function getRobotUser(Workspace $workspace): ?User
    {
        if (null === $workspace->getId()) {
            return null;
        }
        if (array_key_exists($workspace->getId(), $this->botUsers)) {
            return $this->botUsers[$workspace->getId()];
        }
        $this->botUsers[$workspace->getId()] = $this->userRepository->findWorkspaceRobot($workspace);

        return $this->botUsers[$workspace->getId()];
    }

    /**
     * @throws NonUniqueResultException
     */
    public function toggleApiAccess(Workspace $workspace, ?bool $getApiAccess): void
    {
        if (null === $getApiAccess) {
            $getApiAccess = false;
        }
        $botUser = $this->getRobotUser($workspace);
        if (!$botUser instanceof User) {
            if (false === $getApiAccess) {
                return;
            }
            $botUser = $this->createRobotUser($workspace);
        }
        if (false === $getApiAccess) {
            $botUser->setStatus(UserStatus::INACTIVE->value);
        } else {
            $botUser->setStatus(UserStatus::ACTIVE->value);
            if ($botUser->getApiAccessTokens()->count() < 1) {
                // @TODO auslagern
                $accessToken = (new ApiAccessToken())
                    ->setWorkspace($workspace)
                    ->setUser($botUser)
                    ->setToken(substr(password_hash($workspace->getSlug().'-'.time(), PASSWORD_DEFAULT), 10, 20));
                $this->entityManager->persist($accessToken);
            }
        }
        $this->entityManager->flush();
    }

    private function createRobotUser(Workspace $workspace): User
    {
        $botUser = (new User())
            ->setSource(UserSource::ROBOT->value)
            ->setBot(true)
            ->setEmail(sprintf('robot-%s@noreply.daminik.com', $workspace->getSlug()))
            ->setStatus(UserStatus::ACTIVE->value)
            ->setPassword('botuser')
            ->setUsername(sprintf('robot-%s', $workspace->getSlug()))
            ->setLastUsedWorkspace($workspace);
        $this->entityManager->persist($botUser);
        $botUserMembership = (new Membership())
            ->setUser($botUser)
            ->setWorkspace($workspace)
            ->setRoles([UserRole::WORKSPACE_ROBOT->value])
            ->setStatus(MembershipStatus::ROBOT->value);
        $this->entityManager->persist($botUserMembership);

        return $botUser;
    }

    /**
     * @throws NonUniqueResultException
     */
    public function getApiKey(Workspace $workspace): ?string
    {
        $robotUser = $this->getRobotUser($workspace);
        if (!$robotUser instanceof User || $robotUser->getStatus() !== UserStatus::ACTIVE->value) {
            return null;
        }

        $apiAccessToken = $robotUser->getApiAccessTokens()->first();

        return ($apiAccessToken instanceof ApiAccessToken) ? $apiAccessToken->getToken() : null;
    }
}
