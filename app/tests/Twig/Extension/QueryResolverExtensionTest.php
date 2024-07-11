<?php

declare(strict_types=1);

namespace App\Tests\Twig\Extension;

use App\Twig\Extension\QueryResolverExtension;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class QueryResolverExtensionTest extends KernelTestCase
{
    private QueryResolverExtension $queryResolverExtension;

    public function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        /** @var QueryResolverExtension $queryResolverExtension */
        $queryResolverExtension = $container->get(QueryResolverExtension::class);
        $this->queryResolverExtension = $queryResolverExtension;
    }

    public function testWorkspaceFileRoute(): void
    {
        $result = $this->queryResolverExtension->getPath('workspace_file_new_collection', [
            'filename' => 'test.png',
            'subdomain' => 'test',
            'domain' => 'test',
            'tld' => 'localhost',
        ]);
        self::assertSame('//test.test.localhost/file/edit/test.png/collection', $result);
    }
}
