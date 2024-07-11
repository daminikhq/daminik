<?php

declare(strict_types=1);

namespace App\Tests\Twig\Runtime;

use App\Entity\Invitation;
use App\Entity\LogEntry;
use App\Entity\Workspace;
use App\Twig\Runtime\LogEntryExtensionRuntime;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LogEntryExtensionRuntimeTest extends KernelTestCase
{
    /** @noinspection PhpPrivateFieldCanBeLocalVariableInspection */
    private ContainerInterface $container;
    private ?LogEntryExtensionRuntime $runtime = null;

    public function setUp(): void
    {
        self::bootKernel();
        $this->container = static::getContainer();
        /** @var LogEntryExtensionRuntime $runtime */
        $runtime = $this->container->get(LogEntryExtensionRuntime::class);
        $this->runtime = $runtime;
    }

    public function testInvitationEntityLink(): void
    {
        $this->assertInstanceOf(LogEntryExtensionRuntime::class, $this->runtime);
        $workspace = (new Workspace())
            ->setSlug('loglinktest');

        $invitation = (new LogEntry())
            ->setEntityClass(Invitation::class)
            ->setWorkspace($workspace);

        $link = $this->runtime->entityLink($invitation);
        $this->assertSame('<a href="//loglinktest.dam.localhost/invitations"></a>', $link);
    }
}
