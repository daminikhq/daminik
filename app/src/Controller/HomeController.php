<?php

namespace App\Controller;

use App\Dto\NewWorkspace;
use App\Entity\User;
use App\Entity\Workspace;
use App\Enum\FlashType;
use App\Exception\WorkspaceException;
use App\Exception\WorkspaceExistsException;
use App\Form\NewWorkspaceType;
use App\Security\Voter\WorkspaceVoter;
use App\Service\Workspace\CreatorInterface;
use App\Service\Workspace\Inviter;
use App\Service\Workspace\Inviter\InvalidInvitationException;
use App\Service\Workspace\Inviter\UnknownCodeException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/', name: 'home_', defaults: ['domain' => '%app.domain%', 'tld' => '%app.tld%'], host: '{domain}.{tld}')]
class HomeController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(
        Request $request,
    ): Response {
        $user = $this->getUser();
        if ($user instanceof UserInterface) {
            return $this->redirectToRoute('home_dashboard');
        }

        $ref = $request->get('ref');
        if (is_string($ref)) {
            $session = $request->getSession();
            $session->set('ref', $ref);
        }

        return $this->redirectToRoute('login');
    }

    #[Route('dashboard', name: 'dashboard')]
    public function dashboard(): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('home_index');
        }

        if ($user->getMemberships()->count() < 1) {
            return $this->redirectToRoute('home_new_workspace');
        }

        $workspace = $user->getLastUsedWorkspace();
        if ($workspace instanceof Workspace && 'global' !== $workspace->getSlug() && null !== $workspace->getSlug() && $this->isGranted(WorkspaceVoter::VIEW, $workspace)) {
            return $this->redirectToRoute('workspace_index', ['subdomain' => $workspace->getSlug()]);
        }

        foreach ($user->getMemberships() as $membership) {
            $workspace = $membership->getWorkspace();
            if ($workspace instanceof Workspace && 'global' !== $workspace->getSlug() && null !== $workspace->getSlug() && $this->isGranted(WorkspaceVoter::VIEW, $workspace)) {
                return $this->redirectToRoute('workspace_index', ['subdomain' => $workspace->getSlug()]);
            }
        }

        return $this->render('home/dashboard.html.twig');
    }

    /**
     * @throws WorkspaceException
     */
    #[Route('new', name: 'new_workspace')]
    public function workspace(
        Request $request,
        CreatorInterface $workspaceService,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('home_index');
        }

        $newWorkspace = (new NewWorkspace())->setLocale($request->getLocale());
        $form = $this->createForm(NewWorkspaceType::class, $newWorkspace);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $workspace = $workspaceService->createWorkspace($user, $newWorkspace);
                $entityManager->flush();

                return $this->redirectToRoute('workspace_index', ['subdomain' => $workspace->getSlug()]);
            } catch (WorkspaceExistsException) {
                $this->addFlash(FlashType::ERROR->value, 'Workspace exists');
            }
        }

        return $this->render('home/new-workspace.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('invitation/{code}', name: 'invitation')]
    public function invitation(string $code, Request $request, Inviter $inviter, TranslatorInterface $translator): Response
    {
        try {
            $inviter->validateCode($code);
        } catch (InvalidInvitationException) {
            $this->addFlash(FlashType::ERROR->value, $translator->trans('inviter.invalidInvitation'));

            return $this->redirectToRoute('home_index');
        } catch (UnknownCodeException) {
            $this->addFlash(FlashType::ERROR->value, $translator->trans('inviter.unknownCode'));

            return $this->redirectToRoute('home_index');
        }

        $request->getSession()->set(Inviter::SESSION_NAME, $code);

        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('register', ['invitation' => $code]);
        }

        try {
            $workspace = $inviter->addUserByInvitationCode($code, $user);
        } catch (InvalidInvitationException) {
            $this->addFlash(FlashType::ERROR->value, $translator->trans('inviter.invalidInvitation'));
            $workspace = null;
        } catch (UnknownCodeException) {
            $this->addFlash(FlashType::ERROR->value, $translator->trans('inviter.unknownCode'));
            $workspace = null;
        }

        if (!$workspace instanceof Workspace) {
            throw $this->createAccessDeniedException();
        }

        return $this->redirectToRoute('workspace_index', ['subdomain' => $workspace->getSlug()]);
    }
}
