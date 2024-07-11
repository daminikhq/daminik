<?php

declare(strict_types=1);

namespace App\Enum;

enum MimeType: string
{
    case JPG = 'image/jpeg';
    case PNG = 'image/png';
    case GIF = 'image/gif';
    case SVG = 'image/svg+xml';
    case HEIC = 'image/heic';
    case WEBP = 'image/webp';

    public static function tryFromName(string $name): ?MimeType
    {
        $reflection = new \ReflectionEnum(self::class);
        $name = mb_strtoupper($name);

        try {
            $unitEnum = $reflection->hasCase($name)
                ? $reflection->getCase($name)->getValue()
                : null;
        } catch (\ReflectionException) {
            return null;
        }

        return $unitEnum instanceof self ? $unitEnum : null;
    }

    /**
     * @return static[]
     */
    public static function validCases(): array
    {
        return [
            self::JPG,
            self::PNG,
            self::GIF,
            self::SVG,
            self::WEBP,
        ];
    }

    /**
     * @return string[]
     */
    public function getExtensions(): array
    {
        return match ($this) {
            self::JPG => ['.jpg', '.jpeg'],
            self::PNG => ['.png'],
            self::GIF => ['.gif'],
            self::SVG => ['.svg'],
            self::WEBP => ['.webp'],
            self::HEIC => ['.heic'],
        };
    }

    /**
     * @return string[]
     */
    public static function validUploadExtensions(): array
    {
        $extensions = [];
        foreach (self::validCases() as $case) {
            $extensions = array_merge($extensions, $case->getExtensions());
        }

        return $extensions;
    }
}
