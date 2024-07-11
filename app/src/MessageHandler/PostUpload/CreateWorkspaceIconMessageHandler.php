<?php

declare(strict_types=1);

namespace App\MessageHandler\PostUpload;

use App\Exception\FileHandlerException;
use App\Message\PostUpload\CreateWorkspaceIconMessage;
use App\Repository\WorkspaceRepository;
use App\Service\File\Resizer;
use League\Flysystem\FilesystemException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CreateWorkspaceIconMessageHandler
{
    public function __construct(
        private LoggerInterface $logger,
        private WorkspaceRepository $workspaceRepository,
        private Resizer $resizer
    ) {
    }

    /**
     * @throws FilesystemException
     * @throws FileHandlerException
     */
    public function __invoke(CreateWorkspaceIconMessage $message): void
    {
        $this->logger->debug(__METHOD__, [
            'workspaceId' => $message->getWorkspaceId(),
        ]);
        $workspace = $this->workspaceRepository->find($message->getWorkspaceId());
        $file = $workspace?->getIconFile();
        if (null === $workspace || null === $file) {
            $this->logger->debug('Workspace has no icon file');

            return;
        }
        $this->resizer->generateWorkspaceIcon($file);
    }
}
