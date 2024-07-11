<?php

declare(strict_types=1);

namespace App\Service\Ai;

use App\Entity\File;
use App\Entity\User;

interface AiTaggerInterface
{
    /**
     * @throws AiException
     */
    public function tag(File $file, User $user): File;
}
