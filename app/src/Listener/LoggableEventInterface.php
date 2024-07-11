<?php

/** @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection */
declare(strict_types=1);

namespace App\Listener;

use App\Entity\Workspace;
use App\Enum\UserAction;
use Symfony\Component\Security\Core\User\UserInterface;

interface LoggableEventInterface
{
    public function getUserAction(): UserAction;

    public function getObject(): object|null;

    /**
     * @return array<mixed>|null
     */
    public function getMetadata(): array|null;

    public function getActingUser(): ?UserInterface;

    public function getWorkspace(): ?Workspace;
}
