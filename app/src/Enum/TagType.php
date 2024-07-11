<?php

declare(strict_types=1);

namespace App\Enum;

enum TagType: string
{
    case HUMAN = 'human';
    case AI = 'ai';
}
