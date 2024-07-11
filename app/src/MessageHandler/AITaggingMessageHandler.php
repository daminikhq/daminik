<?php

namespace App\MessageHandler;

use App\Message\AITaggingMessage;
use App\Repository\FileRepository;
use App\Repository\UserRepository;
use App\Service\Ai\AiTaggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class AITaggingMessageHandler
{
    public function __construct(
        private FileRepository $fileRepository,
        private UserRepository $userRepository,
        private AiTaggerInterface $aiTagger,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(AITaggingMessage $message): void
    {
        $file = $this->fileRepository->find($message->getFileId());
        $user = $this->userRepository->find($message->getUserId());
        if (null === $file || null === $user || null !== $file->getAiTags()) {
            return;
        }

        try {
            $this->aiTagger->tag($file, $user);
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage(), [
                'exception' => $e::class,
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
        $this->entityManager->flush();
    }
}
