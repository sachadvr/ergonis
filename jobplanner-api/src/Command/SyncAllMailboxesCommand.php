<?php

declare(strict_types=1);

namespace App\Command;

use App\Message\SyncAllMailboxesMessage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class SyncAllMailboxesCommand extends Command
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('app:sync-all-mailboxes');
        $this->setDescription('Dispatch sync for all mailboxes');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->messageBus->dispatch(new SyncAllMailboxesMessage());
        $output->writeln('Dispatched SyncAllMailboxesMessage');

        return Command::SUCCESS;
    }
}
