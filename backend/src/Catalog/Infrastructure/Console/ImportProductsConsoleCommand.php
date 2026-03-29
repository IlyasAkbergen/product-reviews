<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Console;

use App\Catalog\Application\Command\ImportProducts\ImportProductsCommand;
use App\Shared\Application\Bus\CommandBusInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-products',
    description: 'Import products from dummyjson.com and warm the Redis rating cache.',
)]
final class ImportProductsConsoleCommand extends Command
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->info('Importing products from dummyjson.com…');

        $this->commandBus->dispatch(new ImportProductsCommand());

        $io->success('Products imported and rating cache warmed.');

        return Command::SUCCESS;
    }
}
