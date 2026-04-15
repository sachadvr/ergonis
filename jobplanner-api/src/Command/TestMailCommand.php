<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * Envoie un email de test pour vérifier que Mailpit (ou le SMTP configuré) le reçoit.
 * Usage : php bin/console app:mail:test [--to=email@example.com].
 */
#[AsCommand(
    name: 'app:mail:test',
    description: 'Envoie un email de test pour vérifier la configuration SMTP (Mailpit)',
)]
final class TestMailCommand extends Command
{
    public function __construct(
        private readonly MailerInterface $mailer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('to', 't', InputOption::VALUE_OPTIONAL, 'Adresse destinataire', 'test@jobplanner.local')
            ->addOption('subject', 's', InputOption::VALUE_OPTIONAL, 'Sujet du mail', 'Test JobPlanner - Configuration SMTP');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $to = (string) $input->getOption('to');
        $subject = (string) $input->getOption('subject');

        $email = (new Email())
            ->from('noreply@jobplanner.local')
            ->to($to)
            ->subject($subject)
            ->text("Ceci est un email de test envoyé par JobPlanner.\n\nSi vous recevez ce message, la configuration SMTP (Mailpit) fonctionne correctement.\n\nDate : ".date('Y-m-d H:i:s'))
            ->html('<p>Ceci est un email de test envoyé par <strong>JobPlanner</strong>.</p><p>Si vous recevez ce message, la configuration SMTP (Mailpit) fonctionne correctement.</p><p>Date : '.date('Y-m-d H:i:s').'</p>');

        try {
            $this->mailer->send($email);
            $io->success("Email de test envoyé vers {$to}");
            $io->note('Vérifiez Mailpit : http://localhost:8025');

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error('Erreur lors de l\'envoi : '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
