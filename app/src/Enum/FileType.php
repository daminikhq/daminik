<?php

declare(strict_types=1);

namespace App\Enum;

enum FileType: string
{
    case AVATAR = 'avatar';
    case ASSET = 'asset';
    case ICON = 'icon';
}
