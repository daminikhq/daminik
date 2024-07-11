<?php

declare(strict_types=1);

namespace App\Service\File\Helper;

use App\Entity\File;
use App\Entity\Revision;

class FileHelper
{
    public static function getRevision(File $file, ?int $counter): ?Revision
    {
        if (null === $counter) {
            return $file->getActiveRevision();
        }

        foreach ($file->getRevisions() as $revision) {
            if ($revision->getCounter() === $counter) {
                return $revision;
            }
        }

        return null;
    }
}
