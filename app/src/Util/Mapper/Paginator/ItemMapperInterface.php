<?php

declare(strict_types=1);

namespace App\Util\Mapper\Paginator;

interface ItemMapperInterface
{
    /**
     * @throws ItemMapperException
     */
    public function map(mixed $origin): mixed;
}
