<?php

declare(strict_types=1);

namespace App\Command;

use App\Mailer\UserMailerService;
use App\Message\SyncEmailsMessage;
use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'send:mail',
    description: 'Envoie un email et lance la sync mailbox pour le user destinataire',
)]
final class SendMailCommand extends Command
{
    public function __construct(
        private readonly UserMailerService $userMailerService,
        private readonly UserRepository $userRepository,
        private readonly MessageBusInterface $messageBus,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('from', null, InputOption::VALUE_REQUIRED, 'Adresse expediteur')
            ->addOption('to', null, InputOption::VALUE_REQUIRED, 'Adresse destinataire')
            ->addOption('subject', null, InputOption::VALUE_REQUIRED, 'Sujet du mail')
            ->addOption('content', null, InputOption::VALUE_REQUIRED, 'Contenu texte du mail');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $from = trim((string) $input->getOption('from'));
        $to = trim((string) $input->getOption('to'));
        $subject = trim((string) $input->getOption('subject'));
        $content = trim((string) $input->getOption('content'));

        if ('' === $from || '' === $to || '' === $subject || '' === $content) {
            $io->error('Options requises: --from, --to, --subject, --content');

            return Command::INVALID;
        }

        $email = (new Email())
            ->from($from)
            ->to($to)
            ->subject($subject)
            ->text($content)
            ->html(nl2br(htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')));

        try {
            $sender = $this->userRepository->findOneBy(['email' => $from]);
            if (null === $sender || null === $sender->getId()) {
                $io->error('Aucun user local ne correspond a l\'expediteur.');

                return Command::INVALID;
            }

            $this->userMailerService->send((int) $sender->getId(), $email);
            $io->success(sprintf('Email envoye via la boite de %s', $from));
        } catch (\Throwable $e) {
            $io->error('Echec envoi via la boite utilisateur: '.$e->getMessage());

            return Command::FAILURE;
        }

        $user = $this->userRepository->findOneBy(['email' => $to]);
        if (null === $user) {
            $io->note('Aucun user local pour ce destinataire, sync ignoree.');

            return Command::SUCCESS;
        }

        try {
            $this->messageBus->dispatch(new SyncEmailsMessage((int) $user->getId()));
            $io->success(sprintf('Sync declenchee pour user %s', $to));
        } catch (\Throwable $e) {
            $io->warning('Mail envoye mais sync echouee: '.$e->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
