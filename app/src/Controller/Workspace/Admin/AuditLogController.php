<?php

declare(strict_types=1);

namespace App\Controller\Workspace\Admin;

use App\Controller\Workspace\AbstractWorkspaceController;
use App\Dto\Utility\DefaultRequestValues;
use App\Security\Voter\WorkspaceVoter;
use App\Service\DatabaseLogger\DatabaseLoggerInterface;
use App\Util\Paginator\PaginatorException;
use App\Util\RequestArgumentHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(WorkspaceVoter::VIEW_LOG)]
#[Route('/log', name: 'workspace_log_', defaults: ['domain' => '%app.domain%', 'tld' => '%app.tld%'], host: '{subdomain}.{domain}.{tld}')]
class AuditLogController extends AbstractWorkspaceController
{
    /**
     * @throws PaginatorException
     */
    #[Route('', name: 'index')]
    public function index(
        DatabaseLoggerInterface $databaseLogger,
        Request $request,
    ): Response {
        $workspace = $this->getWorkspace();

        $arguments = RequestArgumentHelper::extractArguments(
            request: $request,
            defaultValues: new DefaultRequestValues(
                limit: 100
            )
        );

        $entries = $databaseLogger->getEntries($workspace, $arguments);

        return $this->render('workspace/audit/index.html.twig', [
            'workspace' => $workspace,
            'entries' => $entries,
        ]);
    }
}
