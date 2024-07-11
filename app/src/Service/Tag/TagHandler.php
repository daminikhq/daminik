<?php

declare(strict_types=1);

namespace App\Service\Tag;

use App\Entity\File;
use App\Entity\FileTag;
use App\Entity\Tag;
use App\Entity\User;
use App\Entity\Workspace;
use App\Enum\TagType;
use App\Repository\FileTagRepository;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

readonly class TagHandler implements TagHandlerInterface
{
    public function __construct(
        private SluggerInterface $slugger,
        private TagRepository $tagRepository,
        private FileTagRepository $fileTagRepository,
        private EntityManagerInterface $entityManager,
        private CacheInterface $cache
    ) {
    }

    public function saveTags(File $file, ?string $tagString, ?User $user = null, bool $ai = false): void
    {
        if (null === $tagString) {
            $tagString = '';
        }
        $this->updateTags(file: $file, tagString: $tagString, user: $user ?? $file->getUploader(), ai: $ai);
    }

    public function addTags(File $file, string $tagString, ?User $user = null, bool $ai = false): void
    {
        $this->updateTags(file: $file, tagString: $tagString, user: $user ?? $file->getUploader(), overWrite: false, ai: $ai);
    }

    public function updateTags(File $file, string $tagString, ?User $user = null, bool $overWrite = true, bool $ai = false): void
    {
        $tagTitles = array_filter(explode(',', $tagString));
        $tagTitles = array_map(static fn (string $title) => trim($title), $tagTitles);
        $tagTitles = array_unique($tagTitles);

        if ($overWrite) {
            foreach ($file->getFileTags() as $fileTag) {
                if (null !== $fileTag->getTitle() && !in_array($fileTag->getTitle(), $tagTitles, true)) {
                    $file->removeFileTag($fileTag);
                }
            }
        }

        /** @var string $tagTitle */
        foreach ($tagTitles as $tagTitle) {
            $tagSlug = $this->slugger->slug(strtolower($tagTitle))->toString();
            $tag = $this->tagRepository->findOneBy(['slug' => $tagSlug, 'workspace' => $file->getWorkspace()]);
            if (null === $tag) {
                $tag = (new Tag())
                    ->setTitle($tagTitle)
                    ->setSlug($tagSlug)
                    ->setWorkspace($file->getWorkspace())
                    ->setCreator($user);

                $this->entityManager->persist($tag);
            }

            $fileTag = $this->fileTagRepository->findOneBy(['file' => $file, 'tag' => $tag]);
            if (null === $fileTag) {
                $fileTag = (new FileTag())
                    ->setFile($file)
                    ->setTag($tag)
                    ->setTitle($tagTitle)
                    ->setCreator($user)
                    ->setType($ai ? TagType::AI : TagType::HUMAN);
                $this->entityManager->persist($fileTag);
            }
            $file->addFileTag($fileTag);
        }
        if ($user instanceof User) {
            $file->setUpdatedBy($user);
        }
    }

    public function getTagString(File $file): string
    {
        return implode(', ', $this->getTagStringArray($file));
    }

    /**
     * @return string[]
     */
    public function getTagStringArray(File $file): array
    {
        $tagTitles = [];
        /** @var FileTag $fileTag */
        foreach ($file->getFileTags() as $fileTag) {
            $tagTitles[] = $fileTag->getTitle();
        }

        return array_filter($tagTitles);
    }

    /**
     * @return Tag[]
     *
     * @throws InvalidArgumentException
     */
    public function getForAutocomplete(Workspace $workspace, ?string $query = null, int $limit = 10, bool $cached = true): array
    {
        if ($cached) {
            $cacheKey = sprintf('autocomplete-tag-%s-%s', $workspace->getSlug(), $query);

            return $this->cache->get($cacheKey, function (ItemInterface $item) use ($workspace, $query, $limit) {
                $item->expiresAfter(new \DateInterval('PT30S'));

                return $this->getForAutocomplete($workspace, $query, $limit, false);
            });
        }

        return $this->tagRepository->findForAutocomplete($workspace, $query, $limit);
    }

    public function getTagFromString(string $slug, Workspace $workspace): ?Tag
    {
        return $this->tagRepository->findOneBy(['slug' => $slug, 'workspace' => $workspace]);
    }
}
