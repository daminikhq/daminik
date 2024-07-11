<?php

declare(strict_types=1);

namespace App\Dto\Collection;

use Symfony\Component\Validator\Constraints as Assert;

class Create
{
    #[Assert\NotBlank]
    private ?string $title = null;

    public function setTitle(?string $title): Create
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }
}
