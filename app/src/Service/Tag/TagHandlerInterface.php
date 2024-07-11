<?php

declare(strict_types=1);

namespace App\Service\Tag;

use App\Entity\File;
use App\Entity\Tag;
use App\Entity\User;
use App\Entity\Workspace;
use App\Interfaces\AutoCompleteQueriable;

interface TagHandlerInterface extends AutoCompleteQueriable
{
    public function saveTags(File $file, ?string $tagString, ?User $user = null, bool $ai = false): void;

    public function updateTags(File $file, string $tagString, ?User $user = null, bool $overWrite = true, bool $ai = false): void;

    public function addTags(File $file, string $tagString, ?User $user = null, bool $ai = false): void;

    public function getTagString(File $file): string;

    /**
     * @return string[]
     */
    public function getTagStringArray(File $file): array;

    public function getTagFromString(string $slug, Workspace $workspace): ?Tag;
}
