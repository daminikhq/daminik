<?php

declare(strict_types=1);

namespace App\Controller\Workspace;

use App\Dto\Filter\FilterFormDto;
use App\Dto\Utility\FilterOptions;
use App\Dto\Utility\SortFilterPaginateArguments;
use App\Entity\File;
use App\Entity\User;
use App\Entity\Workspace;
use App\Enum\WorkspaceStatus;
use App\Exception\File\GetterException;
use App\Exception\FileHandlerException;
use App\Exception\Workspace\WorkspaceBlockedException;
use App\Form\FilterFormType;
use App\Service\File\FilePaginationHandlerInterface;
use App\Service\File\Filter\AbstractFileFilter;
use App\Service\File\GetterInterface;
use App\Service\Workspace\WorkspaceIdentifier;
use App\Util\Paginator;
use App\Util\Paginator\PaginatorException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class AbstractWorkspaceController extends AbstractController
{
    protected ?Workspace $workspace = null;

    /**
     * @throws WorkspaceBlockedException
     */
    public function __construct(protected WorkspaceIdentifier $workspaceIdentifier)
    {
        $workspace = $this->workspaceIdentifier->getWorkspace();
        if (!$workspace instanceof Workspace) {
            throw $this->createNotFoundException();
        }
        $workspaceStatus = WorkspaceStatus::fromWorkspace($workspace);
        if (WorkspaceStatus::BLOCKED === $workspaceStatus) {
            throw new WorkspaceBlockedException();
        }
    }

    /**
     * @return array{Workspace, File}
     *
     * @throws AccessDeniedException
     * @throws NotFoundHttpException
     *
     * @noinspection PhpUnused
     */
    protected function getWorkspaceAndFile(
        GetterInterface $fileGetter,
        ?string $filename = null,
        ?string $slug = null,
        bool $includeDeleted = false
    ): array {
        $workspace = $this->getWorkspace();
        $file = $this->getFile($workspace, $fileGetter, $filename, $slug, $includeDeleted);

        return [$workspace, $file];
    }

    /**
     * @throws NotFoundHttpException
     */
    public function getWorkspace(): Workspace
    {
        $workspace = $this->workspaceIdentifier->getWorkspace();
        if (!$workspace instanceof Workspace) {
            throw $this->createNotFoundException();
        }

        return $workspace;
    }

    /**
     * @return array{Workspace, User}
     *
     * @throws AccessDeniedException
     * @throws NotFoundHttpException
     */
    protected function getWorkspaceAndUser(): array
    {
        $workspace = $this->getWorkspace();

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return [$workspace, $user];
    }

    /**
     * @return array{Workspace, User, File}
     *
     * @throws AccessDeniedException
     * @throws NotFoundHttpException
     */
    protected function getWorkspaceUserAndFile(
        GetterInterface $fileGetter,
        ?string $filename = null,
        ?string $slug = null,
        bool $includeDeleted = false
    ): array {
        [$workspace, $user] = $this->getWorkspaceAndUser();
        $file = $this->getFile($workspace, $fileGetter, $filename, $slug, $includeDeleted);

        return [$workspace, $user, $file];
    }

    /**
     * @param array<AbstractFileFilter> $additionalFilters
     *
     * @throws PaginatorException
     * @throws FileHandlerException
     */
    protected function getFiles(
        FilePaginationHandlerInterface $filePaginationHandler,
        Workspace $workspace,
        SortFilterPaginateArguments $arguments,
        array $additionalFilters
    ): Paginator {
        return $filePaginationHandler->filterAndPaginateFiles(
            workspace: $workspace,
            sortFilterPaginateArguments: $arguments,
            additionalFilters: $additionalFilters,
        );
    }

    /**
     * @param array<AbstractFileFilter> $additionalFilters
     *
     * @return array{Paginator, FilterOptions, FormInterface}
     *
     * @throws PaginatorException
     * @throws FileHandlerException
     */
    protected function getFilesAndFilterOptions(
        FilePaginationHandlerInterface $filePaginationHandler,
        Workspace $workspace,
        SortFilterPaginateArguments $arguments,
        array $additionalFilters
    ): array {
        $files = $this->getFiles(
            filePaginationHandler: $filePaginationHandler,
            workspace: $workspace,
            arguments: $arguments,
            additionalFilters: $additionalFilters
        );

        $filterOptions = $filePaginationHandler->getFilterOptions(
            workspace: $workspace,
            sortFilterPaginateArguments: $arguments,
            additionalFilters: $additionalFilters
        );

        $fileTypes = [];
        foreach ($arguments->getMimeTypes() as $mimeTypeFilter) {
            if ($mimeTypeFilter->value) {
                $fileTypes[] = $mimeTypeFilter->label;
            }
        }
        $filterFormDto = new FilterFormDto(
            s: $arguments->getSearch(),
            sort: $arguments->getSort()->value,
            filetype: array_filter($fileTypes),
            tags: [] !== $arguments->getTags() ? implode(', ', $arguments->getTags()) : null,
            uploadedby: [] !== $arguments->getUploadedBy() ? implode(', ', $arguments->getUploadedBy()) : null,
            uploadedat: $arguments->getUploadedAt()
        );

        $filterForm = $this->createForm(FilterFormType::class, $filterFormDto, [
            'method' => 'GET',
            'filterOptions' => $filterOptions,
        ]);

        return [$files, $filterOptions, $filterForm];
    }

    /**
     * @throws AccessDeniedException
     * @throws NotFoundHttpException
     */
    protected function getFile(Workspace $workspace, GetterInterface $fileGetter, ?string $filename = null, ?string $slug = null, bool $includeDeleted = false): File
    {
        if (null === $slug && null === $filename) {
            throw $this->createNotFoundException();
        }

        try {
            if (null !== $filename) {
                $file = $fileGetter->getFile(workspace: $workspace, filename: $filename, includeDeleted: $includeDeleted);
            } elseif (null !== $slug) {
                $file = $fileGetter->getFile(workspace: $workspace, slug: $slug, includeDeleted: $includeDeleted);
            } else {
                $file = null;
            }
        } catch (GetterException) {
            throw $this->createNotFoundException();
        }
        if (!$file instanceof File || null === $file->getFilename()) {
            throw $this->createNotFoundException();
        }

        return $file;
    }

    protected function renderPaginatorContent(Paginator $files, Request $request): JsonResponse
    {
        $responseArray = [];
        if ($files->getPage() < $files->getPages()) {
            $nextPage = $files->getPage() + 1;
            $route = $request->attributes->get('_route');
            if (!is_string($route)) {
                $route = 'home_index';
            }
            $responseArray['nextPage'] = $this->generateUrl(
                route: $route,
                parameters: array_merge($request->query->all(), ['page' => $nextPage])
            );
        }

        $html = '';
        foreach ($files->getItems() as $file) {
            if ($file instanceof File) {
                $html .= $this->renderView('partials/_file-grid-item.html.twig', [
                    'file' => $file,
                ]);
            }
        }
        $responseArray['html'] = $html;

        return new JsonResponse($responseArray);
    }
}
