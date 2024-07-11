<?php

declare(strict_types=1);

namespace App\Enum;

enum MultiAction: string
{
    case DELETE = 'delete';
    case UNDELETE = 'undelete';
    case COLLECTION_ADD = 'collection-add';
    case COLLECTION_REMOVE = 'collection-remove';
    case CATEGORY_ADD = 'category-add';
}
