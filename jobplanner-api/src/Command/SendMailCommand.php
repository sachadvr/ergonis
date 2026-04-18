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
    description: 'Send an email and launch the mailbox sync for the recipient user',
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
            ->addOption('from', null, InputOption::VALUE_REQUIRED, 'Sender address')
            ->addOption('to', null, InputOption::VALUE_REQUIRED, 'Recipient address')
            ->addOption('subject', null, InputOption::VALUE_REQUIRED, 'Subject of the email')
            ->addOption('content', null, InputOption::VALUE_REQUIRED, 'Text content of the email');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $from = trim((string) $input->getOption('from'));
        $to = trim((string) $input->getOption('to'));
        $subject = trim((string) $input->getOption('subject'));
        $content = trim((string) $input->getOption('content'));

        if ('' === $from || '' === $to || '' === $subject || '' === $content) {
            $io->error('Required options: --from, --to, --subject, --content');

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
                $io->error('No local user corresponds to the sender.');

                return Command::INVALID;
            }

            $this->userMailerService->send((int) $sender->getId(), $email);
            $io->success(sprintf('Email sent via the box of %s', $from));
        } catch (\Throwable $e) {
            $io->error('Email sending failed via the user box: '.$e->getMessage());

            return Command::FAILURE;
        }

        $user = $this->userRepository->findOneBy(['email' => $to]);
        if (null === $user) {
            $io->note('No local user for this recipient, sync ignored.');

            return Command::SUCCESS;
        }

        try {
            $this->messageBus->dispatch(new SyncEmailsMessage((int) $user->getId()));
            $io->success(sprintf('Sync triggered for user %s', $to));
        } catch (\Throwable $e) {
            $io->warning('Email sent but sync failed: '.$e->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
