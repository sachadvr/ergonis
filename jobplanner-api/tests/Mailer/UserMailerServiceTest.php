<?php

declare(strict_types=1);

namespace App\Tests\Mailer;

use App\Entity\User;
use App\Entity\UserMailboxSettings;
use App\Mailer\UserMailerService;
use App\Mailer\UserSmtpTransportFactory;
use App\Repository\UserMailboxSettingsRepository;
use App\Security\MailboxSecretEncryptor;
use App\Tests\Support\DoctrineTestHarness;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Email;

final class UserMailerServiceTest extends TestCase
{
    use DoctrineTestHarness;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createInMemoryEntityManager();
    }

    public function testSendThrowsWhenNoSettingsFound(): void
    {
        $service = new UserMailerService(
            new UserMailboxSettingsRepository($this->createManagerRegistry($this->entityManager)),
            new UserSmtpTransportFactory($this->createStub(LoggerInterface::class), new MailboxSecretEncryptor('test-secret')),
            $this->createStub(LoggerInterface::class),
            new MailboxSecretEncryptor('test-secret'),
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No mailbox settings found for user 42');

        $service->send(42, new Email());
    }

    public function testSendThrowsWhenSmtpSettingsAreInvalid(): void
    {
        $owner = (new User())->setEmail('candidate@example.com')->setPassword('secret');
        $this->entityManager->persist($owner);
        $this->entityManager->flush();
        $ownerId = $owner->getId();

        $settings = (new UserMailboxSettings())
            ->setOwner($owner)
            ->setSmtpHost('smtp.example.com')
            ->setSmtpUser('candidate@example.com')
            ->setSmtpPassword('');
        $this->entityManager->persist($settings);
        $this->entityManager->flush();

        $service = new UserMailerService(
            new UserMailboxSettingsRepository($this->createManagerRegistry($this->entityManager)),
            new UserSmtpTransportFactory($this->createStub(LoggerInterface::class), new MailboxSecretEncryptor('test-secret')),
            $this->createStub(LoggerInterface::class),
            new MailboxSecretEncryptor('test-secret'),
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid SMTP settings for user '.$owner->getId());

        $service->send($owner->getId(), new Email());
    }

    public function testSendCreatesTransportAndLogsEmail(): void
    {
        $owner = (new User())->setEmail('candidate@example.com')->setPassword('secret');
        $this->entityManager->persist($owner);
        $this->entityManager->flush();
        $ownerId = $owner->getId();

        $settings = (new UserMailboxSettings())
            ->setOwner($owner)
            ->setSmtpHost('mailpit')
            ->setSmtpPort(1025)
            ->setSmtpEncryption('none')
            ->setSmtpUser('candidate@example.com')
            ->setSmtpPassword('secret');
        $this->entityManager->persist($settings);
        $this->entityManager->flush();

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('info')->with(
            'Email sent via user SMTP',
            $this->callback(static function (array $context) use ($ownerId): bool {
                return $ownerId === $context['userId']
                    && 'sender@example.com' === $context['from']
                    && 'recipient@example.com' === $context['to']
                    && 'Hello' === $context['subject'];
            })
        );

        $service = new UserMailerService(
            new UserMailboxSettingsRepository($this->createManagerRegistry($this->entityManager)),
            new UserSmtpTransportFactory($logger, new MailboxSecretEncryptor('test-secret')),
            $logger,
            new MailboxSecretEncryptor('test-secret'),
        );

        $email = (new Email())
            ->from('sender@example.com')
            ->to('recipient@example.com')
            ->subject('Hello')
            ->text('Body');

        $service->send($ownerId, $email);
    }

    public function testSendSupportsOauthSettings(): void
    {
        $owner = (new User())->setEmail('candidate@example.com')->setPassword('secret');
        $this->entityManager->persist($owner);
        $this->entityManager->flush();
        $ownerId = $owner->getId();

        $settings = (new UserMailboxSettings())
            ->setOwner($owner)
            ->setOauthProvider('google')
            ->setImapUser('candidate@example.com')
            ->setSmtpHost('mailpit')
            ->setSmtpPort(1025)
            ->setSmtpEncryption('none')
            ->setSmtpUser('candidate@example.com')
            ->setAccessToken('token');
        $this->entityManager->persist($settings);
        $this->entityManager->flush();

        $service = new UserMailerService(
            new UserMailboxSettingsRepository($this->createManagerRegistry($this->entityManager)),
            new UserSmtpTransportFactory($this->createStub(LoggerInterface::class), new MailboxSecretEncryptor('test-secret')),
            $this->createStub(LoggerInterface::class),
            new MailboxSecretEncryptor('test-secret'),
        );

        $service->send($ownerId, (new Email())->from('sender@example.com')->to('recipient@example.com')->subject('Hello')->text('Body'));

        $this->assertNotNull($ownerId);
    }
}
