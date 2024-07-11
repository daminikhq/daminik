<?php

declare(strict_types=1);

namespace App\Enum;

enum ViewParam: string
{
    case VIEW_GRID = 'grid';
    case VIEW_LIST = 'list';
}
