<?php

declare(strict_types=1);

namespace App\Mailer;

use App\Entity\UserMailboxSettings;
use App\Repository\UserMailboxSettingsRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final readonly class UserMailerService
{
    public function __construct(
        private UserMailboxSettingsRepository $mailboxSettingsRepository,
        private UserSmtpTransportFactory $transportFactory,
        private LoggerInterface $logger,
    ) {
    }

    public function send(int $userId, Email $email): void
    {
        $settings = $this->mailboxSettingsRepository->findByUserId($userId);
        if (null === $settings) {
            throw new \RuntimeException('No mailbox settings found for user '.$userId);
        }

        if (!$this->hasValidSmtpSettings($settings)) {
            throw new \RuntimeException('Invalid SMTP settings for user '.$userId);
        }

        $transport = $this->transportFactory->createTransport($settings);
        $transport->send($email);

        $this->logger->info('Email sent via user SMTP', [
            'userId' => $userId,
            'from' => ($email->getFrom()[0] ?? null)?->toString() ?? 'unknown',
            'to' => implode(',', array_map(static fn (Address $address) => $address->toString(), $email->getTo())),
            'subject' => $email->getSubject(),
        ]);
    }

    private function hasValidSmtpSettings(UserMailboxSettings $settings): bool
    {
        if (null !== $settings->getOauthProvider()) {
            return '' !== $settings->getSmtpHost()
                && '' !== $settings->getSmtpUser()
                && '' !== trim((string) $settings->getAccessToken());
        }

        return '' !== $settings->getSmtpHost()
            && '' !== $settings->getSmtpUser()
            && '' !== $settings->getSmtpPassword();
    }
}
