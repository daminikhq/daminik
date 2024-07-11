<?php

namespace App\Controller;

use App\Dto\User\ResetPasswordRequest;
use App\Entity\Invitation;
use App\Entity\User;
use App\Enum\FlashType;
use App\Enum\UserSource;
use App\Form\RegistrationFormType;
use App\Form\User\RequestResetPasswordType;
use App\Form\User\ResetPasswordType;
use App\Form\User\UsernameAndPasswordType;
use App\Security\EmailVerifier;
use App\Service\User\RegistrationHandler;
use App\Service\Workspace\Inviter\InvalidInvitationException;
use App\Service\Workspace\Inviter\UnknownCodeException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\ExpiredSignatureException;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    public function __construct(
        private readonly RegistrationHandler $registrationHandler,
        private readonly EmailVerifier $emailVerifier,
        private readonly TranslatorInterface $translator
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[Route('/register', name: 'register')]
    public function register(
        Request $request,
        TranslatorInterface $translator
    ): Response {
        try {
            $invitation = $this->registrationHandler->getInvitation($request);
        } catch (InvalidInvitationException) {
            $this->addFlash(FlashType::ERROR->value, $this->translator->trans('inviter.invalidInvitation'));
            $invitation = null;
        } catch (UnknownCodeException) {
            $this->addFlash(FlashType::ERROR->value, $this->translator->trans('inviter.unknownCode'));
            $invitation = null;
        }

        $registrationCode = $this->registrationHandler->getRegistrationFromRef($request);

        $user = new User();
        if ($invitation instanceof Invitation) {
            $user->setSource(UserSource::INVITATION->value)
                ->setInitialInvitation($invitation);
        }
        if (null !== $invitation?->getInviteeEmail()) {
            $user->setEmail($invitation->getInviteeEmail());
        }

        $form = $this->createForm(RegistrationFormType::class, $user, [
            'invitation' => $invitation instanceof Invitation,
            'inviteCode' => $registrationCode?->getCode(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $inviteCode = null;
                if ($form->has('inviteCode')) {
                    $inviteCode = $form->get('inviteCode')->getData();
                    if (!is_string($inviteCode)) {
                        $inviteCode = null;
                    }
                }
                $user = $this->registrationHandler->registerUserWithoutPassword(
                    user: $user,
                    request: $request,
                    invitation: $invitation,
                    registrationCode: $inviteCode
                );
                $this->addFlash(FlashType::SUCCESS->value, $this->translator->trans('registration.success'));
                $request->getSession()->set('registeredId', $user->getId());

                return $this->redirectToRoute('register_thanks');
            }

            $email = $form->get('email')->getData();
            if (is_string($email) && ($this->emailVerifier->userIsRegisteredAndHasSignedIn($email)
                    || $this->emailVerifier->userIsRegistered($email))) {
                $this->addFlash(FlashType::ERROR->value, $translator->trans('registration.error.userExists'));

                return $this->redirectToRoute('request_reset_password');
            }

            foreach ($form->getErrors() as $error) {
                $this->addFlash(FlashType::ERROR->value, $error->getMessage());
            }
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
            'inviter' => $invitation?->getUser(),
            'workspace' => $invitation?->getWorkspace(),
            'invitationCode' => $invitation?->getCode(),
        ]);
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[Route('/register/thanks', name: 'register_thanks')]
    public function registerThanks(
        Request $request
    ): Response {
        $registeredId = $request->getSession()->get('registeredId');
        if (is_int($registeredId)) {
            $resendMailForm = $this->createFormBuilder([], ['translation_domain' => 'form'])
                ->add('resendMail', SubmitType::class, [
                    'label' => 'button.resendMail',
                    'attr' => [
                        'class' => 'button',
                    ],
                ])
                ->getForm();
            $resendMailForm->handleRequest($request);
            if ($resendMailForm->isSubmitted() && $resendMailForm->isValid()) {
                $this->emailVerifier->resendEmailConfirmation('verify_email', $registeredId);
                $this->addFlash(FlashType::SUCCESS->value, $this->translator->trans('registration.success'));

                return $this->redirectToRoute('register_thanks');
            }
        } else {
            $resendMailForm = null;
        }

        return $this->render('registration/register_thanks.html.twig', [
            'resendMailForm' => $resendMailForm,
        ]);
    }

    #[Route('/verify/email', name: 'verify_email')]
    public function verifyUserEmail(
        Request $request,
        TranslatorInterface $translator,
        Security $security
    ): Response {
        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            $user = $this->emailVerifier->handleEmailConfirmation($request, $this->getUser());
        } catch (ExpiredSignatureException) {
            $this->addFlash(FlashType::ERROR->value, $translator->trans('registration.error.expiredSignature'));

            return $this->redirectToRoute('request_reset_password');
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash(FlashType::ERROR->value, $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));

            return $this->redirectToRoute('register');
        }

        $form = $this->createForm(UsernameAndPasswordType::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            if (!is_string($plainPassword)) {
                throw new \RuntimeException();
            }
            $this->emailVerifier->saveUserPassword($user, $plainPassword);
            $this->addFlash(FlashType::SUCCESS->value, $this->translator->trans('registration.verified'));
            $security->login($user, 'security.authenticator.form_login.main');

            return $this->redirectToRoute('home_index');
        }

        return $this->render('registration/register_username_and_password.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[Route('/password_reset', name: 'request_reset_password')]
    public function requestReset(Request $request): Response
    {
        $resetPasswordRequest = new ResetPasswordRequest();
        $form = $this->createForm(RequestResetPasswordType::class, $resetPasswordRequest);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->emailVerifier->sendResetPasswordMail($resetPasswordRequest);
            $this->addFlash(FlashType::SUCCESS->value, $this->translator->trans('registration.passwordReset'));

            return $this->redirectToRoute('home_index');
        }

        return $this->render('registration/request_reset_password.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/reset', name: 'reset_password')]
    public function reset(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        TranslatorInterface $translator,
        EntityManagerInterface $entityManager
    ): Response {
        try {
            $user = $this->emailVerifier->handleResetPasswordLink($request, $this->getUser());
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash(FlashType::ERROR->value, $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));

            return $this->redirectToRoute('request_reset_password');
        }

        $form = $this->createForm(ResetPasswordType::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            if (!is_string($plainPassword)) {
                throw new \RuntimeException();
            }
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $plainPassword
                )
            )->setIsVerified(true);

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash(FlashType::SUCCESS->value, $this->translator->trans('registration.passwordResetSuccess'));

            return $this->redirectToRoute('login');
        }

        return $this->render('registration/reset_password.html.twig', [
            'form' => $form,
        ]);
    }
}
