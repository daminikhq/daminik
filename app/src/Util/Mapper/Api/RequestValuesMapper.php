<?php

declare(strict_types=1);

namespace App\Util\Mapper\Api;

use App\Dto\Api\Rest\RequestValuesResponse;
use App\Dto\Filter\FilterMonth;
use App\Dto\Utility\FilterOptions;
use App\Interfaces\AutoCompleteItem;

class RequestValuesMapper
{
    /**
     * @param AutoCompleteItem[] $uploaderEntities
     */
    public static function mapOptionsAndUploadersToDto(FilterOptions $filterOptions, array $uploaderEntities): RequestValuesResponse
    {
        $months = array_map(static fn (FilterMonth $filterMonth): string => $filterMonth->getDateString(), $filterOptions->getMonths());
        $uploaders = array_map(static fn (AutoCompleteItem $user): string => $user->getValue(), $uploaderEntities);

        return new RequestValuesResponse(
            $months,
            $uploaders
        );
    }
}
