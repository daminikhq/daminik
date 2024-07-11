<?php

namespace App\Twig\Runtime;

use App\Entity\File;
use App\Enum\MimeType;
use App\Service\File\FilePaginationHandlerInterface;
use App\Service\File\Helper\UrlHelperInterface;
use App\Service\File\UserMetaDataHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Twig\Extension\RuntimeExtensionInterface;

readonly class FileExtensionRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private UserMetaDataHandler $userMetaDataHandler,
        private UrlHelperInterface $urlHelper,
        private RequestStack $requestStack,
        private FilePaginationHandlerInterface $filePaginationHandler,
        private RouterInterface $router,
    ) {
    }

    public function publicUrl(File $file): string
    {
        return $this->urlHelper->getPublicUrl($file) ?? '';
    }

    public function publicThumbnailUrl(File $file): string
    {
        return $this->urlHelper->getPublicThumbnailUrl($file) ?? '';
    }

    public function fileIsFavorite(File $file): bool
    {
        return $this->userMetaDataHandler->isFavorite($file);
    }

    public function fileSize(?int $filesize): ?string
    {
        if (null === $filesize) {
            return null;
        }
        if ($filesize > 1048576) {
            return sprintf('%s MB', round($filesize / 1000000, 1));
        }

        return sprintf('%s kB', round($filesize / 1000));
    }

    public function fileTypeBadge(File $file): string
    {
        $mimeType = MimeType::tryFrom((string) $file->getMime());
        if (!$mimeType instanceof MimeType) {
            return (string) $file->getMime();
        }

        return $mimeType->name;
    }

    public function thumbnailUrl(File $file): string
    {
        $thumbnailUrl = $this->urlHelper->getThumbnailUrl($file);

        return $thumbnailUrl ?? '';
    }

    public function accentColor(File $file): string
    {
        $accentColor = $file->getActiveRevision()?->getAccentColor();
        if (null !== $accentColor) {
            return $accentColor;
        }

        /* @noinspection RandomApiMigrationInspection */
        return '#'.str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
    }

    public function nextFileUrl(File $file): ?string
    {
        $request = $this->requestStack->getMainRequest();

        return $this->filePaginationHandler->getNextFileUrl($file, $request);
    }

    public function previousFileUrl(File $file): ?string
    {
        $request = $this->requestStack->getMainRequest();

        return $this->filePaginationHandler->getPreviousFileUrl($file, $request);
    }

    public function uploadHomeUrl(): string
    {
        $request = $this->requestStack->getMainRequest();
        if (!$request instanceof Request) {
            return $this->router->generate('workspace_index');
        }
        $route = $request->attributes->get('_route');
        switch ($route) {
            case 'workspace_folder_index':
                $matched = $this->router->match($request->getRequestUri());
                if (array_key_exists('slug', $matched) && ('' !== trim((string) $matched['slug']))) {
                    return $request->getRequestUri();
                }

                return $this->router->generate('workspace_index');
            case 'workspace_collection_collection':
                return $request->getRequestUri();
            default:
                return $this->router->generate('workspace_index');
        }
    }

    public function uploadContext(): string
    {
        $request = $this->requestStack->getMainRequest();
        if (!$request instanceof Request) {
            return 'home';
        }
        $route = $request->attributes->get('_route');
        switch ($route) {
            case 'workspace_folder_index':
                $matched = $this->router->match($request->getRequestUri());
                if (array_key_exists('slug', $matched) && ('' !== trim((string) $matched['slug']))) {
                    return 'category-'.$matched['slug'];
                }
                break;
            case 'workspace_collection_collection':
                $matched = $this->router->match($request->getRequestUri());
                if (array_key_exists('slug', $matched) && ('' !== trim((string) $matched['slug']))) {
                    return 'collection-'.$matched['slug'];
                }
                break;
        }

        return 'home';
    }
}
