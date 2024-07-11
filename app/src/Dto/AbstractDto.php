<?php

declare(strict_types=1);

namespace App\Dto;

abstract class AbstractDto implements \JsonSerializable
{
    /**
     * @return array<int, int|string>
     */
    public function keys(): array
    {
        return array_keys(get_object_vars($this));
    }

    /**
     * @return array<int|string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @return array<int|string, mixed>
     */
    public function toArray(bool $removeEmpty = false): array
    {
        $return = [];

        foreach (get_object_vars($this) as $key => $value) {
            if ($value instanceof \DateTimeInterface) {
                $return[$key] = $value->format('c');
            } elseif ($value instanceof self) {
                $return[$key] = $value->toArray($removeEmpty);
            } elseif ($value instanceof \BackedEnum) {
                $return[$key] = $value->value;
            } elseif (is_array($value)) {
                $return[$key] = $removeEmpty ? array_filter($value) : $value;
            } else {
                $return[$key] = $value;
            }
        }

        if ($removeEmpty) {
            $return = array_filter($return);
        }

        return $return;
    }
}
