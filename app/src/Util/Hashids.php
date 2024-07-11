<?php

declare(strict_types=1);

namespace App\Util;

readonly class Hashids
{
    public function __construct(
        private \Hashids\Hashids $hashids
    ) {
    }

    public function decode(string $hashid): ?int
    {
        $keys = $this->hashids->decode($hashid);
        if (count($keys) < 1) {
            return null;
        }

        return $keys[0];
    }

    public function encode(int $value): string
    {
        return $this->hashids->encode($value);
    }
}
