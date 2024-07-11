<?php

namespace App\Security;

use App\Dto\User\ResetPasswordRequest;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Util\Hashids;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Translation\LocaleSwitcher;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

readonly class EmailVerifier
{
    public function __construct(
        private string $registrationEmail,
        private string $registrationName,
        private VerifyEmailHelperInterface $verifyEmailHelper,
        private MailerInterface $mailer,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private Hashids $hashids,
        private LoggerInterface $logger,
        private UserPasswordHasherInterface $userPasswordHasher,
        private LocaleSwitcher $localeSwitcher,
        private TranslatorInterface $translator
    ) {
    }

    public function sendTemplatedEmail(string $verifyEmailRouteName, string $templatePath, UserInterface $user): void
    {
        $user = $this->validateUserEntity($user);
        if (null !== $user->getLocale()) {
            $this->localeSwitcher->setLocale($user->getLocale());
        }

        if ('' === trim($templatePath)) {
            $this->logger->alert('Could not send mail, template path missing');

            return;
        }

        $email = (new TemplatedEmail())
            ->from(new Address($this->registrationEmail, $this->registrationName))
            ->to((string) $user->getEmail())
            ->subject($this->translator->trans('registration.pleaseConfirm'))
            ->htmlTemplate($templatePath);

        $signatureComponents = $this->verifyEmailHelper->generateSignature(
            routeName: $verifyEmailRouteName,
            userId: (string) $user->getId(),
            userEmail: (string) $user->getEmail(),
            extraParams: ['id' => $this->hashids->encode((int) $user->getId())]
        );

        $context = $email->getContext();
        $context['signedUrl'] = $signatureComponents->getSignedUrl();
        $context['expiresAtMessageKey'] = $signatureComponents->getExpirationMessageKey();
        $context['expiresAtMessageData'] = $signatureComponents->getExpirationMessageData();
        $context['locale'] = $user->getLocale();

        $email->context($context);

        $this->mailer->send($email);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendEmailConfirmation(string $verifyEmailRouteName, UserInterface $user): void
    {
        $this->sendTemplatedEmail(
            verifyEmailRouteName: $verifyEmailRouteName,
            templatePath: 'emails/registrations_confirmation_email.html.twig',
            user: $user);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function resendEmailConfirmation(string $verifyEmailRouteName, int $registeredId): void
    {
        $user = $this->userRepository->find($registeredId);
        if (null !== $user) {
            $this->sendTemplatedEmail(
                verifyEmailRouteName: $verifyEmailRouteName,
                templatePath: 'emails/registrations_confirmation_email.html.twig',
                user: $user);
        }
    }

    /**
     * @throws VerifyEmailExceptionInterface
     * @throws \UnexpectedValueException
     */
    public function handleEmailConfirmation(Request $request, ?UserInterface $user): User
    {
        $validatedUser = $this->getValidatedUserFromEmailHash($request, $user);

        $validatedUser->setIsVerified(true);

        $this->entityManager->persist($validatedUser);
        $this->entityManager->flush();

        return $validatedUser;
    }

    /**
     * @throws VerifyEmailExceptionInterface
     */
    public function handleResetPasswordLink(Request $request, ?UserInterface $user): User
    {
        return $this->getValidatedUserFromEmailHash($request, $user);
    }

    private function validateUserEntity(UserInterface $user): User
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException();
        }

        if (null === $user->getId()) {
            throw new UnsupportedUserException();
        }

        if (null === $user->getEmail()) {
            throw new UnsupportedUserException();
        }

        return $user;
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendResetPasswordMail(ResetPasswordRequest $resetPasswordRequest): void
    {
        $user = $this->userRepository->findOneBy(['email' => $resetPasswordRequest->getEmail()]);
        if (null === $user) {
            return;
        }
        $this->sendTemplatedEmail(
            verifyEmailRouteName: 'reset_password',
            templatePath: 'emails/password_reset_email.html.twig',
            user: $user);
    }

    /**
     * @throws VerifyEmailExceptionInterface
     */
    private function getValidatedUserFromEmailHash(Request $request, ?UserInterface $user): User
    {
        $hash = $request->get('id');
        if (!is_string($hash)) {
            throw new \UnexpectedValueException();
        }
        $key = $this->hashids->decode($hash);
        if (null === $key) {
            throw new \UnexpectedValueException();
        }

        $userToValidate = $this->userRepository->find($key);
        if (null === $userToValidate || ($user instanceof UserInterface && $userToValidate !== $user)) {
            throw new \UnexpectedValueException();
        }

        $userToValidate = $this->validateUserEntity($userToValidate);
        $this->verifyEmailHelper->validateEmailConfirmation($request->getUri(), (string) $userToValidate->getId(), (string) $userToValidate->getEmail());

        return $userToValidate;
    }

    public function userIsRegisteredAndHasSignedIn(string $email): bool
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);

        return
            $user instanceof User
            && is_string($user->getPassword())
            && 'registered' !== $user->getPassword();
    }

    public function userIsRegistered(string $email): bool
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);

        return
            $user instanceof User
            && 'registered' === $user->getPassword();
    }

    public function saveUserPassword(User $user, string $plainPassword): User
    {
        $user->setPassword(
            $this->userPasswordHasher->hashPassword(
                $user,
                $plainPassword
            )
        );
        $this->entityManager->flush();

        return $user;
    }
}
