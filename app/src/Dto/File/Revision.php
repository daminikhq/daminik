<?php

declare(strict_types=1);

namespace App\Dto\File;

use App\Entity\File;
use App\Entity\User;

class Revision
{
    public function __construct(
        private User $uploader,
        private File $file
    ) {
    }

    public function setUploader(User $uploader): Revision
    {
        $this->uploader = $uploader;

        return $this;
    }

    public function setFile(File $file): Revision
    {
        $this->file = $file;

        return $this;
    }

    public function getUploader(): User
    {
        return $this->uploader;
    }

    public function getFile(): File
    {
        return $this->file;
    }
}
