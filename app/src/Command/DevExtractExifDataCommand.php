<?php

namespace App\Command;

use App\Message\RecheckAssetMetadataMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:recheck-asset-metadata',
    description: 'Rechecks all assets with missing metadata',
)]
class DevExtractExifDataCommand extends Command
{
    public function __construct(
        private readonly MessageBusInterface $bus,
        ?string $name = null,
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Forces the re-reading of all metadata ⚠️ This will take a while');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $force = $input->getOption('force');

        $this->bus->dispatch(new RecheckAssetMetadataMessage((bool) $force));

        return Command::SUCCESS;
    }
}
