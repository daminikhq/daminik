<?php

declare(strict_types=1);

namespace App\GraphQL\Resolver;

use App\GraphQL\Loader\CategoryLoader;
use App\GraphQL\Loader\FileLoader;
use App\GraphQL\Loader\TagLoader;
use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Definition\ArgumentInterface;
use Overblog\GraphQLBundle\Resolver\ResolverMap;

class Resolver extends ResolverMap
{
    public function __construct(
        private readonly FileLoader $fileLoader,
        private readonly TagLoader $tagLoader,
        private readonly CategoryLoader $categoryLoader
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    protected function map(): array
    {
        return [
            'Query' => [
                self::RESOLVE_FIELD => function ($value, ArgumentInterface $args, \ArrayObject $context, ResolveInfo $info) {
                    switch ($info->fieldName) {
                        case 'tag':
                            $slug = $args['slug'];
                            if (!is_string($slug) || '' === trim($slug)) {
                                return null;
                            }

                            return $this->tagLoader->loadTagBySlug($slug)?->toArray();
                        case 'category':
                            $slug = $args['slug'];
                            if (!is_string($slug) || '' === trim($slug)) {
                                return null;
                            }

                            return $this->categoryLoader->loadCategoryBySlug($slug)?->toArray();
                        case 'asset':
                            $slug = $args['slug'];
                            if (!is_string($slug) || '' === trim($slug)) {
                                return null;
                            }

                            return $this->fileLoader->loadFileBySlug($slug)?->toArray();
                        default:
                            break;
                    }
                    /*
                    dump($value);
                    dump($args);
                    dump($context);
                    dump($info);
                    */

                    // ToDo: mehr GraphQL-Magie lernen und hier irgendwie abfangen
                    return null;
                },
                'tags' => fn () => $this->tagLoader->loadTags()?->toArray(),
                'categories' => fn () => $this->categoryLoader->loadCategories()?->toArray(),
                'assets' => fn () => $this->fileLoader->loadFiles()?->toArray(),
            ],
        ];
    }
}
