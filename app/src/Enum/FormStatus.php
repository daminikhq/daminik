<?php

declare(strict_types=1);

namespace App\Enum;

enum FormStatus: string
{
    case OK = 'ok';
    case ERROR = 'error';
}
