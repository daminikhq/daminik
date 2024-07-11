<?php

declare(strict_types=1);

namespace App\Dto\DatabaseLogger;

class Workspace extends MetaData
{
    public function __construct(
        protected ?string $title,
    ) {
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }
}
