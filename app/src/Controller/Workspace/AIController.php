<?php

declare(strict_types=1);

namespace App\Controller\Workspace;

use App\Enum\FlashType;
use App\Form\File\AiTagsType;
use App\Message\AITaggingMessage;
use App\Service\File\GetterInterface;
use App\Service\Tag\TagHandlerInterface;
use App\Service\Workspace\WorkspaceIdentifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/ai', name: 'workspace_ai_', defaults: ['domain' => '%app.domain%', 'tld' => '%app.tld%'], host: '{subdomain}.{domain}.{tld}')]
class AIController extends AbstractWorkspaceController
{
    public function __construct(
        private readonly GetterInterface $getter,
        protected WorkspaceIdentifier $workspaceIdentifier
    ) {
        parent::__construct($this->workspaceIdentifier);
    }

    #[Route('/tags/{filename}', name: 'tags')]
    public function triggerAITagging(
        string $filename,
        TranslatorInterface $translator,
        MessageBusInterface $bus,
        TagHandlerInterface $tagHandler,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        [$workspace, $user, $file] = $this->getWorkspaceUserAndFile(fileGetter: $this->getter, filename: $filename);

        $aiTags = $file->getAiTags();
        $form = null;
        if (null === $aiTags) {
            $bus->dispatch(new AITaggingMessage(fileId: $file->getId(), userId: $user->getId()));

            $this->addFlash(type: FlashType::SUCCESS->value, message: $translator->trans('feature.ai.initialized'));
        } else {
            $tags = $tagHandler->getTagStringArray($file);
            $form = $this->createForm(AiTagsType::class, ['tags' => $tags], [
                'aiTags' => array_slice($aiTags, 0, 30),
                'action' => $this->generateUrl('workspace_ai_tags', ['filename' => $filename]),
            ]);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $formData = $form->getData();
                if (
                    !is_array($formData)
                    || !array_key_exists('tags', $formData)
                    || !is_array($formData['tags'])
                ) {
                    $this->addFlash(type: FlashType::ERROR->value, message: $translator->trans('feature.ai.error'));
                } else {
                    $tagHandler->addTags(
                        file: $file,
                        tagString: implode(', ', $formData['tags']),
                        user: $user,
                        ai: true
                    );
                    $entityManager->flush();
                    $this->addFlash(type: FlashType::SUCCESS->value, message: $translator->trans('feature.ai.added'));

                    return $this->redirectToRoute('workspace_file_edit', ['filename' => $filename]);
                }
            }
        }

        if ($request->isXmlHttpRequest()) {
            return $this->render('forms/ai-tagging-form.html.twig', [
                'workspace' => $workspace,
                'file' => $file,
                'aiTags' => $aiTags,
                'fileTags' => $tagHandler->getTagStringArray($file),
                'form' => $form,
            ]);
        }

        return $this->render('workspace/file/ai.html.twig', [
            'workspace' => $workspace,
            'file' => $file,
            'aiTags' => $aiTags,
            'fileTags' => $tagHandler->getTagStringArray($file),
            'form' => $form,
        ]);
    }

    #[Route('/tags/{filename}/status', name: 'tags_status')]
    public function tagStatus(
        string $filename,
    ): JsonResponse {
        /* @noinspection PhpUnusedLocalVariableInspection */
        [$workspace, $user, $file] = $this->getWorkspaceUserAndFile(fileGetter: $this->getter, filename: $filename);

        return $this->json([
            'status' => null === $file->getAiTags() ? 'waiting' : 'ok',
        ]);
    }
}
