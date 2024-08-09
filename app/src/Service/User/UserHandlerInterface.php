<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Dto\Admin\Form\UserEdit;
use App\Dto\User\Interface\LocaleRequestChangeInterface;
use App\Dto\User\Interface\NameRequestInterface;
use App\Dto\User\Interface\UsernameRequestInterface;
use App\Dto\Utility\SortFilterPaginateArguments;
use App\Entity\RegistrationCode;
use App\Entity\User;
use App\Entity\Workspace;
use App\Exception\UserHandlerException;
use App\Exception\UsernameAlreadyTakenException;
use App\Interfaces\AutoCompleteQueriable;
use App\Util\Paginator;

interface UserHandlerInterface extends AutoCompleteQueriable
{
    /**
     * @throws UserHandlerException
     */
    public function changeName(NameRequestInterface $action): void;

    /**
     * @throws UserHandlerException
     * @throws UsernameAlreadyTakenException
     */
    public function changeUsername(UsernameRequestInterface $action): void;

    public function changeLocale(LocaleRequestChangeInterface $action): void;

    public function filterAndPaginateUsers(
        SortFilterPaginateArguments $sortFilterPaginateArguments,
        ?RegistrationCode $registrationCode = null
    ): Paginator;

    public function adminUpdateUser(User $userToEdit, UserEdit $edit, User $user): void;

    /**
     * @return array<int, array{uploader: int, filecount: int}>
     */
    public function getWorkspaceUserUploadCounts(Workspace $workspace, bool $cached = true): array;
}
