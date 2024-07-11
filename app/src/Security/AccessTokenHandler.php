<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Workspace;
use App\Enum\UserStatus;
use App\Repository\ApiAccessTokenRepository;
use App\Service\Workspace\WorkspaceIdentifier;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class AccessTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(
        private readonly ApiAccessTokenRepository $accessTokenRepository,
        private readonly WorkspaceIdentifier $workspaceIdentifier
    ) {
    }

    public function getUserBadgeFrom(#[\SensitiveParameter] string $accessToken): UserBadge
    {
        $workspace = $this->workspaceIdentifier->getWorkspace();
        if (!$workspace instanceof Workspace) {
            throw new AuthenticationException();
        }
        $apiAccessToken = $this->accessTokenRepository->findOneBy([
            'token' => $accessToken,
            'workspace' => $workspace,
        ]);
        if (null === $apiAccessToken || null === $apiAccessToken->getUser()) {
            throw new AuthenticationException();
        }

        if ($apiAccessToken->getUser()->getStatus() !== UserStatus::ACTIVE->value) {
            throw new AuthenticationException();
        }

        return new UserBadge($apiAccessToken->getUser()->getUserIdentifier());
    }
}
