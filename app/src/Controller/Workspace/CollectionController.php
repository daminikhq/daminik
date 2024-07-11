<?php

declare(strict_types=1);

namespace App\Controller\Workspace;

use App\Dto\Collection\Config;
use App\Dto\Collection\Create;
use App\Dto\Utility\DefaultRequestValues;
use App\Entity\AssetCollection;
use App\Entity\File;
use App\Enum\FlashType;
use App\Exception\File\GetterException;
use App\Exception\FileHandlerException;
use App\Form\Collection\ConfigType;
use App\Form\Collection\CreateType;
use App\Security\Voter\CollectionVoter;
use App\Security\Voter\WorkspaceVoter;
use App\Service\Collection\CollectionHandlerInterface;
use App\Service\File\FilePaginationHandlerInterface;
use App\Service\File\GetterInterface;
use App\Service\Workspace\WorkspaceIdentifier;
use App\Util\ContextHelper;
use App\Util\Paginator\PaginatorException;
use App\Util\RequestArgumentHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/collection', name: 'workspace_collection_', defaults: ['domain' => '%app.domain%', 'tld' => '%app.tld%'], host: '{subdomain}.{domain}.{tld}')]
class CollectionController extends AbstractWorkspaceController
{
    public function __construct(
        private readonly CollectionHandlerInterface $collectionHandler,
        private readonly TranslatorInterface $translator,
        private readonly RouterInterface $router,
        WorkspaceIdentifier $workspaceIdentifier
    ) {
        parent::__construct($workspaceIdentifier);
    }

    #[Route('/', name: 'index')]
    #[IsGranted(WorkspaceVoter::VIEW)]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        [$workspace, $user] = $this->getWorkspaceAndUser();

        $create = new Create();
        $form = $this->createForm(CreateType::class, $create);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $ollection = $this->collectionHandler->createCollection(create: $create, workspace: $workspace, user: $user);
            $entityManager->flush();

