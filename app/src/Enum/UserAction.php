<?php

declare(strict_types=1);

namespace App\Enum;

enum UserAction: string
{
    case CREATE_INVITATION = 'create_invitation';
    case ACCEPT_INVITATION = 'accept_invitation';

    case UPLOAD_FILE = 'upload_file';
    case EDIT_FILE = 'edit_file';
    case DELETE_FILE = 'delete_file';
    case UNDELETE_FILE = 'undelete_file';
    case COMPLETELY_DELETE_FILE = 'completetly_delete_file';

    case CHANGE_FILE_CATEGORY = 'change_file_category';

    case ADD_FILE_TO_COLLECTION = 'add_file_to_collection';
    case REMOVE_FILE_FROM_COLLECTION = 'remove_file_from_collection';
    case CREATE_COLLECTION = 'create_collection';
    case DELETE_COLLECTION = 'delete_collection';
    case UPDATE_COLLECTION_CONFIG = 'update_collection_config';

    case UPDATE_USER = 'update_user';
    case UPDATE_MEMBERSHIP = 'update_membership';
    case DELETE_MEMBERSHIP = 'delete_membership';

    case CREATE_CATEGORY = 'create_category';
    case DELETE_CATEGORY = 'delete_category';
    case EDIT_CATEGORY = 'edit_category';
    case UPDATE_WORKSPACE_CONFIG = 'update_workspace_config';
}
