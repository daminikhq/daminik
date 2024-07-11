<?php

declare(strict_types=1);

namespace App\Tests\Service\User;

use App\Entity\Membership;
use App\Entity\User;
use App\Entity\Workspace;
use App\Enum\MembershipStatus;
use App\Enum\UserRole;
use App\Exception\UserHandler\CantRemoveLastOwnerException;
use App\Exception\UserHandlerException;
use App\Repository\MembershipRepository;
use App\Service\User\MembershipHandler;
use App\Util\Paginator;
use App\Util\User\RoleUtil;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MembershipHandlerUnitTest extends TestCase
{
    private MembershipHandler $membershipHandler;

    public function setUp(): void
    {
        $this->membershipHandler = new MembershipHandler(
            paginator: $this->createMock(Paginator::class),
            membershipRepository: $this->createMock(MembershipRepository::class),
            workspaceRoleUtil: $this->createMock(RoleUtil::class),
            entityManager: $this->createMock(EntityManagerInterface::class),
            dispatcher: $this->createMock(EventDispatcherInterface::class)
        );
    }

    /**
     * @throws UserHandlerException
     */
    public function testCheckWorkspaceWithoutOwner(): void
    {
        $workspace = new Workspace();

        $member1 = new User();
        $membership1 = (new Membership())
            ->setStatus(MembershipStatus::ACTIVE->value);
        $workspace->addMembership($membership1);
        $member1->addMembership($membership1);

        $member2 = new User();
        $membership2 = (new Membership())
            ->setStatus(MembershipStatus::ACTIVE->value);
        $workspace->addMembership($membership2);
        $member2->addMembership($membership2);

        $this->assertCount(2, $workspace->getMemberships());
        $this->expectException(CantRemoveLastOwnerException::class);
        $this->membershipHandler->checkWorkspaceForOwner($workspace);
    }

    /**
     * @throws UserHandlerException
     */
    public function testCheckWorkspaceWithOneOwner(): void
    {
        $workspace = new Workspace();

        $member1 = new User();
        $membership1 = (new Membership())
            ->setStatus(MembershipStatus::ACTIVE->value);
        $workspace->addMembership($membership1);
        $member1->addMembership($membership1);

        $member2 = new User();
        $membership2 = (new Membership())
            ->setStatus(MembershipStatus::ACTIVE->value)
            ->setRoles([UserRole::WORKSPACE_OWNER->value]);
        $workspace->addMembership($membership2);
        $member2->addMembership($membership2);

        $this->assertCount(2, $workspace->getMemberships());
        $this->membershipHandler->checkWorkspaceForOwner($workspace);
    }

    /**
     * @throws UserHandlerException
     */
    public function testCheckWorkspaceWithLastOwner(): void
    {
        $workspace = new Workspace();

        $member1 = new User();
        $membership1 = (new Membership())
            ->setStatus(MembershipStatus::ACTIVE->value)
            ->setRoles([UserRole::WORKSPACE_OWNER->value]);
        $workspace->addMembership($membership1);
        $member1->addMembership($membership1);

        $member2 = new User();
        $membership2 = (new Membership())
            ->setStatus(MembershipStatus::ACTIVE->value);
        $workspace->addMembership($membership2);
        $member2->addMembership($membership2);

        $this->assertCount(2, $workspace->getMemberships());
        $this->expectException(CantRemoveLastOwnerException::class);
        $this->membershipHandler->checkWorkspaceForOwner($workspace, $member1);
    }

    /**
     * @throws UserHandlerException
     */
    public function testCheckWorkspaceWithTwoOwners(): void
    {
        $workspace = new Workspace();

        $member1 = new User();
        $membership1 = (new Membership())
            ->setStatus(MembershipStatus::ACTIVE->value)
            ->setRoles([UserRole::WORKSPACE_OWNER->value]);
        $workspace->addMembership($membership1);
        $member1->addMembership($membership1);

        $member2 = new User();
        $membership2 = (new Membership())
            ->setStatus(MembershipStatus::ACTIVE->value)
            ->setRoles([UserRole::WORKSPACE_OWNER->value]);
        $workspace->addMembership($membership2);
        $member2->addMembership($membership2);

        $this->assertCount(2, $workspace->getMemberships());
        $this->membershipHandler->checkWorkspaceForOwner($workspace, $member1);
    }
}
