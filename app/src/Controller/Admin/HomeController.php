<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Dto\Utility\DefaultRequestValues;
use App\Enum\UserRole;
use App\Service\User\UserHandlerInterface;
use App\Service\Workspace\WorkspaceHandlerInterface;
use App\Util\RequestArgumentHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/', name: 'admin_', defaults: ['domain' => '%app.domain%', 'tld' => '%app.tld%'], host: 'admin.{domain}.{tld}')]
#[IsGranted(UserRole::SUPER_ADMIN->value)]
class HomeController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(
        Request $request,
        WorkspaceHandlerInterface $workspaceHandler
    ): Response {
        $arguments = RequestArgumentHelper::extractArguments(
            request: $request,
            defaultValues: new DefaultRequestValues(limit: 100)
        );

        $workspaces = $workspaceHandler->filterAndPaginateWorkspaces(
            sortFilterPaginateArguments: $arguments,
        );

        return $this->render('admin/index.html.twig', [
            'workspaces' => $workspaces,
        ]);
    }

    #[Route('users', name: 'users')]
    public function users(
        Request $request,
        UserHandlerInterface $userHandler
    ): Response {
        $arguments = RequestArgumentHelper::extractArguments(
            request: $request,
            defaultValues: new DefaultRequestValues(limit: 100)
        );

        $users = $userHandler->filterAndPaginateUsers(
            sortFilterPaginateArguments: $arguments,
        );

        return $this->render('admin/users/index.html.twig', [
            'users' => $users,
        ]);
    }
}
