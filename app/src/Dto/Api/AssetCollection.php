<?php

declare(strict_types=1);

namespace App\Dto\Api;

use App\Dto\AbstractDto;

/**
 * @implements \ArrayAccess<int, Asset>
 */
class AssetCollection extends AbstractDto implements \ArrayAccess
{
    /** @var Asset[] */
    protected array $assets = [];

    public function addAsset(Asset $asset): self
    {
        if (!in_array($asset, $this->assets, true)) {
            $this->assets[] = $asset;
        }

        return $this;
    }

    /**
     * @return Asset[]
     */
    public function getAssets(): array
    {
        return $this->assets;
    }

    /**
     * @param Asset[] $assets
     */
    public function setAssets(array $assets): AssetCollection
    {
        $this->assets = $assets;

        return $this;
    }

    /**
     * @return array<int, array<int|string, mixed>>
     */
    public function toArray(bool $removeEmpty = false): array
    {
        $return = [];
        foreach ($this->assets as $asset) {
            $return[] = $asset->toArray(removeEmpty: $removeEmpty);
        }
        if ($removeEmpty) {
            $return = array_filter($return);
        }

        return $return;
    }

    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->assets);
    }

    public function offsetGet(mixed $offset): Asset
    {
        return $this->assets[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->assets[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->assets[$offset]);
    }
}
