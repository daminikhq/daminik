<?php

namespace App\Command;

use App\Message\CompletelyDeleteAssetMessage;
use App\Repository\FileRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:clean-deleted-assets',
    description: 'Delete solt-deleted assets older than 30 days',
)]
class CleanDeletedAssetsCommand extends Command
{
    public function __construct(
        private readonly FileRepository $fileRepository,
        private readonly MessageBusInterface $bus,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $deleted = (new \DateTimeImmutable())
            ->sub(new \DateInterval('P30D'));
        $oldAssets = $this->fileRepository->findDeletedBefore($deleted);
        if (count($oldAssets) < 1) {
            $io->success('Did not find any old assets');

            return self::SUCCESS;
        }
        $this->logger->info('Found old assets', [
            'count' => count($oldAssets),
        ]);
        foreach ($oldAssets as $oldAsset) {
            if (null !== $oldAsset->getId()) {
                try {
                    $this->bus->dispatch(new CompletelyDeleteAssetMessage($oldAsset->getId()));
                } catch (ExceptionInterface $e) {
                    $this->logger->critical(
                        $e->getMessage(),
                        [
                            'exception' => $e,
                            'asset' => $oldAsset->getId(),
                        ]
                    );
                }
            }
        }
        $io->success('Started to delete old assets');

        return Command::SUCCESS;
    }
}
