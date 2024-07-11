<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Dto\User\Avatar;
use App\Entity\File;
use App\Entity\User;
use App\Message\CompletelyDeleteAssetMessage;
use App\Repository\FileRepository;
use App\Service\File\Helper\UrlHelperInterface;
use App\Service\Workspace\WorkspaceIdentifier;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly class AvatarHandler
{
    public function __construct(
        private WorkspaceIdentifier $workspaceIdentifier,
        private Security $security,
        private FileRepository $fileRepository,
        private HttpClientInterface $httpClient,
        private CacheInterface $cache,
        private UrlHelperInterface $urlHelper,
        private LoggerInterface $logger,
        private MessageBusInterface $bus
    ) {
    }

    public function getAvatar(?UserInterface $user = null): Avatar
    {
        if (!$user instanceof UserInterface) {
            $user = $this->security->getUser();
        }

        if (!$user instanceof User) {
            return new Avatar();
        }

        $workspace = $this->workspaceIdentifier->getGlobalWorkspace();

        try {
            $avatarFile = $this->fileRepository->findAvatar($user, $workspace);
        } catch (\Throwable $e) {
            $this->logger->error('Error getting avatar', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            $avatarFile = null;
        }
        if ($avatarFile instanceof File) {
            return new Avatar(
                url: $this->urlHelper->getPrivateUrl($avatarFile),
                file: $avatarFile
            );
        }
        try {
            $gravatarUrl = $this->getGravatarUrl($user);
        } catch (\Throwable $e) {
            $this->logger->error('Error getting gravatar URL', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            $gravatarUrl = null;
        }
        if (null !== $gravatarUrl) {
            return new Avatar(
                url: $gravatarUrl,
                gravatar: $gravatarUrl
            );
        }

        return new Avatar();
    }

    public function resetAvatar(?UserInterface $user = null): void
    {
        if (!$user instanceof UserInterface) {
            $user = $this->security->getUser();
        }

        if (!$user instanceof User) {
            return;
        }

        $workspace = $this->workspaceIdentifier->getGlobalWorkspace();
        try {
            $avatarFile = $this->fileRepository->findAvatar($user, $workspace);
        } catch (\Throwable $e) {
            $this->logger->error('Error getting avatar', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            $avatarFile = null;
        }
        if ($avatarFile instanceof File) {
            $avatarFile->setDeletedAt(new \DateTime());
            if (null !== $avatarFile->getId()) {
                $this->bus->dispatch(new CompletelyDeleteAssetMessage($avatarFile->getId()));
            }
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getGravatarUrl(User $user): ?string
    {
        if (null === $user->getEmail()) {
            return null;
        }
        $gravatarHash = hash('sha256', strtolower(trim($user->getEmail())));
        $gravatarUrl = sprintf('https://gravatar.com/avatar/%s?s=200&r=pg&d=404', $gravatarHash);
        if ($this->gravatarExists($gravatarUrl)) {
            return $gravatarUrl;
        }

        return null;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function gravatarExists(string $gravatarUrl): bool
    {
        $key = 'gravatar-'.urlencode($gravatarUrl);

        return $this->cache->get($key, function (CacheItemInterface $item) use ($gravatarUrl) {
            $item->expiresAfter(new \DateInterval('PT30M'));
            try {
                $response = $this->httpClient->request('GET', $gravatarUrl, ['timeout' => 1]);
                $response->getHeaders();
            } catch (\Throwable) {
                return false;
            }

            return true;
        });
    }
}
