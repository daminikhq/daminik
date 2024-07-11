<?php

declare(strict_types=1);

namespace App\Dto\Api;

use App\Dto\AbstractDto;

class TagCollection extends AbstractDto
{
    /** @var Tag[] */
    protected array $tags = [];

    public function addTag(Tag $tag): self
    {
        if (!in_array($tag, $this->tags, true)) {
            $this->tags[] = $tag;
        }

        return $this;
    }

    /**
     * @return Tag[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @param Tag[] $tags
     */
    public function setTags(array $tags): TagCollection
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * @return array<int, array<int|string, mixed>>
     */
    public function toArray(bool $removeEmpty = false): array
    {
        $return = [];
        foreach ($this->tags as $tag) {
            $return[] = $tag->toArray(removeEmpty: $removeEmpty);
        }

        if ($removeEmpty) {
            $return = array_filter($return);
        }

        return $return;
    }
}
