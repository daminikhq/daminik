<?php

declare(strict_types=1);

namespace App\Service\File;

use App\Dto\File\MultiAction;
use App\Dto\Response\FormResponse;

interface MultiActionHandlerInterface
{
    public function handleMultiAction(MultiAction $multiAction): FormResponse;
}
