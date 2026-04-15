<?php

declare(strict_types=1);

namespace App\Service\Mail\Client;

use App\Service\Mail\Client\Abstract\AbstractOAuthClient;

final class MicrosoftOAuthClient extends AbstractOAuthClient
{
    protected function getOauthProvider(): string
    {
        return 'microsoft';
    }

    protected function getImapHost(string $email): string
    {
        return $this->isConsumerOutlookMailbox($email)
            ? 'imap-mail.outlook.com'
            : 'outlook.office365.com';
    }

    protected function getSmtpHost(string $email): string
    {
        return $this->isConsumerOutlookMailbox($email)
            ? 'smtp-mail.outlook.com'
            : 'smtp.office365.com';
    }

    private function isConsumerOutlookMailbox(string $email): bool
    {
        return (bool) preg_match('/@(outlook|hotmail|live|msn)\.[a-z.]+$/i', strtolower($email));
    }
}
