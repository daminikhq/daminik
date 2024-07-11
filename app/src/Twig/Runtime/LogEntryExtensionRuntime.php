<?php

namespace App\Twig\Runtime;

use App\Entity\File;
use App\Entity\Invitation;
use App\Entity\LogEntry;
use App\Repository\FileRepository;
use App\Repository\UserRepository;
use Symfony\Component\Routing\RouterInterface;
use Twig\Extension\RuntimeExtensionInterface;

readonly class LogEntryExtensionRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private UserRepository $userRepository,
        private RouterInterface $router,
        private FileRepository $fileRepository
    ) {
    }

    public function userName(LogEntry $entry): string
    {
        if (null === $entry->getUserId()) {
            return '';
        }

        $user = $this->userRepository->find($entry->getUserId());
        if (null === $user) {
            return '';
        }

        return $user->getName() ?? (string) $user->getEmail();
    }

    public function action(LogEntry $entry): string
    {
        // @todo Irgendwas hübsches mit dem Translator
        return (string) $entry->getAction();
    }

    public function entityLink(LogEntry $entry): string
    {
        switch ($entry->getEntityClass()) {
            case Invitation::class:
                return $this->createLink($this->router->generate('workspace_admin_invitations', ['subdomain' => $entry->getWorkspace()?->getSlug()]), (string) $entry->getEntityId());
            case File::class:
                $file = null !== $entry->getEntityId() ? $this->fileRepository->find($entry->getEntityId()) : null;
                if (null === $file || (null !== $file->getDeletedAt() && $file->getDeletedAt() <= (new \DateTime()))) {
                    return (string) $entry->getEntityId();
                }

                return $this->createLink($this->router->generate('workspace_file_edit', ['subdomain' => $entry->getWorkspace()?->getSlug(), 'filename' => $file->getFilename()]), (string) $entry->getEntityId());
            default:
                return '';
        }
    }

    /** @noinspection HtmlUnknownTarget */
    private function createLink(string $route, string $link): string
    {
        return sprintf('<a href="%s">%s</a>', $route, $link);
    }
}
