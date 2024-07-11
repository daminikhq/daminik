<?php

declare(strict_types=1);

namespace App\Controller\Workspace;

use App\Dto\Category\Create;
use App\Dto\Category\Edit;
use App\Dto\Utility\DefaultRequestValues;
use App\Dto\Utility\SortFilterPaginateArguments;
use App\Entity\Category;
use App\Enum\FlashType;
use App\Exception\FileHandlerException;
use App\Form\Category\CreateType;
use App\Form\Category\EditType;
use App\Security\Voter\CategoryVoter;
use App\Security\Voter\WorkspaceVoter;
use App\Service\Category\CategoryHandlerInterface;
use App\Service\File\FilePaginationHandlerInterface;
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
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/folder', name: 'workspace_folder_', defaults: ['domain' => '%app.domain%', 'tld' => '%app.tld%'], host: '{subdomain}.{domain}.{tld}')]
class CategoryController extends AbstractWorkspaceController
{
    public function __construct(
        WorkspaceIdentifier $workspaceIdentifier,
        private readonly CategoryHandlerInterface $categoryHandler,
        private readonly TranslatorInterface $translator,
        private readonly RouterInterface $router
    ) {
        parent::__construct($workspaceIdentifier);
    }

    #[Route('/{slug}/delete', name: 'delete')]
    public function delete(
        Request $request,
        string $slug
    ): Response {
        [$workspace, $user] = $this->getWorkspaceAndUser();

        $category = $this->categoryHandler->getCategoryBySlug($slug, $workspace);
        if (!$category instanceof Category) {
            throw $this->createNotFoundException();
        }

        if (!$this->isGranted(CategoryVoter::DELETE, $category)) {
            return $this->setDeleteErrorFlashAndRedirect($category);
        }

        $parent = $category->getParent();
        $form = $this->getDeleteForm($category);
        if (!$form instanceof FormInterface) {
            return $this->setDeleteErrorFlashAndRedirect($category);
        }
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->categoryHandler->deleteCategory($category, $user);
                $this->addFlash(FlashType::SUCCESS->value, $this->translator->trans('category.delete.success'));
                if ($parent instanceof Category) {
                    return $this->redirectToRoute('workspace_folder_index', ['slug' => $parent->getSlug()]);
                }

                return $this->redirectToRoute('workspace_folder_index');
            } catch (\Throwable) {
                return $this->setDeleteErrorFlashAndRedirect($category);
            }
        }

        return $this->render('workspace/category/delete.html.twig', [
            'category' => $category,
            'workspace' => $workspace,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{slug}/edit', name: 'edit')]
    public function edit(
        Request $request,
        EntityManagerInterface $entityManager,
        string $slug
    ): Response {
        [$workspace, $user] = $this->getWorkspaceAndUser();

        $category = $this->categoryHandler->getCategoryBySlug($slug, $workspace);
        if (!$category instanceof Category) {
            throw $this->createNotFoundException();
        }

        if (!$this->isGranted(CategoryVoter::EDIT, $category)) {
            return $this->setEditErrorFlashAndRedirect($category);
        }

        $edit = (new Edit())
            ->setTitle($category->getTitle())
            ->setParent($category->getParent());
        $form = $this->createForm(EditType::class, $edit);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->categoryHandler->editCategory($edit, $category, $user);
            $entityManager->flush();

            return $this->redirectToRoute(route: 'workspace_folder_index', parameters: ['slug' => $category->getSlug()]);
        }

        return $this->render('workspace/category/edit.html.twig', [
            'category' => $category,
            'workspace' => $workspace,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @throws PaginatorException
     * @throws FileHandlerException
     */
    #[Route('/{slug?}', name: 'index')]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        FilePaginationHandlerInterface $filePaginationHandler,
        ?string $slug = null
    ): Response {
        [$workspace, $user] = $this->getWorkspaceAndUser();

        $category = is_string($slug) ? $this->categoryHandler->getCategoryBySlug($slug, $workspace) : null;

        $form = null;
        if ($this->isGranted(WorkspaceVoter::CREATE_CATEGORIES, $workspace)) {
            $create = (new Create())
                ->setParent($category);
            $form = $this->createForm(CreateType::class, $create);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $this->categoryHandler->createCategory(create: $create, workspace: $workspace, user: $user);
                $entityManager->flush();

                return $this->redirectToRoute(route: 'workspace_folder_index', parameters: ['slug' => $category?->getSlug()]);
            }
        }

        $subCategories = $this->categoryHandler->getCategoryChildren(
            workspace: $workspace,
            parent: $category
        );

        $files = $filterOptions = $arguments = $filterForm = null;
        if ($category instanceof Category) {
            $arguments = RequestArgumentHelper::extractArguments(
                request: $request,
                defaultValues: new DefaultRequestValues()
            );

            [$files, $filterOptions, $filterForm] = $this->getFilesAndFilterOptions(
                filePaginationHandler: $filePaginationHandler,
                workspace: $workspace,
                arguments: $arguments,
                additionalFilters: ContextHelper::getRequestFilters(request: $request, category: $category)
            );

            if ($arguments->isPaginator()) {
                return $this->renderPaginatorContent($files, $request);
            }
        }

        return $this->render(
            'workspace/category/index.html.twig',
            array_merge($arguments instanceof SortFilterPaginateArguments ? $arguments->asViewParameters() : [], [
                'category' => $category,
                'workspace' => $workspace,
                'subCategories' => $subCategories,
                'files' => $files,
                'filterOptions' => $filterOptions,
                'form' => $form?->createView(),
                'filterForm' => $filterForm?->createView(),
            ])
        );
    }

    private function getDeleteForm(?Category $category): ?FormInterface
    {
        if (!$category instanceof Category || null === $category->getSlug()) {
            return null;
        }
        if (!$this->isGranted(CategoryVoter::DELETE, $category)) {
            return null;
        }

        return $this->createFormBuilder([], [
            'action' => $this->router->generate('workspace_folder_delete', ['slug' => $category->getSlug()]),
            'translation_domain' => 'form',
        ])
            ->add('deleteCategory', SubmitType::class, [
                'label' => 'button.deleteCategory',
                'attr' => [
                    'class' => 'button',
                ],
            ])
            ->getForm();
    }

    private function setDeleteErrorFlashAndRedirect(Category $category): RedirectResponse
    {
        $this->addFlash(FlashType::ERROR->value, $this->translator->trans('category.delete.error.notPossible'));

        return $this->redirectToRoute('workspace_folder_index', ['slug' => $category->getSlug()]);
    }

    private function setEditErrorFlashAndRedirect(Category $category): RedirectResponse
    {
        $this->addFlash(FlashType::ERROR->value, $this->translator->trans('category.edit.error.notPossible'));

        return $this->redirectToRoute('workspace_folder_index', ['slug' => $category->getSlug()]);
    }
}
