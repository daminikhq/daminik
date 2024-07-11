<?php

declare(strict_types=1);

namespace App\Dto\File;

use App\Dto\AbstractDto;

class AiTag extends AbstractDto
{
    public function __construct(
        protected string $tag,
        protected float $confidence = 0
    ) {
    }

    public function getTag(): string
    {
        return $this->tag;
    }

    public function setTag(string $tag): AiTag
    {
        $this->tag = $tag;

        return $this;
    }

    public function getConfidence(): float
    {
        return $this->confidence;
    }

    public function setConfidence(float $confidence): AiTag
    {
        $this->confidence = $confidence;

        return $this;
    }
}
