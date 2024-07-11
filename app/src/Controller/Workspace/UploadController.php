<?php

declare(strict_types=1);

namespace App\Controller\Workspace;

use App\Dto\File\Upload;
use App\Entity\User;
use App\Exception\FileHandlerException;
use App\Form\File\UploadType;
use App\Message\Filesize\UpdateUploadSizesMessage;
use App\Message\PostUpload\CreateThumbnailMessage;
use App\Message\PostUpload\ReadMetadataMessage;
use App\Security\Voter\WorkspaceVoter;
use App\Service\File\FileHandler;
use App\Util\XmlHttpRequestForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(WorkspaceVoter::UPLOAD_ASSET)]
#[Route('', name: 'workspace_upload_', defaults: ['domain' => '%app.domain%', 'tld' => '%app.tld%'], host: '{subdomain}.{domain}.{tld}')]
class UploadController extends AbstractWorkspaceController
{
    /**
     * @throws FileHandlerException
     * @throws ExceptionInterface
     *
     * @noinspection DuplicatedCode
     */
    #[Route('/upload', name: 'index')]
    public function index(
        Request $request,
        FileHandler $fileHandler,
        MessageBusInterface $bus,
        EntityManagerInterface $entityManager,
        RouterInterface $router
    ): Response {
        $workspace = $this->getWorkspace();

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $upload = new Upload(
            uploader: $user,
            workspace: $workspace,
            context: $request->headers->get('X-Daminik-Context') ?? 'home'
        );
        $form = $this->createForm(UploadType::class, $upload);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $uploadedFile = $form->get('file')->getData();
                if (!$uploadedFile instanceof UploadedFile) {
                    throw new \RuntimeException();
                }

                $file = $fileHandler->saveUploadedFile($upload, $uploadedFile);
                $entityManager->flush();
                if (is_int($file->getId())) {
                    if (is_int($file->getActiveRevision()?->getId())) {
                        $bus->dispatch(new UpdateUploadSizesMessage($file->getId(), $file->getActiveRevision()->getId()));
                    }
                    $bus->dispatch(new CreateThumbnailMessage($file->getId(), $file->getActiveRevision()?->getId()));
                    $bus->dispatch(new ReadMetadataMessage($file->getId(), $file->getActiveRevision()?->getId()));
                }

                if ($request->isXmlHttpRequest()) {
                    return $this->json(
                        XmlHttpRequestForm::jsonResultFromForm(form: $form, redirectTo: $router->generate('workspace_index'))->toArray(true),
                    );
                }

                return $this->redirectToRoute('workspace_index');
            }

            if ($request->isXmlHttpRequest()) {
                return $this->json(XmlHttpRequestForm::jsonResultFromForm($form), 422);
            }
        }

        return $this->render('workspace/file/upload.html.twig', [
            'controller_name' => self::class,
            'workspace' => $workspace,
            'uploadForm' => $form,
        ]);
    }

    /**
     * @throws FileHandlerException
     * @throws ExceptionInterface
     *
     * @noinspection DuplicatedCode
     */
    #[Route('/xhrupload', name: 'xhr')]
    public function xhrupload(
        Request $request,
        FileHandler $fileHandler,
        MessageBusInterface $bus,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $workspace = $this->getWorkspace();

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        $upload = new Upload(
            uploader: $user,
            workspace: $workspace,
            context: $request->headers->get('X-Daminik-Context') ?? 'home'
        );

        $uploadedFile = $request->files->get('file');
        if (!$uploadedFile instanceof UploadedFile) {
            return $this->json(['message' => 'File is not a file'], 400);
        }

        $file = $fileHandler->saveUploadedFile($upload, $uploadedFile);
        $entityManager->flush();
        if (is_int($file->getId())) {
            if (is_int($file->getActiveRevision()?->getId())) {
                $bus->dispatch(new UpdateUploadSizesMessage($file->getId(), $file->getActiveRevision()->getId()));
            }
            $bus->dispatch(new CreateThumbnailMessage($file->getId(), $file->getActiveRevision()?->getId()));
            $bus->dispatch(new ReadMetadataMessage($file->getId(), $file->getActiveRevision()?->getId()));
        }

        return $this->json([]);
    }
}
