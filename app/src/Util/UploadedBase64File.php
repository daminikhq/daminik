<?php

declare(strict_types=1);

namespace App\Util;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadedBase64File extends UploadedFile
{
    public function __construct(string $base64String, string $originalName)
    {
        $filePath = tempnam(sys_get_temp_dir(), 'UploadedFile');
        $data = base64_decode($base64String);
        if (false === $filePath) {
            throw new \RuntimeException('Error saving file');
        }
        file_put_contents($filePath, $data);
        $error = null;
        $mimeType = null;
        parent::__construct(path: $filePath, originalName: $originalName, mimeType: $mimeType, error: $error, test: true);
    }
}
