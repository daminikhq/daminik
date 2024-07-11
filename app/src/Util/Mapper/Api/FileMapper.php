<?php

declare(strict_types=1);

namespace App\Util\Mapper\Api;

use App\Dto\Api\Asset;
use App\Entity\File;
use App\Service\File\Helper\UrlHelperInterface;
use App\Util\Mapper\MapperException;

class FileMapper
{
    /**
     * @throws MapperException
     */
    public static function mapEntityToDto(
        File $file,
        UrlHelperInterface $urlHelper,
        bool $includePrivateUrl = true
    ): Asset {
        $tags = [];
        foreach ($file->getFileTags() as $fileTag) {
            if (null !== $fileTag->getTag()) {
                $tags[] = TagMapper::mapEntityToDto($fileTag->getTag());
            }
        }

        $collections = [];
        foreach ($file->getFileAssetCollections() as $fileAssetCollection) {
            if (null !== $fileAssetCollection->getAssetCollection()) {
                $collections[] = CollectionMapper::mapEntityToDto($fileAssetCollection->getAssetCollection());
            }
        }

        $category = null;
        foreach ($file->getFileCategories() as $fileCategory) {
            if (null !== $fileCategory->getCategory()) {
                $category = CategoryMapper::mapEntityToDto($fileCategory->getCategory());
                break;
            }
        }
        if (
            null === $file->getPublicFilenameSlug()
            || null === $file->getFilename()
        ) {
            throw new MapperException(fromClass: File::class, toClass: Asset::class, message: 'File has no slug or name');
        }

        $asset = (new Asset(
            slug: $file->getPublicFilenameSlug(),
            filename: $file->getFilename(),
            public: (bool) $file->isPublic(),
            publicUrl: true === $file->isPublic() ? $urlHelper->getPublicUrl($file) : null
        ))
            ->setTitle($file->getTitle())
            ->setDescription($file->getDescription())
            ->setMime($file->getMime())
            ->setWidth($file->getWidth())
            ->setHeight($file->getHeight())
            ->setTags($tags)
            ->setCategory($category)
            ->setCollections($collections);
        if ($includePrivateUrl) {
            $asset->setUrl($urlHelper->getPrivateUrl($file));
        }

        return $asset;
    }
}
