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
    description: 'Envoie les rappels pour les entretiens dans les 24h',
)]
final class SendInterviewRemindersCommand extends Command
{
    public function __construct(
        private readonly InterviewRepository $interviewRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserMailerService $userMailerService,
        private readonly string $simulationMode = 'true',
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('hours', null, InputOption::VALUE_OPTIONAL, 'Heures avant l\'entretien pour envoyer le rappel', '24');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $hours = (int) $input->getOption('hours');
        $interviews = $this->interviewRepository->findUpcomingNeedingReminder($hours);

        if (empty($interviews)) {
            $io->success('Aucun rappel à envoyer.');

            return Command::SUCCESS;
        }

        $isSimulation = filter_var($this->simulationMode, \FILTER_VALIDATE_BOOLEAN);
        if ($isSimulation) {
            $io->note('Mode simulation : aucun email ne sera envoyé.');
        }

        foreach ($interviews as $interview) {
            $app = $interview->getApplication();
            $jobOffer = $app->getJobOffer();
            $owner = $app->getOwner();
            if (null === $owner || '' === $owner->getUserIdentifier()) {
                $io->warning("Entretien ID {$interview->getId()} : pas d'email utilisateur, ignoré.");
                continue;
            }

            $to = $owner->getUserIdentifier();

            if (null === $owner->getId()) {
                $io->warning("Entretien ID {$interview->getId()} : utilisateur sans ID, ignoré.");
                continue;
            }

            $from = $owner->getUserIdentifier();

            $dateStr = $interview->getScheduledAt()->format('d/m/Y à H:i');
            $poste = $jobOffer->getTitle();
            $entreprise = $jobOffer->getCompany();
            $lien = $interview->getLocationOrLink();

            $body = "Bonjour,\n\nRappel : vous avez un entretien prévu le {$dateStr} pour le poste de {$poste} chez {$entreprise}.";
            if ($lien) {
                $body .= "\n\nLien : {$lien}";
            }
            $body .= "\n\nBonne préparation !";

            if (!$isSimulation) {
                $email = (new Email())
                    ->from($from)
                    ->to($to)
                    ->subject("Rappel entretien : {$poste} - {$entreprise}")
                    ->text($body);
                $this->userMailerService->send((int) $owner->getId(), $email);
            }

            $interview->setReminderSent(true);
            $io->text("  → {$poste} chez {$entreprise} ({$dateStr})");
        }

        $this->entityManager->flush();
        $io->success(sprintf('%d rappel(s) traité(s).', \count($interviews)));

        return Command::SUCCESS;
    }
}
