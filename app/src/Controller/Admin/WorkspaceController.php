<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Dto\Admin\Form\WorkspaceEdit;
use App\Dto\Utility\DefaultRequestValues;
use App\Entity\Workspace;
use App\Enum\FlashType;
use App\Enum\UserRole;
use App\Enum\WorkspaceStatus;
use App\Form\Admin\WorkspaceEditType;
use App\Service\User\MembershipHandlerInterface;
use App\Service\Workspace\WorkspaceHandlerInterface;
use App\Util\Paginator\PaginatorException;
use App\Util\RequestArgumentHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/workspace', name: 'admin_workspace_', defaults: ['domain' => '%app.domain%', 'tld' => '%app.tld%'], host: 'admin.{domain}.{tld}')]
#[IsGranted(UserRole::SUPER_ADMIN->value)]
class WorkspaceController extends AbstractAdminController
{
    /**
     * @throws PaginatorException
     */
    #[Route('/{workspace}', name: 'index')]
    public function index(
        Workspace $workspace,
        Request $request,
        WorkspaceHandlerInterface $workspaceHandler,
        TranslatorInterface $translator,
        MembershipHandlerInterface $membershipHandler
    ): Response {
        $edit = (new WorkspaceEdit())
            ->setStatus(
                WorkspaceStatus::fromWorkspace($workspace)
            )
            ->setAdminNotice($workspace->getAdminNotice());
        $form = $this->createForm(WorkspaceEditType::class, $edit);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $workspaceHandler->updateStatus($workspace, $edit);
            $this->addFlash(FlashType::SUCCESS->value, $translator->trans('admin.message.workspace.success.edit'));

            return $this->redirectToRoute('admin_index');
        }

        $arguments = RequestArgumentHelper::extractArguments(
            request: $request,
            defaultValues: new DefaultRequestValues(limit: 100)
        );

        $memberships = $membershipHandler->filterAndPaginateMemberships(
            workspace: $workspace,
            sortFilterPaginateArguments: $arguments,
        );

        return $this->render('admin/workspace.html.twig', [
            'workspace' => $workspace,
            'memberships' => $memberships,
            'form' => $form->createView(),
        ]);
    }
}
