<?php

declare(strict_types=1);

namespace App\Interfaces;

interface AutoCompleteItem
{
    public function getValue(): string;

    public function getText(): string;
}
