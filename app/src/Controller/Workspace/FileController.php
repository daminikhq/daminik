<?php

declare(strict_types=1);

namespace App\Controller\Workspace;

use App\Dto\Category\Create;
use App\Dto\File\Edit;
use App\Dto\File\Rename;
use App\Dto\File\Revision;
use App\Entity\File;
use App\Enum\FlashType;
use App\Enum\HandleDeleteAction;
use App\Exception\File\GetterException;
use App\Exception\File\MissingWorkspaceException;
use App\Exception\FileHandlerException;
use App\Exception\UserNotFoundException;
use App\Form\Category\CreateForFileType;
use App\Form\Collection\CreateType;
use App\Form\File\DeleteType;
use App\Form\File\EditType;
use App\Form\File\HandleDeletedType;
use App\Form\File\RenameType;
use App\Form\File\RevisionType;
use App\Message\Filesize\UpdateUploadSizesMessage;
use App\Message\PostUpload\CreateThumbnailMessage;
use App\Message\PostUpload\ReadMetadataMessage;
use App\Security\Voter\FileVoter;
use App\Security\Voter\WorkspaceVoter;
use App\Service\Category\CategoryHandlerInterface;
use App\Service\Collection\CollectionHandlerInterface;
use App\Service\File\DeleterInterface;
use App\Service\File\FileHandler;
use App\Service\File\GetterInterface;
use App\Service\File\Helper\UrlHelperInterface;
use App\Service\File\MetadataHandler;
use App\Service\File\UserMetaDataHandler;
use App\Service\Tag\TagHandlerInterface;
use App\Service\Workspace\WorkspaceIdentifier;
use App\Util\Base64FileExtractor;
use App\Util\UploadedBase64File;
use App\Util\XmlHttpRequestForm;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted(WorkspaceVoter::VIEW)]
#[Route('/file', name: 'workspace_file_', defaults: ['domain' => '%app.domain%', 'tld' => '%app.tld%'], host: '{subdomain}.{domain}.{tld}')]
class FileController extends AbstractWorkspaceController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly GetterInterface $fileGetter,
        WorkspaceIdentifier $workspaceIdentifier
    ) {
        parent::__construct($workspaceIdentifier);
    }

    /**
     * @throws FileHandlerException
     */
    #[Route('/edit/{filename}', name: 'edit')]
    public function edit(
        string $filename,
        Request $request,
        FileHandler $fileHandler,
        TagHandlerInterface $tagHandler,
        CategoryHandlerInterface $categoryHandler,
        CollectionHandlerInterface $collectionHandler,
        EntityManagerInterface $entityManager,
        UrlHelperInterface $urlHelper,
        MetadataHandler $metadataHandler,
        DeleterInterface $fileDeleter
    ): Response {
        [$workspace, $user, $file] = $this->getWorkspaceUserAndFile(fileGetter: $this->fileGetter, filename: $filename, includeDeleted: true);
        if (!$request->isMethod('GET') && !$this->isGranted(WorkspaceVoter::UPLOAD_ASSET, $workspace)) {
            throw $this->createAccessDeniedException();
        }

        $deleted = null !== $file->getDeletedAt();

        $edit = new Edit(
            title: $file->getTitle(),
            description: $file->getDescription(),
            public: $file->isPublic(),
            tags: $tagHandler->getTagString($file),
            category: $categoryHandler->getFileCategory($file),
            assetCollections: $collectionHandler->getFileCollections($file),
        );
        $form = $this->createForm(type: EditType::class, data: $edit, options: ['workspace' => $workspace]);

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $fileHandler->editFile(file: $file, edit: $edit, user: $user);
                $entityManager->flush();

                return $this->handleEditSuccess(file: $file, request: $request, form: $form);
            }

            if ($request->isXmlHttpRequest()) {
                return $this->json(data: XmlHttpRequestForm::jsonResultFromForm($form), status: 422);
            }
        }

        $privateUrl = $urlHelper->getPrivateUrl($file);

        $filemetaData = $metadataHandler->getMetadata($file->getActiveRevision());

        $deleteForm = null;
        if ($deleted) {
            $deleteForm = $this->createForm(type: HandleDeletedType::class, data: $file);
            $deleteForm->handleRequest($request);
            if ($deleteForm->isSubmitted()) {
                if ($deleteForm->isValid() && method_exists($deleteForm, 'getClickedButton')) {
                    $deleteFormAction = HandleDeleteAction::tryFrom((string) $deleteForm->getClickedButton()?->getName());
                    $fileDeleter->handleDeletionForm($file, $deleteFormAction);
                    switch ($deleteFormAction) {
                        case HandleDeleteAction::UNDELETE:
                            return $this->handleEditSuccess(file: $file, request: $request, form: $form);
                        case HandleDeleteAction::DELETE:
                            return $this->handleDeleteSuccess($file, $request, $form);
                    }
                    if ($request->isXmlHttpRequest()) {
                        return $this->json(data: XmlHttpRequestForm::jsonResultFromForm($deleteForm), status: 422);
                    }

                    return $this->redirectToRoute(route: 'workspace_file_edit', parameters: ['filename' => $file->getFilename()]);
                }

                if ($request->isXmlHttpRequest()) {
                    return $this->json(data: XmlHttpRequestForm::jsonResultFromForm($deleteForm), status: 422);
                }
            }
        }

        return $this->render(view: 'workspace/file/view.html.twig', parameters: [
            'workspace' => $workspace,
            'file' => $file,
            'form' => $form,
            'deleteForm' => $deleteForm,
            'fileUrl' => $privateUrl,
            'fileMetaData' => $filemetaData,
            'deleted' => $deleted,
        ]);
    }

    /**
     * @throws FileHandlerException
     */
    #[Route('/edit/{filename}/rename', name: 'rename')]
    public function rename(
        string $filename,
        FileHandler $fileHandler,
        Request $request
    ): Response {
        [$workspace, $user, $file] = $this->getWorkspaceUserAndFile(fileGetter: $this->fileGetter, filename: $filename, includeDeleted: true);
        if (!$request->isMethod('GET') && !$this->isGranted(WorkspaceVoter::EDIT_ASSET, $workspace)) {
            throw $this->createAccessDeniedException();
        }

        $renameDto = (new Rename())->setSlug($file->getPublicFilenameSlug());
        $form = $this->createForm(RenameType::class, $renameDto, [
            'action' => $this->generateUrl('workspace_file_rename', ['filename' => $filename]),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $file = $fileHandler->renameFile($file, $renameDto, $user);

            return $this->redirectToRoute('workspace_file_edit', ['filename' => $file->getFilename()]);
        }

        if ($request->isXmlHttpRequest()) {
            return $this->render(view: 'forms/asset-rename-form.html.twig', parameters: [
                'workspace' => $workspace,
                'file' => $file,
                'form' => $form->createView(),
            ]);
        }

        return $this->render(view: 'workspace/file/rename.html.twig', parameters: [
            'workspace' => $workspace,
            'file' => $file,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/edit/{filename}/folder', name: 'new_folder')]
    public function newFolder(string $filename, CategoryHandlerInterface $categoryHandler, Request $request): Response
    {
        [$workspace, $user, $file] = $this->getWorkspaceUserAndFile(fileGetter: $this->fileGetter, filename: $filename, includeDeleted: true);
        if (
            !$this->isGranted(WorkspaceVoter::CREATE_CATEGORIES, $workspace)
            || (!$request->isMethod('GET')
                && !$this->isGranted(WorkspaceVoter::EDIT_ASSET, $workspace))
        ) {
            throw $this->createAccessDeniedException();
        }

        $create = new Create();
        $form = $this->createForm(CreateForFileType::class, $create, [
            'workspace' => $workspace,
            'action' => $this->generateUrl('workspace_file_new_folder', ['filename' => $filename]),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $category = $categoryHandler->createCategory($create, $workspace, $user, $file);
            $message = sprintf($this->translator->trans('file.addFolderSuccess'), $file->getFilename());

            if ($request->isXmlHttpRequest()) {
                return $this->json(
                    XmlHttpRequestForm::jsonResultFromForm(
                        form: $form,
                        message: $message,
                        body: [
                            'slug' => $category->getSlug(),
                            'title' => $category->getTitle(),
                        ]
                    )->toArray(true),
                );
            }

            return $this->redirectToRoute('workspace_file_edit', ['filename' => $file->getFilename()]);
        }

        if ($request->isXmlHttpRequest()) {
            return $this->render(view: 'forms/category-create-form.html.twig', parameters: [
                'workspace' => $workspace,
                'file' => $file,
                'form' => $form->createView(),
            ]);
        }

        return $this->render(view: 'workspace/file/new-folder.html.twig', parameters: [
            'workspace' => $workspace,
            'file' => $file,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/edit/{filename}/collection', name: 'new_collection')]
    public function newCollection(string $filename, CollectionHandlerInterface $collectionHandler, Request $request): Response
    {
        [$workspace, $user, $file] = $this->getWorkspaceUserAndFile(fileGetter: $this->fileGetter, filename: $filename, includeDeleted: true);
        if (
            !$this->isGranted(WorkspaceVoter::CREATE_COLLECTION, $workspace)
            || (!$request->isMethod('GET')
                && !$this->isGranted(WorkspaceVoter::EDIT_ASSET, $workspace))
        ) {
            throw $this->createAccessDeniedException();
        }

        $create = new \App\Dto\Collection\Create();
        $form = $this->createForm(CreateType::class, $create, [
            'action' => $this->generateUrl('workspace_file_new_collection', ['filename' => $filename]),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $collection = $collectionHandler->createCollection($create, $workspace, $user, $file);
            $message = sprintf($this->translator->trans('file.newCollectionSuccess'), $file->getFilename());

            if ($request->isXmlHttpRequest()) {
                return $this->json(
                    XmlHttpRequestForm::jsonResultFromForm(
                        form: $form,
                        message: $message,
                        body: [
                            'slug' => $collection->getSlug(),
                            'title' => $collection->getTitle(),
                        ]
                    )->toArray(true),
                );
            }

            return $this->redirectToRoute('workspace_file_edit', ['filename' => $file->getFilename()]);
        }

        if ($request->isXmlHttpRequest()) {
            return $this->render(view: 'forms/collection-create-form.html.twig', parameters: [
                'workspace' => $workspace,
                'file' => $file,
                'form' => $form->createView(),
            ]);
        }

        return $this->render(view: 'workspace/file/new-collection.html.twig', parameters: [
            'workspace' => $workspace,
            'file' => $file,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @throws FileHandlerException
     * @throws FilesystemException
     * @throws \JsonException
     * @throws MissingWorkspaceException
     * @throws ExceptionInterface
     *
     * @noinspection DuplicatedCode
     */
    #[IsGranted(WorkspaceVoter::UPLOAD_ASSET)]
    #[Route('/editorupload/{filename}', name: 'editor', methods: ['POST'])]
    public function editorUpload(
        Request $request,
        FileHandler $fileHandler,
        EntityManagerInterface $entityManager,
        MessageBusInterface $bus
    ): JsonResponse {
        $parameters = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        if (is_array($parameters) && array_key_exists('filename', $parameters) && array_key_exists('image', $parameters)) {
            /** @var string $filename */
            $filename = $parameters['filename'];
            /* @noinspection PhpUnusedLocalVariableInspection */
            [$workspace, $user, $file] = $this->getWorkspaceUserAndFile(fileGetter: $this->fileGetter, filename: $filename);
        } else {
            throw $this->createAccessDeniedException();
        }
        $upload = new Revision($user, $file);
        $base64Image = Base64FileExtractor::extractBase64String($parameters['image']);
        $uploadedFile = new UploadedBase64File($base64Image, $filename);
        $revision = $fileHandler->saveRevision($upload, $uploadedFile, $user);
        $entityManager->flush();
        if (is_int($file->getId())) {
            if (is_int($revision->getId())) {
                $bus->dispatch(new UpdateUploadSizesMessage($file->getId(), $revision->getId()));
            }
            $bus->dispatch(new CreateThumbnailMessage($file->getId(), $revision->getId()));
            $bus->dispatch(new ReadMetadataMessage($file->getId(), $revision->getId()));
        }

        return $this->json([
            'data' => [
                'message' => sprintf($this->translator->trans('file.updateSuccess'), $file->getFilename()),
                'redirectTo' => $this->generateUrl(route: 'workspace_file_edit', parameters: ['filename' => $file->getFilename()]),
            ],
        ]);
    }

    /**
     * @throws FilesystemException
     * @throws FileHandlerException
     * @throws MissingWorkspaceException
     * @throws ExceptionInterface
     *
     * @noinspection DuplicatedCode
     */
    #[Route('/edit/{filename}/revisions', name: 'revisions')]
    public function revisions(
        string $filename,
        Request $request,
        FileHandler $fileHandler,
        UrlHelperInterface $urlHelper,
        EntityManagerInterface $entityManager,
        MessageBusInterface $bus
    ): Response {
        [$workspace, $user, $file] = $this->getWorkspaceUserAndFile(fileGetter: $this->fileGetter, filename: $filename);
        if (!$request->isMethod('GET') && !$this->isGranted(WorkspaceVoter::UPLOAD_ASSET, $workspace)) {
            throw $this->createAccessDeniedException();
        }

        $upload = new Revision($user, $file);
        $form = $this->createForm(RevisionType::class, $upload);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $uploadedFile = $form->get('file')->getData();
            if (!$uploadedFile instanceof UploadedFile) {
                throw new \RuntimeException();
            }

            $revision = $fileHandler->saveRevision($upload, $uploadedFile, $user);
            $entityManager->flush();

            if (is_int($file->getId())) {
                if (is_int($revision->getId())) {
                    $bus->dispatch(new UpdateUploadSizesMessage($file->getId(), $revision->getId()));
                }
                $bus->dispatch(new CreateThumbnailMessage($file->getId(), $revision->getId()));
                $bus->dispatch(new ReadMetadataMessage($file->getId(), $revision->getId()));
            }

            return $this->redirectToRoute('workspace_file_revisions', ['filename' => $file->getFilename()]);
        }

        $privateUrl = $urlHelper->getPrivateUrl($file);
        $entityManager->flush();

        return $this->render('workspace/file/revisions.html.twig', [
            'workspace' => $workspace,
            'file' => $file,
            'form' => $form,
            'fileUrl' => $privateUrl,
        ]);
    }

    #[Route('/favorite/{filename}', name: 'favorite', methods: ['POST'])]
    public function toggleFavorite(
        string $filename,
        UserMetaDataHandler $metaDataHandler,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        [$workspace, $user, $file] = $this->getWorkspaceUserAndFile(fileGetter: $this->fileGetter, filename: $filename);

        try {
            $favorite = $metaDataHandler->markAsFavorite($file, $user);
            $entityManager->flush();
        } catch (UserNotFoundException $e) {
            throw $this->createAccessDeniedException($this->translator->trans('file.accessDenied'), $e);
        }

        return $this->json([
            'workspace' => $workspace->getSlug(),
            'file' => $file->getFilename(),
            'favorite' => $favorite,
        ]);
    }

    /**
     * @throws FileHandlerException
     */
    #[Route('/edit/{filename}/revisions/update', name: 'revisions_update', methods: ['POST'])]
    public function updateRevision(string $filename, Request $request, FileHandler $fileHandler): Response
    {
        if (!$this->isCsrfTokenValid('update_active_revision', (string) $request->request->get('token'))) {
            $this->addFlash(FlashType::ERROR->value, $this->translator->trans('file.tryAgainCsrf'));

            return $this->redirectToRoute('workspace_file_revisions', ['filename' => $filename]);
        }
        /* @noinspection PhpUnusedLocalVariableInspection */
        [$workspace, $user, $file] = $this->getWorkspaceUserAndFile(fileGetter: $this->fileGetter, filename: $filename);

        $fileHandler->setRevisionById($file, (string) $request->request->get('revision'), $user);

        return $this->redirectToRoute('workspace_file_revisions', ['filename' => $filename]);
    }

    /**
     * @throws FileHandlerException
     * @throws MissingWorkspaceException
     * @throws GetterException
     * @throws ExceptionInterface
     */
    #[Route('/delete/{filename}', name: 'delete')]
    public function delete(string $filename, Request $request, FileHandler $fileHandler, EntityManagerInterface $entityManager): Response
    {
        $workspace = $this->getWorkspace();

        $file = $this->fileGetter->getFile(workspace: $workspace, filename: $filename);
        if (!$file instanceof File || null === $file->getFilename()) {
            throw $this->createNotFoundException();
        }

        if (!$this->isGranted(FileVoter::DELETE, $file)) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(DeleteType::class, $file);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $fileHandler->deleteFile($file);
            $entityManager->flush();
            $this->addFlash(FlashType::SUCCESS->value, sprintf($this->translator->trans('file.deletedSuccess'), $file->getFilename()));

            return $this->redirectToRoute('workspace_index');
        }

        return $this->render('workspace/file/delete.html.twig', [
            'workspace' => $workspace,
            'file' => $file,
            'form' => $form,
        ]);
    }

    private function handleEditSuccess(File $file, Request $request, FormInterface $form): JsonResponse|RedirectResponse
    {
        $message = sprintf($this->translator->trans('file.updateSuccess'), $file->getFilename());

        if ($request->isXmlHttpRequest()) {
            return $this->json(
                XmlHttpRequestForm::jsonResultFromForm(
                    form: $form,
                    message: $message
                )->toArray(true),
            );
        }

        $this->addFlash(type: FlashType::SUCCESS->value, message: $message);

        return $this->redirectToRoute(route: 'workspace_file_edit', parameters: ['filename' => $file->getFilename()]);
    }

    private function handleDeleteSuccess(File $file, Request $request, FormInterface $form): RedirectResponse|JsonResponse
    {
        $message = sprintf($this->translator->trans('file.deletedSuccess'), $file->getFilename());

        if ($request->isXmlHttpRequest()) {
            return $this->json(
                XmlHttpRequestForm::jsonResultFromForm(
                    form: $form,
                    redirectTo: $this->generateUrl(route: 'workspace_index'),
                    message: $message,
                )->toArray(true),
            );
        }

        $this->addFlash(type: FlashType::SUCCESS->value, message: $message);

        return $this->redirectToRoute(route: 'workspace_index');
    }
}
