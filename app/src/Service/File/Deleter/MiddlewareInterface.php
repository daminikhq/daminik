<?php

declare(strict_types=1);

namespace App\Service\File\Deleter;

interface MiddlewareInterface
{
    public function pipe(MiddlewarePayloadInterface $payload): MiddlewarePayloadInterface;
}
