<?php

declare(strict_types=1);

namespace App\Service\File\Helper;

class FilePathHelper
{
    public static function getFilePath(?string $filename, string $filenameSlug, ?int $revisionCounter = null, ?bool $short = false): string
    {
        if ($short) {
            return implode('/', array_filter([substr($filenameSlug, 0, 4), $revisionCounter, $filename]));
        }

        return implode('/', array_filter([substr($filenameSlug, 0, 4), $filenameSlug, $revisionCounter, $filename]));
    }

    public static function getFilePathWithSize(string $filenameSlug, ?int $width = null, ?int $height = null, ?int $revisionCounter = null): string
    {
        return sprintf('%s/%s-%sx%s.png', implode('/', array_filter([self::getFilePath(null, $filenameSlug), $revisionCounter])), $filenameSlug, $width, $height);
    }
}
