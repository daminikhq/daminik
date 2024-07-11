<?php

namespace App\MessageHandler;

use App\Message\CompletelyDeleteAssetMessage;
use App\Repository\FileRepository;
use App\Service\File\DeleterInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CompletelyDeleteAssetMessageHandler
{
    public function __construct(
        private FileRepository $fileRepository,
        private DeleterInterface $deleter,
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(CompletelyDeleteAssetMessage $message): void
    {
        $file = $this->fileRepository->find($message->getId());
        if (null === $file) {
            return;
        }
        try {
            $this->deleter->completelyDeleteFile($file);
        } catch (\Throwable $e) {
            $this->logger->error('Error deleting file', [
                'exception' => $e::class,
                'file' => $file->getId(),
                'workspace' => $file->getWorkspace()?->getSlug(),
                'exceptionFile' => $e->getFile(),
                'exceptionLine' => $e->getLine(),
                'exceptionMessage' => $e->getMessage(),
                'exceptionCode' => $e->getCode(),
            ]);
        }
    }
}
