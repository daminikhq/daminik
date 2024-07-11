<?php

declare(strict_types=1);

namespace App\Controller;

use App\Enum\FlashType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;

class LoginController extends AbstractController
{
    #[Route('/login', name: 'login', defaults: ['domain' => '%app.domain%', 'tld' => '%app.tld%'], host: '{domain}.{tld}')]
    public function login(
        AuthenticationUtils $authenticationUtils,
        TranslatorInterface $translator
    ): Response {
        $user = $this->getUser();
        if ($user instanceof UserInterface) {
            return $this->redirectToRoute('home_dashboard');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        if ($error instanceof AuthenticationException) {
            $this->addFlash(FlashType::ERROR->value, $translator->trans($error->getMessageKey(), $error->getMessageData(), 'security'));
        }

        return $this->render('login/login.html.twig', [
            'lastUsername' => $authenticationUtils->getLastUsername(),
        ]);
    }

    #[Route('/logout', name: 'logout', defaults: ['domain' => '%app.domain%', 'tld' => '%app.tld%'], host: '{domain}.{tld}', methods: ['GET'])]
    public function logout(): void
    {
        // controller can be blank: it will never be called!
        throw new \RuntimeException('What.');
    }
}
