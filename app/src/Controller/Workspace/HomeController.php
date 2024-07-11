<?php

declare(strict_types=1);

namespace App\Controller\Workspace;

use App\Dto\Utility\DefaultRequestValues;
use App\Enum\FlashType;
use App\Exception\FileHandlerException;
use App\Security\Voter\WorkspaceVoter;
use App\Service\File\DeleterInterface;
use App\Service\File\FilePaginationHandlerInterface;
use App\Service\Workspace\WorkspaceIdentifier;
use App\Util\ContextHelper;
use App\Util\Paginator\PaginatorException;
use App\Util\RequestArgumentHelper;
use App\Util\SearchRouteHelper;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted(WorkspaceVoter::VIEW)]
#[Route('/', name: 'workspace_', defaults: ['domain' => '%app.domain%', 'tld' => '%app.tld%'], host: '{subdomain}.{domain}.{tld}')]
class HomeController extends AbstractWorkspaceController
{
    public const DEFAULT_VIEW = 'grid';

    public function __construct(
        private readonly FilePaginationHandlerInterface $filePaginationHandler,
        WorkspaceIdentifier $workspaceIdentifier
    ) {
        parent::__construct($workspaceIdentifier);
    }

    /**
     * @throws FileHandlerException
     * @throws PaginatorException
     */
    #[Route('', name: 'index')]
    public function index(
        Request $request
    ): Response {
        $workspace = $this->getWorkspace();
        $arguments = RequestArgumentHelper::extractArguments(
            request: $request,
            defaultValues: new DefaultRequestValues(
                view: self::DEFAULT_VIEW
            )
        );

        [$files, $filterOptions, $filterForm] = $this->getFilesAndFilterOptions(
            filePaginationHandler: $this->filePaginationHandler,
            workspace: $workspace,
            arguments: $arguments,
            additionalFilters: ContextHelper::getRequestFilters($request)
        );

        if ($arguments->isPaginator()) {
            return $this->renderPaginatorContent($files, $request);
        }

        return $this->render(
            'workspace/index.html.twig',
            array_merge($arguments->asViewParameters(), [
                'workspace' => $workspace,
                'files' => $files,
                'filterOptions' => $filterOptions,
                'filterForm' => $filterForm->createView(),
            ])
        );
    }

    /**
     * @throws FileHandlerException
     * @throws PaginatorException
     */
    #[Route('bin', name: 'bin')]
    public function bin(
        DeleterInterface $fileDeleter,
        Request $request,
        TranslatorInterface $translator
    ): Response {
        $workspace = $this->getWorkspace();

        $form = $this->createFormBuilder([])
            ->add('emptyBin', SubmitType::class, [
                'label' => 'form.emptyBin',
                'attr' => [
                    'class' => 'button button--function',
                ],
            ])
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $fileDeleter->emptyBin();
            $this->addFlash(FlashType::SUCCESS->value, $translator->trans('file.bin.empty.success'));

            return $this->redirectToRoute('workspace_bin');
        }

        $arguments = RequestArgumentHelper::extractArguments(
            request: $request,
            defaultValues: new DefaultRequestValues(
                view: self::DEFAULT_VIEW
            )
        );

        [$files, $filterOptions, $filterForm] = $this->getFilesAndFilterOptions(
            filePaginationHandler: $this->filePaginationHandler,
            workspace: $workspace,
            arguments: $arguments,
            additionalFilters: ContextHelper::getRequestFilters($request)
        );

        if ($arguments->isPaginator()) {
            return $this->renderPaginatorContent($files, $request);
        }

        return $this->render(
            'workspace/bin.html.twig',
            array_merge($arguments->asViewParameters(), [
                'files' => $files,
                'emptyBinForm' => $form,
                'workspace' => $workspace,
                'filterOptions' => $filterOptions,
                'filterForm' => $filterForm->createView(),
            ]),
        );
    }

    /**
     * @throws FileHandlerException
     * @throws PaginatorException
     *
     * @noinspection PhpUnused
     */
    #[Route('favorites', name: 'favorites')]
    public function favorites(
        Request $request
    ): Response {
        [$workspace, $user] = $this->getWorkspaceAndUser();

        $arguments = RequestArgumentHelper::extractArguments(
            request: $request,
            defaultValues: new DefaultRequestValues(
                view: self::DEFAULT_VIEW
            )
        );

        [$files, $filterOptions, $filterForm] = $this->getFilesAndFilterOptions(
            filePaginationHandler: $this->filePaginationHandler,
            workspace: $workspace,
            arguments: $arguments,
            additionalFilters: ContextHelper::getRequestFilters($request, $user)
        );

        if ($arguments->isPaginator()) {
            return $this->renderPaginatorContent($files, $request);
        }

        return $this->render(
            'workspace/favorites/index.html.twig',
            array_merge($arguments->asViewParameters(), [
                'files' => $files,
                'workspace' => $workspace,
                'filterOptions' => $filterOptions,
                'filterForm' => $filterForm->createView(),
            ])
        );
    }

    #[Route('search', name: 'search')]
    public function search(
        Request $request
    ): Response {
        $s = $request->get('s');

        $parameterString = $request->get('parameters');
        $parameters = [];
        if (is_string($parameterString)) {
            parse_str($parameterString, $parameters);
        }
        if (is_string($s)) {
            $parameters['s'] = $s;
        }

        $route = $request->get('route');
        $route = SearchRouteHelper::getValidSearchRoute($route);
        $slug = $request->get('slug');
        if (is_string($slug)) {
            $parameters['slug'] = $slug;
        }

        return $this->redirectToRoute($route, $parameters);
    }
}
