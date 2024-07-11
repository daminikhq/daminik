<?php

declare(strict_types=1);

namespace App\Dto\Api\Rest;

use App\Dto\AbstractDto;
use OpenApi\Attributes as OA;

class RequestValuesResponse extends AbstractDto
{
    /**
     * @param string[] $months
     * @param string[] $uploaders
     */
    public function __construct(
        #[OA\Property(
            example: ['2024-05']
        )]
        protected array $months = [],
        #[OA\Property(
            example: ['janesmith']
        )]
        protected array $uploaders = []
    ) {
    }

    /**
     * @return string[]
     */
    public function getMonths(): array
    {
        return $this->months;
    }

    /**
     * @param string[] $months
     */
    public function setMonths(array $months): RequestValuesResponse
    {
        $this->months = $months;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getUploaders(): array
    {
        return $this->uploaders;
    }

    /**
     * @param string[] $uploaders
     */
    public function setUploaders(array $uploaders): RequestValuesResponse
    {
        $this->uploaders = $uploaders;

        return $this;
    }
}
