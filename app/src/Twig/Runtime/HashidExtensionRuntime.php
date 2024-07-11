<?php

namespace App\Twig\Runtime;

use App\Util\Hashids;
use Twig\Extension\RuntimeExtensionInterface;

readonly class HashidExtensionRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private Hashids $hashids
    ) {
    }

    public function encode(mixed $value): string
    {
        if (is_string($value)) {
            $value = (int) $value;
        }
        if (is_int($value)) {
            return $this->hashids->encode($value);
        }
        throw new \RuntimeException('Error encoding value');
    }
}
