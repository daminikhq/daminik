<?php

namespace App\Tests\Security\Voter;

use App\Entity\AssetCollection;
use App\Entity\Membership;
use App\Entity\User;
use App\Entity\Workspace;
use App\Enum\UserRole;
use App\Security\Voter\CollectionVoter;
use App\Util\User\RoleUtil;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * @group voter
 */
class CollectionVoterTest extends TestCase
{
    private CollectionVoter $voter;

    public function setUp(): void
    {
        $roleUtil = new RoleUtil();
        $this->voter = new CollectionVoter($roleUtil);
    }

    public function testLoggedOutUser(): void
    {
        // Token without User: User is not logged in
        $token = $this->createMock(TokenInterface::class);

        // Collection is set to private, VIEW and EDIT should be DENIED
        $privateCollection = (new AssetCollection())
            ->setPublic(false);

        $viewPrivateResult = $this->voter->vote($token, $privateCollection, [CollectionVoter::VIEW]);
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $viewPrivateResult);

        $editPrivateResult = $this->voter->vote($token, $privateCollection, [CollectionVoter::EDIT]);
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $editPrivateResult);

        // Collection is set to public, VIEW should be GRANTED and EDIT should be DENIED
        $publicCollection = (new AssetCollection())
            ->setPublic(true);

        $viewPublicResult = $this->voter->vote($token, $publicCollection, [CollectionVoter::VIEW]);
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $viewPublicResult);

        $editPublicResult = $this->voter->vote($token, $publicCollection, [CollectionVoter::EDIT]);
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $editPublicResult);
    }

    public function testLoggedInRandomUser(): void
    {
        // Token with User: User is logged in. This one is not in the current workspace, though
        $user = new User();
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        // Collection is set to private, VIEW and EDIT should be DENIED
        $privateCollection = (new AssetCollection())
            ->setPublic(false);

        $viewResult = $this->voter->vote($token, $privateCollection, [CollectionVoter::VIEW]);
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $viewResult);

        $editResult = $this->voter->vote($token, $privateCollection, [CollectionVoter::EDIT]);
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $editResult);
    }

    public function testLoggedInMember(): void
    {
        // User is logged in and in the current workspace
        $workspace = new Workspace();
        $user = new User();
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        // Collection is set to private, VIEW and EDIT should be GRANTED anyway
        $privateCollection = (new AssetCollection())
            ->setWorkspace($workspace)
            ->setPublic(false);

        $membership = (new Membership())
            ->setUser($user)
            ->setWorkspace($workspace)
            ->setRoles([UserRole::WORKSPACE_USER->value]);
        $user->addMembership($membership);
        $workspace->addMembership($membership);

        $viewResult = $this->voter->vote($token, $privateCollection, [CollectionVoter::VIEW]);
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $viewResult);

        $editResult = $this->voter->vote($token, $privateCollection, [CollectionVoter::EDIT]);
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $editResult);
    }
}
