<?php

declare(strict_types=1);

namespace App\Listener;

use App\Entity\User;
use App\Repository\WorkspaceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final readonly class LastWorkspaceListener implements EventSubscriberInterface
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private WorkspaceRepository $workspaceRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $user = $this->tokenStorage->getToken()?->getUser();
        if (!$user instanceof User) {
            return;
        }

        $subdomain = $event->getRequest()->attributes->get('subdomain');
        if (!is_string($subdomain) || 'global' === $subdomain) {
            return;
        }
        $workspace = $this->workspaceRepository->findOneBy(['slug' => $subdomain]);
        if (null === $workspace) {
            return;
        }
        if ($user->getLastUsedWorkspace() !== $workspace) {
            $user->setLastUsedWorkspace($workspace);
            $this->entityManager->flush();
        }
    }
}
