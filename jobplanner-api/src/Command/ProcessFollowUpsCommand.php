<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\ScheduledFollowUpRepository;
use App\Service\FollowUpProcessorService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:follow-ups:process',
    description: 'Process the scheduled follow-ups whose date has passed',
)]
final class ProcessFollowUpsCommand extends Command
{
    public function __construct(
        private readonly ScheduledFollowUpRepository $followUpRepository,
        private readonly FollowUpProcessorService $processor,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $pending = $this->followUpRepository->findPendingDueNow();

        if (empty($pending)) {
            $io->success('No follow-up to process.');

            return Command::SUCCESS;
        }

        $io->info(sprintf('%d follow-up(s) to process.', \count($pending)));

        foreach ($pending as $followUp) {
            $app = $followUp->getApplication();
            $offerTitle = $app->getJobOffer()->getTitle();
            $io->text("  → {$offerTitle} (ID {$followUp->getId()})");
            $this->processor->process($followUp);
        }

        $io->success('Follow-ups processed.');

        return Command::SUCCESS;
    }
}
