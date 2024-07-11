<?php

declare(strict_types=1);

namespace App\Dto\User;

use App\Entity\File;

readonly class Avatar
{
    public function __construct(
        private ?string $url = null,
        private ?File $file = null,
        private ?string $gravatar = null,
    ) {
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function getGravatar(): ?string
    {
        return $this->gravatar;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }
}
