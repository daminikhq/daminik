<?php

declare(strict_types=1);

namespace App\Enum;

enum Visibility
{
    case ALL;
    case PUBLIC;
    case PRIVATE;
}
