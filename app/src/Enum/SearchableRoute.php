<?php

declare(strict_types=1);

namespace App\Enum;

enum SearchableRoute: string
{
    case COLLECTION = 'workspace_collection_collection';
    case FOLDER = 'workspace_folder_index';
    case BIN = 'workspace_bin';
    case FAVORITES = 'workspace_favorites';
    case INDEX = 'workspace_index';
}
