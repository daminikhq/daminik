<?php

/** @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection */
declare(strict_types=1);

namespace App\Service\File\Filter;

use App\Dto\Filter\BooleanFilter;
use App\Enum\MimeType;
use App\Exception\FileHandlerException;

class MimeTypeFilter extends ChoiceFilter
{
    /**
     * @param mixed[] $args
     *
     * @throws FileHandlerException
     */
    protected function resolveArgs(array $args): void
    {
        $args = array_filter($args);

        if (count($args) < 1) {
            $args = MimeType::cases();
        }

        foreach ($args as $filterParameter) {
            if (is_array($filterParameter)) {
                $this->resolveArgs($filterParameter);
            } elseif (is_string($filterParameter)) {
                $this->addCriteria(['mime', 'eq', $filterParameter]);
            } elseif ($filterParameter instanceof MimeType) {
                $this->addCriteria(['mime', 'eq', $filterParameter->value]);
            } elseif ($filterParameter instanceof BooleanFilter && $filterParameter->getValue()) {
                $this->addCriteria(['mime', 'eq', $filterParameter->getKey()]);
            }
        }
    }
}
