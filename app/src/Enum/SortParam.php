<?php

declare(strict_types=1);

namespace App\Enum;

enum SortParam: string
{
    case UPLOADED_ASC = 'uploaded_asc';
    case UPLOADED_DESC = 'uploaded_desc';
    case MODIFIED_ASC = 'modified_asc';
    case MODIFIED_DESC = 'modified_desc';

    public function swap(): self
    {
        return match ($this) {
            self::UPLOADED_ASC => self::UPLOADED_DESC,
            self::MODIFIED_ASC => self::MODIFIED_DESC,
            self::MODIFIED_DESC => self::MODIFIED_ASC,
            self::UPLOADED_DESC => self::UPLOADED_ASC,
        };
    }
}
