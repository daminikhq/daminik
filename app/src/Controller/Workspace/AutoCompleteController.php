<?php

declare(strict_types=1);

namespace App\Controller\Workspace;

use App\Interfaces\AutoCompleteQueriable;
use App\Security\Voter\WorkspaceVoter;
use App\Service\Collection\CollectionHandlerInterface;
use App\Service\Tag\TagHandlerInterface;
use App\Service\User\UserHandlerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(WorkspaceVoter::VIEW)]
#[Route('/ac', name: 'workspace_autocomplete_', defaults: ['domain' => '%app.domain%', 'tld' => '%app.tld%'], host: '{subdomain}.{domain}.{tld}')]
class AutoCompleteController extends AbstractWorkspaceController
{
    #[Route('/users', name: 'users')]
    public function users(UserHandlerInterface $userHandler, Request $request): JsonResponse
    {
        return $this->returnAutoCompleteResponse(service: $userHandler, request: $request);
    }

    #[Route('/tags', name: 'tags')]
    public function tags(TagHandlerInterface $tagHandler, Request $request): JsonResponse
    {
        return $this->returnAutoCompleteResponse($tagHandler, $request);
    }

    #[Route('/collections', name: 'collections')]
    public function collections(CollectionHandlerInterface $collectionHandler, Request $request): JsonResponse
    {
        return $this->returnAutoCompleteResponse($collectionHandler, $request);
    }

    private function returnAutoCompleteResponse(
        AutoCompleteQueriable $service,
        Request $request
    ): JsonResponse {
        $workspace = $this->getWorkspace();
        $query = $request->get('query');
        if (!is_string($query) || '' === $query) {
            $query = null;
        }
        $tags = $service->getForAutocomplete(workspace: $workspace, query: $query);
        $tagArray = [];
        foreach ($tags as $tag) {
            $tagArray[] = [
                'value' => $tag->getValue(),
                'text' => $tag->getText(),
            ];
        }

        return $this->json([
            'results' => $tagArray,
        ]);
    }
}
