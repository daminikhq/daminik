<?php

declare(strict_types=1);

namespace App\Listener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

readonly class UserLocaleListener implements EventSubscriberInterface
{
    public function __construct(
        private RequestStack $requestStack
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => ['onKernelRequest', 101],
            SecurityEvents::INTERACTIVE_LOGIN => 'onInteractiveLogin',
        ];
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event): void
    {
        if (true === $this->requestStack->getMainRequest()?->attributes->getBoolean('_stateless')) {
            return;
        }

        $user = $event->getAuthenticationToken()->getUser();

        if (!is_null($user) && method_exists($user, 'getLocale') && null !== $user->getLocale()) {
            $this->requestStack->getSession()->set('_locale', $user->getLocale());
        }
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if ($request->attributes->getBoolean('_stateless')) {
            return;
        }

        $locale = $request->getSession()->get('_locale');
        if (is_string($locale) && $request->hasPreviousSession()) {
            $request->setLocale($locale);
        } else {
            // @TODO get the locales from the config file
            $locale = $request->getPreferredLanguage(['de', 'en']);
            if (is_string($locale)) {
                $request->setLocale($locale);
            }
        }
    }
}
