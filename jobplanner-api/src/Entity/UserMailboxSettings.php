<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\State\UserMailboxSettingsProcessor;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(processor: UserMailboxSettingsProcessor::class),
        new Put(processor: UserMailboxSettingsProcessor::class),
        new Patch(
            processor: UserMailboxSettingsProcessor::class,
            inputFormats: ['json' => ['application/merge-patch+json']],
        ),
    ],
    normalizationContext: ['groups' => ['mailbox_settings:read']],
    denormalizationContext: ['groups' => ['mailbox_settings:write']],
    order: ['id' => 'DESC'],
)]
#[ORM\Entity]
#[ORM\Table(name: 'user_mailbox_settings')]
class UserMailboxSettings
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['mailbox_settings:read'])]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, unique: true)]
    private ?User $owner = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Groups(['mailbox_settings:read', 'mailbox_settings:write'])]
    private ?string $imapHost = null;

    #[ORM\Column(type: 'integer', options: ['default' => 993])]
    #[Assert\Positive(groups: ['mailbox_settings:write'])]
    #[Assert\LessThanOrEqual(65535)]
    #[Groups(['mailbox_settings:read', 'mailbox_settings:write'])]
    private int $imapPort = 993;

    #[ORM\Column(type: 'string', length: 10, options: ['default' => 'ssl'])]
    #[Assert\Choice(choices: ['ssl', 'tls', 'none'], groups: ['mailbox_settings:write'])]
    #[Groups(['mailbox_settings:read', 'mailbox_settings:write'])]
    private string $imapEncryption = 'ssl';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Groups(['mailbox_settings:read', 'mailbox_settings:write'])]
    private ?string $imapUser = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['mailbox_settings:write'])]
    private ?string $imapPassword = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Groups(['mailbox_settings:read', 'mailbox_settings:write'])]
    private ?string $imapFolder = 'INBOX';

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['mailbox_settings:read', 'mailbox_settings:write'])]
    private bool $isActive = true;

    #[ORM\Column(type: 'string', length: 255, options: ['default' => ''])]
    #[Groups(['mailbox_settings:read', 'mailbox_settings:write'])]
    private string $smtpHost = '';

    #[ORM\Column(type: 'integer', options: ['default' => 587])]
    #[Assert\Positive]
    #[Assert\LessThanOrEqual(65535)]
    #[Groups(['mailbox_settings:read', 'mailbox_settings:write'])]
    private int $smtpPort = 587;

    #[ORM\Column(type: 'string', length: 10, options: ['default' => 'tls'])]
    #[Assert\Choice(choices: ['ssl', 'tls', 'none'])]
    #[Groups(['mailbox_settings:read', 'mailbox_settings:write'])]
    private string $smtpEncryption = 'tls';

    #[ORM\Column(type: 'string', length: 255, options: ['default' => ''])]
    #[Groups(['mailbox_settings:read', 'mailbox_settings:write'])]
    private string $smtpUser = '';

    #[ORM\Column(type: 'string', length: 255, options: ['default' => ''])]
    #[Groups(['mailbox_settings:write'])]
    private string $smtpPassword = '';

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    #[Groups(['mailbox_settings:read'])]
    private ?string $oauthProvider = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $accessToken = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $refreshToken = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['mailbox_settings:read'])]
    private ?\DateTimeImmutable $tokenExpiresAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastSyncedAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(User $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

    public function getImapHost(): string
    {
        return $this->imapHost;
    }

    public function setImapHost(string $imapHost): self
    {
        $this->imapHost = $imapHost;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getImapPort(): int
    {
        return $this->imapPort;
    }

    public function setImapPort(int $imapPort): self
    {
        $this->imapPort = $imapPort;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getImapEncryption(): string
    {
        return $this->imapEncryption;
    }

    public function setImapEncryption(string $imapEncryption): self
    {
        $this->imapEncryption = $imapEncryption;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getImapUser(): string
    {
        return $this->imapUser;
    }

    public function setImapUser(string $imapUser): self
    {
        $this->imapUser = $imapUser;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getImapPassword(): string
    {
        return $this->imapPassword ?? '';
    }

    public function setImapPassword(string $imapPassword): self
    {
        $this->imapPassword = $imapPassword;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getImapFolder(): ?string
    {
        return $this->imapFolder ?? 'INBOX';
    }

    public function setImapFolder(?string $imapFolder): self
    {
        $this->imapFolder = $imapFolder;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getSmtpHost(): string
    {
        return $this->smtpHost;
    }

    public function setSmtpHost(string $smtpHost): self
    {
        $this->smtpHost = $smtpHost;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getSmtpPort(): int
    {
        return $this->smtpPort;
    }

    public function setSmtpPort(int $smtpPort): self
    {
        $this->smtpPort = $smtpPort;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getSmtpEncryption(): string
    {
        return $this->smtpEncryption;
    }

    public function setSmtpEncryption(string $smtpEncryption): self
    {
        $this->smtpEncryption = $smtpEncryption;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getSmtpUser(): string
    {
        return $this->smtpUser;
    }

    public function setSmtpUser(string $smtpUser): self
    {
        $this->smtpUser = $smtpUser;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getSmtpPassword(): string
    {
        return $this->smtpPassword;
    }

    public function setSmtpPassword(string $smtpPassword): self
    {
        $this->smtpPassword = $smtpPassword;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getOauthProvider(): ?string
    {
        return $this->oauthProvider;
    }

    public function setOauthProvider(?string $oauthProvider): self
    {
        $this->oauthProvider = $oauthProvider;

        return $this;
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function setAccessToken(?string $accessToken): self
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(?string $refreshToken): self
    {
        $this->refreshToken = $refreshToken;

        return $this;
    }

    public function getTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->tokenExpiresAt;
    }

    public function setTokenExpiresAt(?\DateTimeImmutable $tokenExpiresAt): self
    {
        $this->tokenExpiresAt = $tokenExpiresAt;

        return $this;
    }

    public function getLastSyncedAt(): ?\DateTimeImmutable
    {
        return $this->lastSyncedAt;
    }

    public function setLastSyncedAt(?\DateTimeImmutable $lastSyncedAt): self
    {
        $this->lastSyncedAt = $lastSyncedAt;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function isOauth(): bool
    {
        return null !== $this->oauthProvider;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Build IMAP path for php-imap Mailbox constructor.
     * Format: {host:port/imap/ssl}INBOX
     * Note: XOAUTH2 is handled via password parameter, not path flag.
     */
    public function getImapPath(): string
    {
        $isPop3 = 1110 === $this->imapPort || str_contains(strtolower($this->imapHost), 'mailpit');

        $flags = match ($this->imapEncryption) {
            'ssl' => $isPop3 ? '/pop3/ssl' : '/imap/ssl',
            'tls' => $isPop3 ? '/pop3/tls' : '/imap/tls',
            default => $isPop3 ? '/pop3/notls' : '/imap',
        };

        $folder = $this->getImapFolder();

        return sprintf('{%s:%d%s}%s', $this->imapHost, $this->imapPort, $flags, $folder);
    }
}
