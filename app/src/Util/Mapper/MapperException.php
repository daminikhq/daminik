<?php

declare(strict_types=1);

namespace App\Util\Mapper;

class MapperException extends \Exception
{
    public function __construct(
        string $fromClass,
        string $toClass,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null)
    {
        $message .= sprintf('(from: %s to %s)', $fromClass, $toClass);

        parent::__construct($message, $code, $previous);
    }
}
