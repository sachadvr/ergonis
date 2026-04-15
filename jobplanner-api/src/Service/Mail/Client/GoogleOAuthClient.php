<?php

declare(strict_types=1);

namespace App\Service\Mail\Client;

use App\Service\Mail\Client\Abstract\AbstractOAuthClient;

final class GoogleOAuthClient extends AbstractOAuthClient
{
    protected function getOauthProvider(): string
    {
        return 'google';
    }

    protected function getImapHost(string $email): string
    {
        return 'imap.gmail.com';
    }

    protected function getSmtpHost(string $email): string
    {
        return 'smtp.gmail.com';
    }

    protected function getSmtpPort(): int
    {
        return 465;
    }

    protected function getSmtpEncryption(): string
    {
        return 'ssl';
    }
}
