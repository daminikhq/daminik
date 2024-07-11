<?php

declare(strict_types=1);

namespace App\Controller\Workspace\Admin;

use App\Controller\Workspace\AbstractWorkspaceController;
use App\Dto\User\UserAdminEdit;
use App\Dto\Utility\DefaultRequestValues;
use App\Dto\WorkspaceAdmin;
use App\Dto\WorkspaceInvitation;
use App\Entity\Membership;
use App\Enum\FlashType;
use App\Exception\File\MissingWorkspaceException;
use App\Exception\FileHandlerException;
use App\Exception\UserHandler\CantRemoveLastOwnerException;
use App\Exception\UserHandler\WorkspaceNotSetException;
use App\Exception\WorkspaceException;
use App\Form\User\UserAdminEditType;
use App\Form\WorkspaceAdminType;
use App\Form\WorkspaceInvitationType;
use App\Repository\UserRepository;
use App\Security\Voter\MembershipVoter;
use App\Security\Voter\WorkspaceVoter;
use App\Service\User\MembershipHandlerInterface;
use App\Service\User\WorkspaceRobotHandler;
use App\Service\Workspace\Inviter;
use App\Service\Workspace\WorkspaceHandlerInterface;
use App\Service\Workspace\WorkspaceIdentifier;
use App\Util\Hashids;
use App\Util\Paginator\PaginatorException;
use App\Util\RequestArgumentHelper;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted(WorkspaceVoter::VIEW)]
#[Route('/', name: 'workspace_admin_', defaults: ['domain' => '%app.domain%', 'tld' => '%app.tld%'], host: '{subdomain}.{domain}.{tld}')]
class AdminController extends AbstractWorkspaceController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        WorkspaceIdentifier $workspaceIdentifier,
    ) {
        parent::__construct($workspaceIdentifier);
    }

    /**
     * @throws MissingWorkspaceException
     * @throws FileHandlerException
     * @throws NonUniqueResultException
     */
    #[Route('admin', name: 'index')]
    #[IsGranted(WorkspaceVoter::CONFIG)]
    public function index(
        Request $request,
        WorkspaceHandlerInterface $workspaceHandler,
        WorkspaceRobotHandler $workspaceRobotHandler
    ): Response {
        [$workspace, $user] = $this->getWorkspaceAndUser();

        $adminFormData = new WorkspaceAdmin(
            $workspace->getName(),
            $workspace->getLocale(),
            $workspaceRobotHandler->hasApiAccess($workspace)
        );

        $iconFile = $workspace->getIconFile();
        $form = $this->createForm(WorkspaceAdminType::class, $adminFormData, [
            'logo' => $iconFile,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $submitButton = $form->get('submit');
            if (method_exists($submitButton, 'isClicked') && $submitButton->isClicked()) {
                $logo = $form->get('logo')->getData();
                if (!$logo instanceof UploadedFile) {
                    $logo = null;
                }
                $workspaceHandler->update(workspace: $workspace, adminFormData: $adminFormData, user: $user, logo: $logo);

                return $this->redirectToRoute('workspace_admin_index');
            }

            // resetAvatar-Button was clicked
            if (null !== $iconFile) {
                $workspaceHandler->removeIcon($workspace, $user);
            }

            return $this->redirectToRoute('workspace_admin_index');
        }

        return $this->render('workspace/admin.html.twig', [
            'controller_name' => self::class,
            'workspace' => $workspace,
            'icon' => $iconFile,
            'form' => $form,
            'apikey' => $workspaceRobotHandler->getApiKey($workspace),
        ]);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws WorkspaceException
     */
    #[Route('invitations', name: 'invitations')]
    #[IsGranted(WorkspaceVoter::INVITE)]
    public function invitations(
        Request $request,
        Inviter $inviter
    ): Response {
        $workspace = $this->getWorkspace();

        $invitation = (new WorkspaceInvitation())
            ->setWorkspace($workspace);

        $form = $this->createForm(WorkspaceInvitationType::class, $invitation);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $invitationEntity = $inviter->createInvitation($this->getUser(), $invitation);

            return $this->redirectToRoute('workspace_admin_invitations', ['invitation' => $invitationEntity->getCode()]);
        }

        $invitations = $inviter->getInvitations($workspace);

        return $this->render('workspace/invitations.html.twig', [
            'invitations' => $invitations,
            'workspace' => $workspace,
            'form' => $form,
        ]);
    }

    /**
     * @throws WorkspaceNotSetException
     */
    #[Route('user/{userId}', name: 'user_edit')]
    #[IsGranted(WorkspaceVoter::USERS_EDIT)]
    public function edit(
        string $userId,
        Request $request,
        Hashids $hashids,
        UserRepository $userRepository,
        MembershipHandlerInterface $membershipHandler
    ): Response {
        $membership = $this->getMembership(hashids: $hashids, userId: $userId, userRepository: $userRepository, membershipHandler: $membershipHandler);
        if (!$membership instanceof Membership || !$this->isGranted(MembershipVoter::MEMBERSHIP_EDIT, $membership)) {
            throw $this->createAccessDeniedException();
        }

        $userAdminDto = (new UserAdminEdit())
            ->setRole($membershipHandler->getHighestWorkspaceRole($membership));
        $form = $this->createForm(UserAdminEditType::class, $userAdminDto);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $membershipHandler->updateMembership(membership: $membership, userAdminDto: $userAdminDto);
            } catch (CantRemoveLastOwnerException) {
                $this->addFlash(FlashType::ERROR->value, $this->translator->trans('admin.message.user.lastOwner'));

                return $this->redirectToRoute('workspace_admin_user_edit', ['userId' => $userId]);
            }
            $this->addFlash(FlashType::SUCCESS->value, $this->translator->trans('admin.message.user.success.edit'));

            return $this->redirectToRoute('workspace_admin_users');
        }

        return $this->render('workspace/user.html.twig', [
            'workspace' => $membership->getWorkspace(),
            'membership' => $membership,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @throws WorkspaceNotSetException
     */
    #[Route('user/{userId}/delete', name: 'user_delete')]
    #[IsGranted(WorkspaceVoter::USERS_EDIT)]
    public function delete(
        string $userId,
        Request $request,
        Hashids $hashids,
        UserRepository $userRepository,
        MembershipHandlerInterface $membershipHandler
    ): Response {
        $membership = $this->getMembership(hashids: $hashids, userId: $userId, userRepository: $userRepository, membershipHandler: $membershipHandler);
        if (!$membership instanceof Membership || !$this->isGranted(MembershipVoter::MEMBERSHIP_DELETE, $membership)) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createFormBuilder([])
            ->add('deleteMembership', SubmitType::class, [
                'label' => 'form.deleteMembership',
                'attr' => [
                    'class' => 'button',
                ],
            ])
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $membershipHandler->deleteMembership($membership);
            } catch (CantRemoveLastOwnerException) {
                $this->addFlash(FlashType::ERROR->value, $this->translator->trans('admin.message.user.lastOwner'));

                return $this->redirectToRoute('workspace_admin_users');
            }
            $this->addFlash(FlashType::SUCCESS->value, $this->translator->trans('admin.message.user.success.delete'));

            return $this->redirectToRoute('workspace_admin_users');
        }

        return $this->render('workspace/user-delete.html.twig', [
            'workspace' => $membership->getWorkspace(),
            'membership' => $membership,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @throws PaginatorException
     */
    #[Route('users', name: 'users')]
    #[IsGranted(WorkspaceVoter::USERS_EDIT)]
    public function users(
        Request $request,
        MembershipHandlerInterface $membershipHandler
    ): Response {
        $workspace = $this->getWorkspace();
        $arguments = RequestArgumentHelper::extractArguments(
            request: $request,
            defaultValues: new DefaultRequestValues(limit: 100)
        );

        $memberships = $membershipHandler->filterAndPaginateMemberships(
            workspace: $workspace,
            sortFilterPaginateArguments: $arguments,
        );

        return $this->render('workspace/users.html.twig', [
            'workspace' => $workspace,
            'memberships' => $memberships,
        ]);
    }

    private function getMembership(Hashids $hashids, string $userId, UserRepository $userRepository, MembershipHandlerInterface $membershipHandler): ?Membership
    {
        $decodedUserId = $hashids->decode($userId);
        if (null === $decodedUserId) {
            throw $this->createNotFoundException();
        }
        $member = $userRepository->find($decodedUserId);
        if (null === $member) {
            throw $this->createNotFoundException();
        }
        $workspace = $this->getWorkspace();

        return $membershipHandler->getMembership($member, $workspace);
    }
}
