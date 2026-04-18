<?php

declare(strict_types=1);

namespace App\Command;

use App\Message\SyncEmailsMessage;
use App\MessageHandler\SyncEmailsHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:mailbox:sync',
    description: 'Synchronize the emails of the mailbox for a user',
)]
final class SyncMailboxCommand extends Command
{
    public function __construct(
        private readonly SyncEmailsHandler $syncEmailsHandler,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('user-id', InputArgument::REQUIRED, 'ID of the user');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $userId = (int) $input->getArgument('user-id');

        $io->info("Starting sync for User {$userId}...");

        try {
            ($this->syncEmailsHandler)(new SyncEmailsMessage($userId));
            $io->success('Sync completed successfully!');

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error('ERROR: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
