<?php

namespace App\Message;

final readonly class RecheckAssetMetadataMessage
{
    public function __construct(
        private bool $force = false
    ) {
    }

    public function isForce(): bool
    {
        return $this->force;
    }
}