            return $this->redirectToRoute(route: 'workspace_collection_collection', parameters: ['slug' => $ollection->getSlug()]);
        }

        $arguments = RequestArgumentHelper::extractArguments(
            request: $request,
            defaultValues: new DefaultRequestValues()
        );
        $collections = $this->collectionHandler->filterAndPaginateCollections(
            workspace: $workspace,
            sortFilterPaginateArguments: $arguments,
        );

        return $this->render('workspace/collection/index.html.twig', [
            'form' => $form,
            'workspace' => $workspace,
            'collections' => $collections,
        ]);
    }

    /**
     * @throws FileHandlerException
     * @throws PaginatorException
     */
    #[Route('/{slug}', name: 'collection')]
    public function collection(
        string $slug,
        FilePaginationHandlerInterface $filePaginationHandler,
        Request $request
    ): Response {
        $workspace = $this->getWorkspace();
        $collection = $this->collectionHandler->getCollectionBySlug($slug, $workspace);
        if (!$collection instanceof AssetCollection) {
            throw $this->createNotFoundException();
        }

        if (!$this->isGranted(CollectionVoter::VIEW, $collection)) {
            throw $this->createNotFoundException();
        }

        $arguments = RequestArgumentHelper::extractArguments(
            request: $request,
            defaultValues: new DefaultRequestValues()
        );

        [$files, $filterOptions, $filterForm] = $this->getFilesAndFilterOptions(
            filePaginationHandler: $filePaginationHandler,
            workspace: $workspace,
            arguments: $arguments,
            additionalFilters: ContextHelper::getRequestFilters(request: $request, collection: $collection)
        );

        if ($arguments->isPaginator()) {
            return $this->renderPaginatorContent($files, $request);
        }

        if (!$this->isGranted(CollectionVoter::EDIT, $collection)) {
            return $this->render(
                'workspace/collection/collection_public.html.twig',
                array_merge($arguments->asViewParameters(), [
                    'workspace' => $workspace,
                    'collection' => $collection,
                    'files' => $files,
                    'filterOptions' => $filterOptions,
                    'filterForm' => $filterForm->createView(),
                ])
            );
        }

        return $this->render(
            'workspace/collection/collection.html.twig',
            array_merge($arguments->asViewParameters(), [
                'workspace' => $workspace,
                'collection' => $collection,
                'files' => $files,
                'filterOptions' => $filterOptions,
                'filterForm' => $filterForm->createView(),
            ])
        );
    }

    #[Route('/{slug}/file/{filename}', name: 'collection_file')]
    public function collectionFile(string $slug, string $filename, GetterInterface $fileGetter): Response
    {
        $workspace = $this->getWorkspace();
        try {
            $file = $fileGetter->getFile(workspace: $workspace, filename: $filename);
        } catch (GetterException) {
            throw $this->createNotFoundException();
        }
        if (!$file instanceof File || null === $file->getFilename()) {
            throw $this->createNotFoundException();
        }

        $collection = $this->collectionHandler->getCollectionBySlug($slug, $workspace);
        if (!$collection instanceof AssetCollection) {
            throw $this->createNotFoundException();
        }

        if (!$this->isGranted(CollectionVoter::VIEW, $collection)) {
            throw $this->createNotFoundException();
        }
        if ($this->isGranted(CollectionVoter::EDIT, $collection)) {
            return $this->redirectToRoute('workspace_file_edit', ['filename' => $filename]);
        }

        return $this->render(
            'workspace/collection/collection_public_file.html.twig',
            [
                'workspace' => $workspace,
                'collection' => $collection,
                'file' => $file,
            ]
        );
    }

    #[Route('/{slug}/delete', name: 'delete')]
    #[IsGranted(WorkspaceVoter::VIEW)]
    public function collectionDelete(
        string $slug,
        Request $request,
    ): Response {
        [$workspace, $user] = $this->getWorkspaceAndUser();
        $collection = $this->collectionHandler->getCollectionBySlug($slug, $workspace);
        if (!$collection instanceof AssetCollection) {
            throw $this->createNotFoundException();
        }

        if (!$this->isGranted(CollectionVoter::DELETE, $collection)) {
            return $this->setDeleteErrorFlashAndRedirect($collection);
        }

        $form = $this->getDeleteForm($collection);
        if (!$form instanceof FormInterface) {
            return $this->setDeleteErrorFlashAndRedirect($collection);
        }
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->collectionHandler->deleteCollection($collection, $user);
                $this->addFlash(FlashType::SUCCESS->value, $this->translator->trans('collection.delete.success'));

                return $this->redirectToRoute('workspace_collection_index');
            } catch (\Throwable) {
                return $this->setDeleteErrorFlashAndRedirect($collection);
            }
        }

        return $this->render('workspace/collection/delete.html.twig', [
            'collection' => $collection,
            'workspace' => $workspace,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{slug}/config', name: 'config')]
    #[IsGranted(WorkspaceVoter::VIEW)]
    public function collectionConfig(
        string $slug,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        [$workspace, $user] = $this->getWorkspaceAndUser();
        $collection = $this->collectionHandler->getCollectionBySlug($slug, $workspace);
        if (!$collection instanceof AssetCollection) {
            throw $this->createNotFoundException();
        }

        $config = (new Config())
            ->setTitle($collection->getTitle())
            ->setSlug($collection->getSlug())
            ->setPublic($collection->isPublic());

        $form = $this->createForm(ConfigType::class, $config);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->collectionHandler->updateCollectionConfig(config: $config, collection: $collection, user: $user);
            $entityManager->flush();

            return $this->redirectToRoute(route: 'workspace_collection_collection', parameters: ['slug' => $collection->getSlug()]);
        }

        return $this->render('workspace/collection/config.html.twig', [
            'form' => $form,
            'workspace' => $workspace,
            'collection' => $collection,
        ]);
    }

    private function getDeleteForm(?AssetCollection $assetCollection): ?FormInterface
    {
        if (!$assetCollection instanceof AssetCollection || null === $assetCollection->getSlug()) {
            return null;
        }
        if (!$this->isGranted(CollectionVoter::DELETE, $assetCollection)) {
            return null;
        }

        return $this->createFormBuilder([], [
            'action' => $this->router->generate('workspace_collection_delete', ['slug' => $assetCollection->getSlug()]),
            'translation_domain' => 'form',
        ])
            ->add('deleteCollection', SubmitType::class, [
                'label' => 'button.deleteCollection',
                'attr' => [
                    'class' => 'button',
                ],
            ])
            ->getForm();
    }

    private function setDeleteErrorFlashAndRedirect(AssetCollection $collection): RedirectResponse
    {
        $this->addFlash(FlashType::ERROR->value, $this->translator->trans('collection.delete.error.notPossible'));

        return $this->redirectToRoute('workspace_collection_collection', ['slug' => $collection->getSlug()]);
    }
}
