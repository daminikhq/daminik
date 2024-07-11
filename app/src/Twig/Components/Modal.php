<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Modal
{
    public string $title = '';
    public string $id = 'modal';
    public string $size = '';
    public string $stimulusTarget = '';

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getSize(): string
    {
        return $this->size;
    }

    public function getStimulusTarget(): string
    {
        return $this->stimulusTarget;
    }
}
