<?php

declare(strict_types=1);

namespace App\Util;

class StringUtil
{
    public static function isStringCastable(mixed $value): bool
    {
        if (is_array($value)) {
            return false;
        }

        if (is_object($value)) {
            return method_exists($value, '__toString');
        }

        return is_string($value) || is_numeric($value);
    }

    /**
     * @throws \Exception
     */
    public static function strictCastToString(mixed $value): string
    {
        if (self::isStringCastable($value)) {
            /* @phpstan-ignore-next-line */
            return (string) $value;
        }
        /* @noinspection ThrowRawExceptionInspection */
        throw new \Exception();
    }
}
