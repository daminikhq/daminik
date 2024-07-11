<?php

declare(strict_types=1);

namespace App\Enum;

enum FilesystemType: string
{
    case LOCAL = 'local';
    case S3 = 's3';
}
