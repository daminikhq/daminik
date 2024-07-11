<?php

declare(strict_types=1);

namespace App\Tests\Traits;

use App\Entity\File;
use App\Entity\User;
use App\Entity\Workspace;
use App\Enum\MimeType;
use Doctrine\ORM\EntityManagerInterface;
use Random\RandomException;

trait HasTestEntityFunctionsTrait
{
    private ?User $user = null;
    private ?Workspace $workspace = null;
    private ?File $file = null;

    /**
     * @throws RandomException
     */
    private function createTestWorkspace(?EntityManagerInterface $entityManager): Workspace
    {
        if ($this->workspace instanceof Workspace) {
            return $this->workspace;
        }

        $user = $this->createTestUser($entityManager);

        $workspace = (new Workspace())
            ->setName('Test')
            ->setSlug('test')
            ->setCreatedBy($user);
        $entityManager?->persist($workspace);
        $this->workspace = $workspace;

        return $workspace;
    }

    /**
     * @throws RandomException
     */
    private function createTestUser(?EntityManagerInterface $entityManager): User
    {
        if ($this->user instanceof User) {
            return $this->user;
        }
        $user = (new User())
            ->setEmail(sha1(random_int(0, 10).time()).'@example.com')
            ->setPassword('test');
        $entityManager?->persist($user);
        $this->user = $user;

        return $user;
    }

    /**
     * @throws RandomException
     */
    private function createTestFile(
        ?EntityManagerInterface $entityManager,
        string $filenameSlug = 'test',
        string $publicFilenameSlug = 'test',
        string $extension = 'png',
        bool $public = true,
    ): File {
        if ($this->file instanceof File) {
            return $this->file;
        }
        $user = $this->createTestUser($entityManager);

        $workspace = $this->createTestWorkspace($entityManager);

        $mime = MimeType::tryFromName(strtoupper($extension));

        $file = (new File())
            ->setWorkspace($workspace)
            ->setFilepath(sprintf('%s.%s', $filenameSlug, $extension))
            ->setFilenameSlug($filenameSlug)
            ->setPublicFilenameSlug($publicFilenameSlug)
            ->setExtension($extension)
            ->setMime($mime?->value)
            ->setPublic($public)
            ->setUploader($user);
        $entityManager?->persist($file);

        return $file;
    }
}
