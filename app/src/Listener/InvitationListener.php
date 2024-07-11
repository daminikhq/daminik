<?php

declare(strict_types=1);

namespace App\Listener;

use App\Entity\User;
use App\Entity\Workspace;
use App\Service\Workspace\Inviter;
use App\Service\Workspace\Inviter\InvalidInvitationException;
use App\Service\Workspace\Inviter\UnknownCodeException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

readonly class InvitationListener implements EventSubscriberInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private Inviter $inviter,
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @return string[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            SecurityEvents::INTERACTIVE_LOGIN => 'onInteractiveLogin',
        ];
    }

    /**
     * @throws InvalidInvitationException
     * @throws UnknownCodeException
     */
    public function onInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();
        if (true === $this->requestStack->getMainRequest()?->attributes->getBoolean('_stateless')) {
            return;
        }
        $session = $this->requestStack->getSession();
        $code = $session->get(Inviter::SESSION_NAME);
        if ($user instanceof User && is_string($code)) {
            $workspace = $this->inviter->addUserByInvitationCode($code, $user);
            if ($workspace instanceof Workspace && 'global' !== $workspace->getSlug()) {
                $user->setLastUsedWorkspace($workspace);
                $this->entityManager->flush();
            }
        }
    }
}
