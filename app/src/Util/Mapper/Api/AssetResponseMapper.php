<?php

declare(strict_types=1);

namespace App\Util\Mapper\Api;

use App\Dto\Api\Rest\AssetsResponse;
use App\Entity\File;
use App\Service\File\Helper\UrlHelperInterface;
use App\Util\Mapper\MapperException;
use App\Util\Paginator;

class AssetResponseMapper
{
    /**
     * @throws MapperException
     */
    public static function mapPaginatorToAssetResponse(
        Paginator $files, UrlHelperInterface $urlHelper,
        bool $includePrivateUrls = true
    ): AssetsResponse {
        $response = new AssetsResponse(
            page: $files->getPage(),
            pages: $files->getPages(),
            total: $files->getTotal()
        );

        /** @var File $file */
        foreach ($files->getItems() as $file) {
            $response->addAsset(FileMapper::mapEntityToDto(file: $file, urlHelper: $urlHelper, includePrivateUrl: $includePrivateUrls));
        }

        return $response;
    }
}
