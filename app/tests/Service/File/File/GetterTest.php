<?php

declare(strict_types=1);

namespace App\Tests\Service\File\File;

use App\Entity\Workspace;
use App\Exception\File\GetterException;
use App\Repository\FileRepository;
use App\Service\File\Getter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetterTest extends TestCase
{
    private Workspace $workspace;
    private MockObject&FileRepository $fileRepository;
    private Getter $getter;

    public function setUp(): void
    {
        $this->workspace = new Workspace();
        $this->fileRepository = $this->createMock(FileRepository::class);
        $this->getter = new Getter($this->fileRepository);
    }

    /**
     * @throws GetterException
     */
    public function testGetFileByFilename(): void
    {
        $filename = 'test.jpg';
        $this->fileRepository->method('findOneBy')->with(['workspace' => $this->workspace, 'filename' => $filename, 'deletedAt' => null])->willReturn(null);
        $file = $this->getter->getFile(workspace: $this->workspace, filename: $filename);
        $this->assertNull($file);
    }

    /**
     * @throws GetterException
     */
    public function testGetFileBySlug(): void
    {
        $slug = 'test';
        $this->fileRepository->method('findOneBy')->with(['workspace' => $this->workspace, 'filenameSlug' => $slug, 'deletedAt' => null])->willReturn(null);
        $file = $this->getter->getFile(workspace: $this->workspace, slug: $slug);
        $this->assertNull($file);
    }

    /**
     * @throws GetterException
     */
    public function testGetFileWithoutSlugOrFilename(): void
    {
        $this->expectException(GetterException::class);
        $this->getter->getFile(workspace: $this->workspace);
    }
}
