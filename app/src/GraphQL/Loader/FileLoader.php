<?php

declare(strict_types=1);

namespace App\GraphQL\Loader;

use App\Dto\Api\Asset;
use App\Dto\Api\AssetCollection;
use App\Entity\File;
use App\Entity\Workspace;
use App\Exception\File\GetterException;
use App\Service\File\GetterInterface;
use App\Service\File\Helper\UrlHelperInterface;
use App\Service\Workspace\WorkspaceIdentifier;
use App\Util\Mapper\Api\FileMapper;
use App\Util\Mapper\MapperException;

readonly class FileLoader
{
    public function __construct(
        private WorkspaceIdentifier $workspaceIdentifier,
        private GetterInterface $getter,
        private UrlHelperInterface $urlHelper
    ) {
    }

    /**
     * @throws MapperException
     */
    public function loadFileBySlug(string $slug): ?Asset
    {
        $workspace = $this->workspaceIdentifier->getWorkspace();
        if (!$workspace instanceof Workspace) {
            return null;
        }
        try {
            $file = $this->getter->getFile(workspace: $workspace, slug: $slug);
        } catch (GetterException) {
            return null;
        }
        if (!$file instanceof File) {
            return null;
        }

        return FileMapper::mapEntityToDto(file: $file, urlHelper: $this->urlHelper);
    }

    /**
     * @throws MapperException
     */
    public function loadFiles(): ?AssetCollection
    {
        $workspace = $this->workspaceIdentifier->getWorkspace();
        if (!$workspace instanceof Workspace) {
            return null;
        }
        $files = $this->getter->getFiles($workspace);
        if (count($files) < 1) {
            return null;
        }
        $collectionDto = new AssetCollection();
        foreach ($files as $file) {
            $collectionDto->addAsset(FileMapper::mapEntityToDto(file: $file, urlHelper: $this->urlHelper));
        }

        return $collectionDto;
    }
}
