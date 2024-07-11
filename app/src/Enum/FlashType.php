<?php

declare(strict_types=1);

namespace App\Enum;

enum FlashType: string
{
    case ERROR = 'error';
    case SUCCESS = 'success';
}
