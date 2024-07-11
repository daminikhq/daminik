<?php

declare(strict_types=1);

namespace App\Controller\Workspace;

use App\Dto\Api\Asset;
use App\Dto\Api\Rest\AssetsResponse;
use App\Dto\Api\Rest\CategoryResponse;
use App\Dto\Api\Rest\RequestValuesResponse;
use App\Dto\Utility\DefaultRequestValues;
use App\Entity\AssetCollection;
use App\Entity\Category;
use App\Entity\Tag;
use App\Exception\FileHandlerException;
use App\Security\Voter\WorkspaceVoter;
use App\Service\Category\CategoryHandlerInterface;
use App\Service\Collection\CollectionHandlerInterface;
use App\Service\File\FilePaginationHandler;
use App\Service\File\GetterInterface;
use App\Service\File\Helper\UrlHelperInterface;
use App\Service\Tag\TagHandlerInterface;
use App\Service\User\UserHandlerInterface;
use App\Util\ContextHelper;
use App\Util\Mapper\Api\AssetResponseMapper;
use App\Util\Mapper\Api\CategoryResponseMapper;
use App\Util\Mapper\Api\FileMapper;
use App\Util\Mapper\Api\RequestValuesMapper;
use App\Util\Mapper\MapperException;
use App\Util\Paginator\PaginatorException;
use App\Util\RequestArgumentHelper;
use Nelmio\ApiDocBundle\Annotation as API;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(WorkspaceVoter::VIEW)]
#[Security(name: 'Bearer')]
#[Route('/api', name: 'workspace_api_', defaults: ['domain' => '%app.domain%', 'tld' => '%app.tld%'], host: '{subdomain}.{domain}.{tld}', stateless: false)]
class ApiController extends AbstractWorkspaceController
{
    /**
     * @throws PaginatorException
     * @throws FileHandlerException
     * @throws MapperException
     */
    #[OA\Response(
        response: 200,
        description: 'The main endpoint to query assets',
        content: new OA\JsonContent(
            ref: new API\Model(type: AssetsResponse::class)
        )
    )]
    #[OA\Parameter(
        name: 'page',
        in: 'query',
        required: false,
        allowEmptyValue: true,
    )]
    #[OA\Parameter(
        name: 'uploadedby',
        description: 'Uploader username, possible values are returned by the `/api/values` endpoint',
        in: 'query',
        required: false,
        allowEmptyValue: true
    )]
    #[OA\Parameter(
        name: 'uploadedat',
        description: 'Upload month, possible values are returned by the `/api/values` endpoint',
        in: 'query',
        required: false,
        allowEmptyValue: true,
    )]
    #[OA\Parameter(
        name: 's',
        description: 'Full text search',
        in: 'query',
        required: false,
        allowEmptyValue: true
    )]
    #[OA\Tag(name: 'Assets')]
    #[Route('/assets', name: 'assets', methods: ['GET'])]
    public function assets(
        Request $request,
        FilePaginationHandler $filePaginationHandler,
        UrlHelperInterface $urlHelper
    ): JsonResponse {
        $workspace = $this->getWorkspace();
        $arguments = RequestArgumentHelper::extractArguments(
            request: $request,
            defaultValues: new DefaultRequestValues()
        );

        $files = $this->getFiles(
            filePaginationHandler: $filePaginationHandler,
            workspace: $workspace,
            arguments: $arguments,
            additionalFilters: ContextHelper::getRequestFilters($request)
        );

        return $this->json(AssetResponseMapper::mapPaginatorToAssetResponse(files: $files, urlHelper: $urlHelper, includePrivateUrls: false));
    }

    /**
     * @throws FileHandlerException
     */
    #[OA\Response(
        response: 200,
        description: 'The possible values for month and uploader queries',
        content: new OA\JsonContent(
            ref: new API\Model(type: RequestValuesResponse::class)
        )
    )]
    #[OA\Tag(name: 'Values')]
    #[Route('/values', name: 'values', methods: ['GET'])]
    public function values(
        Request $request,
        FilePaginationHandler $filePaginationHandler,
        UserHandlerInterface $userHandler
    ): JsonResponse {
        $workspace = $this->getWorkspace();
        $arguments = RequestArgumentHelper::extractArguments(
            request: $request,
            defaultValues: new DefaultRequestValues()
        );

        $filterOptions = $filePaginationHandler->getFilterOptions(
            workspace: $workspace,
            sortFilterPaginateArguments: $arguments,
            additionalFilters: ContextHelper::getRequestFilters($request)
        );

        $uploaders = $userHandler->getForAutocomplete(workspace: $workspace, limit: 0);

        return $this->json(RequestValuesMapper::mapOptionsAndUploadersToDto($filterOptions, $uploaders));
    }

    /**
     * @throws MapperException
     */
    #[OA\Response(
        response: 200,
        description: 'The main endpoint to query assets',
        content: new OA\JsonContent(
            ref: new API\Model(type: Asset::class)
        )
    )]
    #[OA\Tag(name: 'Assets')]
    #[Route('/asset/{slug}', name: 'asset', methods: ['GET'])]
    public function asset(
        string $slug,
        GetterInterface $fileGetter,
        UrlHelperInterface $urlHelper
    ): JsonResponse {
        $workspace = $this->getWorkspace();
        $file = $this->getFile(workspace: $workspace, fileGetter: $fileGetter, slug: $slug);

        return $this->json(FileMapper::mapEntityToDto(file: $file, urlHelper: $urlHelper, includePrivateUrl: false));
    }

    /**
     * @throws MapperException
     * @throws FileHandlerException
     * @throws PaginatorException
     */
    #[OA\Response(
        response: 200,
        description: 'The endpoint to query assets for a tag',
        content: new OA\JsonContent(
            ref: new API\Model(type: AssetsResponse::class)
        )
    )]
    #[OA\Parameter(
        name: 'page',
        in: 'query',
        required: false,
        allowEmptyValue: true,
    )]
    #[OA\Tag(name: 'Tags')]
    #[OA\Tag(name: 'Assets')]
    #[Route('/tag/{slug}/assets', name: 'tag_assets', methods: ['GET'])]
    public function tagAssets(
        string $slug,
        Request $request,
        TagHandlerInterface $tagHandler,
        UrlHelperInterface $urlHelper,
        FilePaginationHandler $filePaginationHandler,
    ): JsonResponse {
        $workspace = $this->getWorkspace();
        $tag = $tagHandler->getTagFromString($slug, $workspace);
        if (!$tag instanceof Tag) {
            throw $this->createNotFoundException();
        }
        $arguments = RequestArgumentHelper::extractArguments(
            request: $request,
            defaultValues: new DefaultRequestValues(),
        );

        $files = $this->getFiles(
            filePaginationHandler: $filePaginationHandler,
            workspace: $workspace,
            arguments: $arguments,
            additionalFilters: ContextHelper::getRequestFilters(request: $request, tags: [$tag]));

        return $this->json(AssetResponseMapper::mapPaginatorToAssetResponse(files: $files, urlHelper: $urlHelper, includePrivateUrls: false));
    }

    /**
     * @throws MapperException
     * @throws FileHandlerException
     * @throws PaginatorException
     */
    #[OA\Response(
        response: 200,
        description: 'The endpoint to query assets for a collection',
        content: new OA\JsonContent(
            ref: new API\Model(type: AssetsResponse::class)
        )
    )]
    #[OA\Parameter(
        name: 'page',
        in: 'query',
        required: false,
        allowEmptyValue: true,
    )]
    #[OA\Tag(name: 'Assets')]
    #[OA\Tag(name: 'Collections')]
    #[Route('/collection/{slug}/assets', name: 'collection_assets', methods: ['GET'])]
    public function collectionAssets(
        string $slug,
        Request $request,
        CollectionHandlerInterface $collectionHandler,
        UrlHelperInterface $urlHelper,
        FilePaginationHandler $filePaginationHandler,
    ): JsonResponse {
        $workspace = $this->getWorkspace();
        $collection = $collectionHandler->getCollectionBySlug($slug, $workspace);
        if (!$collection instanceof AssetCollection) {
            throw $this->createNotFoundException();
        }
        $arguments = RequestArgumentHelper::extractArguments(
            request: $request,
            defaultValues: new DefaultRequestValues(),
        );

        $files = $this->getFiles(
            filePaginationHandler: $filePaginationHandler,
            workspace: $workspace,
            arguments: $arguments,
            additionalFilters: ContextHelper::getRequestFilters(request: $request, collection: $collection)
        );

        return $this->json(AssetResponseMapper::mapPaginatorToAssetResponse(files: $files, urlHelper: $urlHelper, includePrivateUrls: false));
    }

    /**
     * @throws MapperException
     * @throws FileHandlerException
     * @throws PaginatorException
     */
    #[OA\Response(
        response: 200,
        description: 'The endpoint to query assets for a category',
        content: new OA\JsonContent(
            ref: new API\Model(type: CategoryResponse::class)
        )
    )]
    #[OA\Parameter(
        name: 'page',
        in: 'query',
        required: false,
        allowEmptyValue: true,
    )]
    #[OA\Tag(name: 'Assets')]
    #[OA\Tag(name: 'Categories')]
    #[Route('/category/{slug}/assets', name: 'category_assets', methods: ['GET'])]
    public function categoryAssets(
        string $slug,
        Request $request,
        CategoryHandlerInterface $categoryHandler,
        UrlHelperInterface $urlHelper,
        FilePaginationHandler $filePaginationHandler,
    ): JsonResponse {
        $workspace = $this->getWorkspace();
        $category = $categoryHandler->getCategoryBySlug($slug, $workspace);
        if (!$category instanceof Category) {
            throw $this->createNotFoundException();
        }
        $arguments = RequestArgumentHelper::extractArguments(
            request: $request,
            defaultValues: new DefaultRequestValues(),
        );

        $files = $this->getFiles(
            filePaginationHandler: $filePaginationHandler,
            workspace: $workspace,
            arguments: $arguments,
            additionalFilters: ContextHelper::getRequestFilters(request: $request, category: $category)
        );

        return $this->json(CategoryResponseMapper::mapPaginatorToCategoryResponse(files: $files, category: $category, urlHelper: $urlHelper, includePrivateUrls: false));
    }
}
