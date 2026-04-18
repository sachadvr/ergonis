<?php

declare(strict_types=1);

namespace App\Command;

use App\Mailer\UserMailerService;
use App\Repository\InterviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'app:interview-reminders:send',
    description: 'Send reminders for interviews in the next 24 hours',
)]
final class SendInterviewRemindersCommand extends Command
{
    public function __construct(
        private readonly InterviewRepository $interviewRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserMailerService $userMailerService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('hours', null, InputOption::VALUE_OPTIONAL, 'Hours before the interview to send the reminder', '24');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $hours = (int) $input->getOption('hours');
        $interviews = $this->interviewRepository->findUpcomingNeedingReminder($hours);

        if (empty($interviews)) {
            $io->success('No reminder to send.');

            return Command::SUCCESS;
        }

        foreach ($interviews as $interview) {
            $app = $interview->getApplication();
            $jobOffer = $app->getJobOffer();
            $owner = $app->getOwner();
            if (null === $owner || '' === $owner->getUserIdentifier()) {
                $io->warning("Interview ID {$interview->getId()} : no user email, ignored.");
                continue;
            }

            $to = $owner->getUserIdentifier();

            if (null === $owner->getId()) {
                $io->warning("Interview ID {$interview->getId()} : user without ID, ignored.");
                continue;
            }

            $from = $owner->getUserIdentifier();

            $dateStr = $interview->getScheduledAt()->format('d/m/Y à H:i');
            $offerTitle = $jobOffer->getTitle();
            $company = $jobOffer->getCompany();
            $locationOrLink = $interview->getLocationOrLink();

            $body = "Hello,\n\nReminder: you have an interview scheduled for {$dateStr} for the post of {$offerTitle} at {$company}.";
            if ($locationOrLink) {
                $body .= "\n\nLink: {$locationOrLink}";
            }
            $body .= "\n\nGood preparation!";

            $email = (new Email())
                ->from($from)
                ->to($to)
                ->subject("Interview reminder: {$offerTitle} - {$company}")
                ->text($body);
            $this->userMailerService->send((int) $owner->getId(), $email);

            $interview->setReminderSent(true);
            $io->text("  → {$offerTitle} at {$company} ({$dateStr})");
        }

        $this->entityManager->flush();
        $io->success(sprintf('%d reminder(s) processed.', \count($interviews)));

        return Command::SUCCESS;
    }
}
