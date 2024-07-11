<?php

declare(strict_types=1);

namespace App\Service\File\Deleter;

use Psr\Log\LoggerInterface;

interface MiddlewarePayloadInterface
{
    public function getLogger(): LoggerInterface;
}
