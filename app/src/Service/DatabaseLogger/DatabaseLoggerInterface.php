<?php

declare(strict_types=1);

namespace App\Service\DatabaseLogger;

use App\Dto\DatabaseLogger\MetaDataInterface;
use App\Dto\Utility\SortFilterPaginateArguments;
use App\Entity\Workspace;
use App\Enum\UserAction;
use App\Util\Paginator;
use App\Util\Paginator\PaginatorException;
use Symfony\Component\Security\Core\User\UserInterface;

interface DatabaseLoggerInterface
{
    /**
     * @param array<mixed>|MetaDataInterface|null $metadata
     *
     * @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection
     */
    public function log(UserAction $userAction, ?object $object = null, array|MetaDataInterface|null $metadata = null, ?UserInterface $actingUser = null, ?Workspace $workspace = null): void;

    /**
     * @throws PaginatorException
     */
    public function getEntries(Workspace $workspace, SortFilterPaginateArguments $arguments): Paginator;
}
