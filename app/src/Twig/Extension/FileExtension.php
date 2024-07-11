<?php

namespace App\Twig\Extension;

use App\Twig\Runtime\FileExtensionRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class FileExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('publicUrl', [FileExtensionRuntime::class, 'publicUrl']),
            new TwigFilter('fileSize', [FileExtensionRuntime::class, 'fileSize']),
            new TwigFilter('fileTypeBadge', [FileExtensionRuntime::class, 'fileTypeBadge']),
            new TwigFilter('fileIsFavorite', [FileExtensionRuntime::class, 'fileIsFavorite']),
            new TwigFilter('thumbnailUrl', [FileExtensionRuntime::class, 'thumbnailUrl']),
            new TwigFilter('publicThumbnailUrl', [FileExtensionRuntime::class, 'publicThumbnailUrl']),
            new TwigFilter('nextFileUrl', [FileExtensionRuntime::class, 'nextFileUrl']),
            new TwigFilter('previousFileUrl', [FileExtensionRuntime::class, 'previousFileUrl']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('accentColor', [FileExtensionRuntime::class, 'accentColor']),
            new TwigFunction('uploadContext', [FileExtensionRuntime::class, 'uploadContext']),
            new TwigFunction('uploadHomeUrl', [FileExtensionRuntime::class, 'uploadHomeUrl']),
        ];
    }
}
