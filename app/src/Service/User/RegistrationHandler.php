<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Entity\Invitation;
use App\Entity\RegistrationCode;
use App\Entity\User;
use App\Security\EmailVerifier;
use App\Service\Workspace\Inviter;
use App\Service\Workspace\Inviter\InvalidInvitationException;
use App\Service\Workspace\Inviter\UnknownCodeException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

readonly class RegistrationHandler
{
    public function __construct(
        private Inviter $inviter,
        private EntityManagerInterface $entityManager,
        private EmailVerifier $emailVerifier,
        private RegistrationCodeHandler $registrationCodeHandler
    ) {
    }

    /**
     * Returns the proper invitation if there is a code
     * If there is no code it will just null
     * If the code is unknown there will be an UnknownCodeException
     * If the invitation has run out there will be an InvalidInvitationException.
     *
     * @throws UnknownCodeException
     * @throws InvalidInvitationException
     */
    public function getInvitation(Request $request): ?Invitation
    {
        $invitationCode = $request->get('invitation');
        if (is_string($invitationCode)) {
            return $this->inviter->getInvitation($invitationCode);
        }

        return null;
    }

    /**
     * Saves a new user object with a dummy password,
     * sends the confirmation email and
     * adds the user to the invitation object if necessary.
     *
     * @throws TransportExceptionInterface
     */
    public function registerUserWithoutPassword(
        User $user,
        Request $request,
        ?Invitation $invitation = null,
        ?string $registrationCode = null
    ): User {
        $user->setPassword('registered');
        if (null === $user->getLocale()) {
            $user->setLocale($request->getLocale());
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->emailVerifier->sendEmailConfirmation(
            verifyEmailRouteName: 'verify_email',
            user: $user
        );

        if ($invitation instanceof Invitation) {
            $this->inviter->addUserToInvitation($invitation, $user);
        }

        if (is_string($registrationCode)) {
            $this->registrationCodeHandler->setUserRegistrationCode($user, $registrationCode);
        }

        return $user;
    }

    public function getRegistrationFromRef(Request $request): ?RegistrationCode
    {
        $ref = $request->get('ref') ?? $request->getSession()->get('ref');
        if (!is_string($ref)) {
            return null;
        }

        return $this->registrationCodeHandler->getByRef($ref);
    }
}
