<?php

/** @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection */
declare(strict_types=1);

namespace App\Exception\Workspace;

use App\Exception\WorkspaceException;

class MissingConfigException extends WorkspaceException
{
    /**
     * @param string|mixed[]|null $field
     */
    public function __construct(string $message = '', int $code = 0, protected string|array|null $field = null, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return mixed[]|string|null
     */
    public function getField(): array|string|null
    {
        return $this->field;
    }
}
