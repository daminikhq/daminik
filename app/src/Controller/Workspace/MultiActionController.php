<?php

declare(strict_types=1);

namespace App\Controller\Workspace;

use App\Dto\File\MultiAction as MultiActionDto;
use App\Dto\Response\FormResponse;
use App\Entity\User;
use App\Enum\FlashType;
use App\Enum\FormStatus;
use App\Enum\MultiAction;
use App\Form\File\MultiActionType;
use App\Service\Collection\CollectionHandlerInterface;
use App\Service\File\GetterInterface;
use App\Service\File\MultiActionHandlerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/', name: 'workspace_', defaults: ['domain' => '%app.domain%', 'tld' => '%app.tld%'], host: '{subdomain}.{domain}.{tld}')]
class MultiActionController extends AbstractWorkspaceController
{
    #[Route('multiaction', name: 'multiaction')]
    public function multiAction(
        Request $request,
        MultiActionHandlerInterface $multiActionHandler,
        TranslatorInterface $translator,
        EntityManagerInterface $entityManager,
        CollectionHandlerInterface $collectionHandler,
        GetterInterface $getter
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        $workspace = $this->getWorkspace();
        $queryParameters = $request->query->all();
        if (!array_key_exists('fileNames', $queryParameters)) {
            $this->addFlash(FlashType::ERROR->value, $translator->trans('multiaction.error.noAssetsSelected'));

            return $this->redirectToRoute('workspace_index', ['workspace' => $this->getWorkspace()]);
        }
        $fileNames = $queryParameters['fileNames'];
        $action = $request->query->get('multiaction');
        $collectionSlug = $request->query->get('collection');
        $collection = null;
        if (null !== $collectionSlug) {
            $collectionSlug = (string) $collectionSlug;
            $collection = $collectionHandler->getCollectionBySlug($collectionSlug, $workspace);
        }

        if (!is_array($fileNames) || !is_string($action)) {
            return $this->returnInvalidDataJsonResponse($translator);
        }
        $multiAction = MultiAction::tryFrom(value: $action);
        if (!$multiAction instanceof MultiAction) {
            return $this->returnInvalidDataJsonResponse($translator);
        }

        $files = $getter->getFiles(workspace: $this->getWorkspace(), filename: $fileNames);
        $multiActionDto = new MultiActionDto(
            action: $multiAction,
            user: $user,
            files: $files,
            collection: $collection
        );

        $form = $this->createForm(type: MultiActionType::class, data: $multiActionDto);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $multiActionResponse = $multiActionHandler->handleMultiAction($multiActionDto);
            if (FormStatus::OK === $multiActionResponse->getStatus()) {
                $entityManager->flush();
                $redirectTo = $multiActionResponse->getRedirectTo();
                if (null !== $redirectTo) {
                    return $this->redirect($redirectTo);
                }

                return $this->redirectToRoute('workspace_index', ['workspace' => $this->getWorkspace()]);
            }
            $this->addFlash(FlashType::ERROR->value, $multiActionResponse->getMessage());
        }

        return $this->render(
            view: 'workspace/multiaction.html.twig',
            parameters: [
                'multiAction' => $multiActionDto,
                'workspace' => $this->getWorkspace(),
                'form' => $form,
            ]
        );
    }

    private function returnInvalidDataJsonResponse(TranslatorInterface $translator): JsonResponse
    {
        $response = (new FormResponse())
            ->setStatus(FormStatus::ERROR)
            ->setMessage($translator->trans(id: 'error.invalid', domain: 'form'));

        return $this->json(
            data: $response->toArray(true),
            status: 422
        );
    }
}
