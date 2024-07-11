<?php

declare(strict_types=1);

namespace App\Tests\Service\File\File\Deleter\Middleware;

use App\Entity\AssetCollection;
use App\Entity\File;
use App\Entity\FileAssetCollection;
use App\Service\File\Deleter\Middleware\FileAssetCollectionDeleterMiddleware;
use App\Service\File\Deleter\Payload\FileDeletePayload;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class FileAssetCollectionDeleterMiddlewareUnitTest extends TestCase
{
    public function testDeleteAssetCollections(): void
    {
        $assetCollection = new AssetCollection();
        $file = new File();
        $fileAssetCollection = (new FileAssetCollection())
            ->setFile($file)
            ->setAssetCollection($assetCollection);
        $file->addFileAssetCollection($fileAssetCollection);

        $logger = $this->createMock(LoggerInterface::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $payload = new FileDeletePayload($file, $logger);
        $middleware = new FileAssetCollectionDeleterMiddleware($entityManager);

        $this->assertCount(1, $payload->getFile()->getFileAssetCollections());

        $payload = $middleware->pipe($payload);
        $this->assertInstanceOf(FileDeletePayload::class, $payload);
        $this->assertCount(0, $payload->getFile()->getFileAssetCollections());
    }
}
