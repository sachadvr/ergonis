<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\User;
use App\Entity\UserMailboxSettings;
use PHPUnit\Framework\TestCase;

final class UserMailboxSettingsTest extends TestCase
{
    public function testDefaultsAndImapPath(): void
    {
        $settings = new UserMailboxSettings();

        $settings->setImapHost('mailpit');
        $settings->setImapUser('user@example.com');

        $this->assertSame(993, $settings->getImapPort());
        $this->assertSame('ssl', $settings->getImapEncryption());
        $this->assertSame('INBOX', $settings->getImapFolder());
        $this->assertSame('{mailpit:993/pop3/ssl}INBOX', $settings->getImapPath());
        $this->assertInstanceOf(\DateTimeImmutable::class, $settings->getCreatedAt());
    }

    public function testSettersAndOauthHelpersWork(): void
    {
        $owner = (new User())->setEmail('candidate@example.com')->setPassword('secret');

        $settings = (new UserMailboxSettings())
            ->setOwner($owner)
            ->setImapHost('imap.example.com')
            ->setImapPort(143)
            ->setImapEncryption('tls')
            ->setImapUser('user@example.com')
            ->setImapPassword('imap-secret')
            ->setImapFolder('Archive')
            ->setIsActive(false)
            ->setSmtpHost('smtp.example.com')
            ->setSmtpPort(587)
            ->setSmtpEncryption('tls')
            ->setSmtpUser('user@example.com')
            ->setSmtpPassword('smtp-secret')
            ->setOauthProvider('google')
            ->setAccessToken('access')
            ->setRefreshToken('refresh')
            ->setTokenExpiresAt(new \DateTimeImmutable('+1 hour'))
            ->setLastSyncedAt(new \DateTimeImmutable('2026-04-15 10:00:00'));

        $this->assertSame($owner, $settings->getOwner());
        $this->assertSame('imap.example.com', $settings->getImapHost());
        $this->assertSame(143, $settings->getImapPort());
        $this->assertSame('tls', $settings->getImapEncryption());
        $this->assertSame('user@example.com', $settings->getImapUser());
        $this->assertSame('imap-secret', $settings->getImapPassword());
        $this->assertSame('Archive', $settings->getImapFolder());
        $this->assertFalse($settings->isActive());
        $this->assertSame('smtp.example.com', $settings->getSmtpHost());
        $this->assertSame(587, $settings->getSmtpPort());
        $this->assertSame('tls', $settings->getSmtpEncryption());
        $this->assertSame('user@example.com', $settings->getSmtpUser());
        $this->assertSame('smtp-secret', $settings->getSmtpPassword());
        $this->assertSame('google', $settings->getOauthProvider());
        $this->assertSame('access', $settings->getAccessToken());
        $this->assertSame('refresh', $settings->getRefreshToken());
        $this->assertTrue($settings->isOauth());
        $this->assertInstanceOf(\DateTimeImmutable::class, $settings->getUpdatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $settings->getLastSyncedAt());
    }
}
