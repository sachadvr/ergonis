<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\ImapConnectionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:mailbox:list-folders',
    description: 'List available mailbox folders for a user',
)]
final class ListMailboxFoldersCommand extends Command
{
    public function __construct(
        private readonly ImapConnectionService $imapConnectionService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('user-id', InputArgument::REQUIRED, 'User ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $userId = (int) $input->getArgument('user-id');

        $io->info("Listing folders for User {$userId}...");

        $folders = $this->imapConnectionService->listAvailableFolders($userId);

        if ([] === $folders) {
            $io->warning('No folders found or mailbox is not reachable.');

            return Command::SUCCESS;
        }

        $io->writeln($folders);

        return Command::SUCCESS;
    }
}
