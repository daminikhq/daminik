<?php

declare(strict_types=1);

namespace App\Enum;

enum UserSource: string
{
    case INVITATION = 'invitation';
    case ROBOT = 'robot';
}
