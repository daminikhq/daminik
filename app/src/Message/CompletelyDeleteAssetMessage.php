<?php

namespace App\Message;

final readonly class CompletelyDeleteAssetMessage
{
    public function __construct(
        private int $id
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
