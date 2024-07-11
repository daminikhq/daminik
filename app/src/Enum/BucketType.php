<?php

namespace App\Enum;

enum BucketType: string
{
    case SINGLE = 'single';
    case YEAR = 'year';
    case WORKSPACE = 'workspace';

    public static function tryOrDefault(?string $value = null): self
    {
        if (null === $value) {
            return self::SINGLE;
        }

        return self::tryFrom($value) ?? self::SINGLE;
    }
}
