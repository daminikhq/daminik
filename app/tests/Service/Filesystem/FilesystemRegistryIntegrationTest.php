<?php

declare(strict_types=1);

namespace App\Tests\Service\Filesystem;

use App\Entity\Workspace;
use App\Enum\FilesystemType;
use App\Service\Filesystem\FilesystemRegistry;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FilesystemRegistryIntegrationTest extends WebTestCase
{
    public function testGetDefaultFilesystem(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        /** @var FilesystemRegistry $filesystemRegistry */
        $filesystemRegistry = $container->get(FilesystemRegistry::class);

        $workspace = new Workspace();
        $filesystem = $filesystemRegistry->getWorkspaceFilesystemConfig($workspace);
        self::assertSame(FilesystemType::LOCAL->value, $filesystem->getType());
    }
}
