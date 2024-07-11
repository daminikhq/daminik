<?php

namespace App\Util;

use App\Entity\AssetCollection;
use App\Entity\Category;
use App\Entity\Tag;
use App\Entity\User;
use App\Enum\FileType;
use App\Enum\Visibility;
use App\Exception\FileHandlerException;
use App\Service\File\Filter\AbstractFileFilter;
use App\Service\File\Filter\CategoryFilter;
use App\Service\File\Filter\ChoiceFilter;
use App\Service\File\Filter\CollectionFilter;
use App\Service\File\Filter\FavoriteFilter;
use App\Service\File\Filter\FileTypeFilter;
use App\Service\File\Filter\PublicFilter;
use App\Service\File\Filter\TagFilter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;

class ContextHelper
{
    /**
     * @var string[]
     */
    private static array $onlyNotDeleted = [
        'workspace_index',
        'workspace_favorites',
        'workspace_api_assets',
        'workspace_api_values',
        'workspace_api_tag_assets',
        'workspace_api_collection_assets',
        'workspace_api_category_assets',
        'workspace_folder_index',
        'workspace_collection_collection',
    ];

    /**
     * @var string[]
     */
    private static array $onlyDeleted = [
        'workspace_bin',
    ];

    /**
     * @var string[]
     */
    private static array $onlyPublic = [
        'workspace_api_assets',
        'workspace_api_values',
        'workspace_api_tag_assets',
        'workspace_api_collection_assets',
        'workspace_api_category_assets',
    ];

    /**
     * @var string[]
     */
    private static array $fileTypeAsset = [
        'workspace_index',
        'workspace_bin',
        'workspace_favorites',
        'workspace_api_assets',
        'workspace_api_values',
        'workspace_api_tag_assets',
        'workspace_api_collection_assets',
        'workspace_api_category_assets',
        'workspace_folder_index',
        'workspace_collection_collection',
    ];

    /**
     * @var string[]
     */
    private static array $tags = [
        'workspace_api_tag_assets',
    ];

    /**
     * @var string[]
     */
    private static array $collection = [
        'workspace_api_collection_assets',
        'workspace_collection_collection',
    ];

    /**
     * @var string[]
     */
    private static array $category = [
        'workspace_api_category_assets',
        'workspace_folder_index',
    ];

    /**
     * @param Tag[]|null $tags
     *
     * @return AbstractFileFilter[]
     *
     * @throws FileHandlerException
     */
    public static function getRequestFilters(
        Request $request,
        ?UserInterface $user = null,
        ?array $tags = null,
        ?AssetCollection $collection = null,
        ?Category $category = null,
    ): array {
        $route = $request->attributes->get('_route');
        if (!is_string($route)) {
            $route = 'workspace_index';
        }

        return self::getContextFilters($route, $user, $tags, $collection, $category);
    }

    /**
     * @param Tag[]|null $tags
     *
     * @return AbstractFileFilter[]
     *
     * @throws FileHandlerException
     */
    public static function getContextFilters(
        string $route,
        ?UserInterface $user = null,
        ?array $tags = null,
        ?AssetCollection $collection = null,
        ?Category $category = null,
    ): array {
        $contextFilters = [];

        if (in_array($route, self::$onlyNotDeleted, true)) {
            $contextFilters[] = new ChoiceFilter(['deletedAt', 'isNull']);
        }

        if (in_array($route, self::$onlyDeleted, true)) {
            $contextFilters[] = new ChoiceFilter(['deletedAt', 'isNotNull']);
            $contextFilters[] = new ChoiceFilter(['completelyDeleteStarted', 'isNull']);
        }

        if (in_array($route, self::$fileTypeAsset, true)) {
            $contextFilters[] = new FileTypeFilter(FileType::ASSET);
        }

        if ('workspace_favorites' === $route && $user instanceof User) {
            $contextFilters[] = new FavoriteFilter($user);
        }

        if (in_array($route, self::$onlyPublic, true)) {
            $contextFilters[] = new PublicFilter(Visibility::PUBLIC);
        }

        if (in_array($route, self::$tags, true) && null !== $tags && [] !== $tags) {
            $contextFilters[] = new TagFilter($tags);
        }

        if (in_array($route, self::$collection, true) && $collection instanceof AssetCollection) {
            $contextFilters[] = new CollectionFilter($collection);
        }

        if (in_array($route, self::$category, true) && $category instanceof Category) {
            $contextFilters[] = new CategoryFilter($category);
        }

        return $contextFilters;
    }

    public static function needsUser(string $route): bool
    {
        return 'workspace_favorites' === $route;
    }

    public static function needsCollection(string $route): bool
    {
        return in_array($route, self::$collection, true);
    }

    public static function needsCategory(string $route): bool
    {
        return in_array($route, self::$category, true);
    }
}
