<?php

declare(strict_types=1);

namespace App\Enum;

enum HandleDeleteAction: string
{
    case UNDELETE = 'undelete';
    case DELETE = 'delete';
}
