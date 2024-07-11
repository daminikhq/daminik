<?php

declare(strict_types=1);

namespace App\Service\Workspace;

use App\Dto\WorkspaceInvitation;
use App\Entity\Invitation;
use App\Entity\Membership;
use App\Entity\User;
use App\Entity\Workspace;
use App\Enum\MembershipStatus;
use App\Enum\UserAction;
use App\Enum\UserRole;
use App\Exception\WorkspaceException;
use App\Repository\InvitationRepository;
use App\Service\DatabaseLogger\DatabaseLoggerInterface;
use App\Service\Workspace\Inviter\InvalidInvitationException;
use App\Service\Workspace\Inviter\UnknownCodeException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class Inviter
{
    public const SESSION_NAME = 'invitation_code';

    public function __construct(
        private InvitationRepository $invitationRepository,
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer,
        private RouterInterface $router,
        private string $invitationEmail,
        private string $invitationName,
        private DatabaseLoggerInterface $databaseLogger,
        private TranslatorInterface $translator
    ) {
    }

    /**
     * @throws WorkspaceException
     * @throws TransportExceptionInterface
     */
    public function createInvitation(?UserInterface $user, WorkspaceInvitation $dto): Invitation
    {
        if (!$user instanceof User) {
            throw new WorkspaceException();
        }

        $invitation = (new Invitation())
            ->setUser($user)
            ->setWorkspace($dto->getWorkspace())
            ->setCode(Uuid::v4()->toRfc4122())
            ->setRole($dto->getRole()?->value ?? UserRole::WORKSPACE_USER->value)
            ->setInviteeEmail($dto->getEmail());
        $this->entityManager->persist($invitation);

        if (null !== $invitation->getInviteeEmail()) {
            $this->sendInvitation($invitation);
        }

        $this->databaseLogger->log(UserAction::CREATE_INVITATION, $invitation);
        $this->entityManager->flush();

        return $invitation;
    }

    /**
     * @return Invitation[]
     */
    public function getInvitations(Workspace $workspace): array
    {
        return $this->invitationRepository->findBy(['workspace' => $workspace], ['createdAt' => 'DESC']);
    }

    /**
     * @throws TransportExceptionInterface
     */
    private function sendInvitation(Invitation $invitation): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->invitationEmail, $this->invitationName))
            ->to((string) $invitation->getInviteeEmail())
            ->subject($this->translator->trans('invitation.invited'))
            ->htmlTemplate('workspace/invitation_email.html.twig');

        $context = $email->getContext();

        $context['url'] = $this->router->generate('home_invitation', ['code' => $invitation->getCode()], UrlGeneratorInterface::ABSOLUTE_URL);

        $email->context($context);

        $this->mailer->send($email);
    }

    /**
     * @throws TransportExceptionInterface
     */
    private function sendInvitationAcceptedNotification(Invitation $invitation, User $invitee): void
    {
        $user = $invitation->getUser();
        if (!$user instanceof User || null === $user->getEmail()) {
            return;
        }
        $email = (new TemplatedEmail())
            ->from(new Address($this->invitationEmail, $this->invitationName))
            ->to(new Address($user->getEmail(), $user->getName() ?? ''))
            ->subject($this->translator->trans('invitation.accepted'))
            ->htmlTemplate('workspace/invitation_accepted_email.html.twig');

        $context = $email->getContext();

        $context['invitee'] = $invitee;

        $email->context($context);

        $this->mailer->send($email);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function addUserToInvitation(Invitation $invitation, User $user): void
    {
        $invitation->addInvitee($user);
        if (null !== $invitation->getInviteeEmail()) {
            $invitation->setValidUntil(new \DateTimeImmutable());
        }

        $this->entityManager->flush();
        $this->sendInvitationAcceptedNotification($invitation, $user);
    }

    /**
     * @throws InvalidInvitationException
     * @throws UnknownCodeException
     */
    public function addUserByInvitationCode(string $code, User $user): ?Workspace
    {
        $invitation = $this->getInvitationFromCode($code, $user);

        foreach ($user->getMemberships() as $membership) {
            if ($membership->getWorkspace() === $invitation->getWorkspace()) {
                return $membership->getWorkspace();
            }
        }

        $invitation->addInvitee($user);
        $membership = (new Membership())
            ->setUser($user)
            ->setWorkspace($invitation->getWorkspace())
            ->setStatus(MembershipStatus::ACTIVE->value)
            ->setRoles([$invitation->getRole() ?? UserRole::WORKSPACE_USER->value]);

        $this->entityManager->persist($membership);

        if (null !== $invitation->getInviteeEmail()) {
            $invitation->setValidUntil(new \DateTimeImmutable());
        }

        $this->entityManager->flush();

        $this->databaseLogger->log(userAction: UserAction::ACCEPT_INVITATION, object: $invitation, actingUser: $user, workspace: $invitation->getWorkspace());

        return $invitation->getWorkspace();
    }

    /**
     * @throws InvalidInvitationException
     * @throws UnknownCodeException
     */
    public function getInvitation(string $invitationCode): ?Invitation
    {
        return $this->getInvitationFromCode($invitationCode);
    }

    /**
     * @throws InvalidInvitationException
     * @throws UnknownCodeException
     */
    private function getInvitationFromCode(string $code, ?User $user = null): Invitation
    {
        $invitation = $this->invitationRepository->findOneBy(['code' => $code]);

        if (
            null === $invitation
            || null === $invitation->getWorkspace()
        ) {
            throw new UnknownCodeException();
        }

        if ($invitation->getInvitees()->contains($user)) {
            return $invitation;
        }

        if (
            null !== $invitation->getValidUntil()
            && $invitation->getValidUntil() < (new \DateTimeImmutable())
        ) {
            throw new InvalidInvitationException();
        }

        return $invitation;
    }

    /**
     * @throws InvalidInvitationException
     * @throws UnknownCodeException
     */
    public function validateCode(string $code): void
    {
        $this->getInvitationFromCode($code);
    }
}
