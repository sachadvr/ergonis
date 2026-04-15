<?php

declare(strict_types=1);

namespace App\Service\Mail\Provider;

use App\Entity\UserMailboxSettings;
use App\Service\Mail\EmailMessageMapper;
use App\Service\Mail\TokenRefreshService;
use Psr\Log\LoggerInterface;

final class MicrosoftOAuthMailProvider extends AbstractOAuthMailProvider
{
    public function __construct(
        UserMailboxSettings $settings,
        TokenRefreshService $tokenRefreshService,
        EmailMessageMapper $messageMapper,
        LoggerInterface $logger,
    ) {
        parent::__construct($settings, $tokenRefreshService, $messageMapper, $logger);
    }

    protected function resolveHost(): string
    {
        $email = strtolower(trim($this->settings->getImapUser()));
        if ($this->isConsumerOutlookMailbox($email)) {
            return 'imap-mail.outlook.com';
        }

        return '' !== $this->settings->getImapHost() ? $this->settings->getImapHost() : 'outlook.office365.com';
    }

    private function isConsumerOutlookMailbox(string $email): bool
    {
        return (bool) preg_match('/@(outlook|hotmail|live|msn)\.[a-z.]+$/i', $email);
    }
}
