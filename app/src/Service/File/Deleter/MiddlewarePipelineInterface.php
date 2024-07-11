<?php

declare(strict_types=1);

namespace App\Service\File\Deleter;

interface MiddlewarePipelineInterface
{
    public function pipe(MiddlewarePayloadInterface $payload): MiddlewarePayloadInterface;

    /**
     * @return MiddlewareInterface[]
     */
    public function getMiddleware(): array;
}
