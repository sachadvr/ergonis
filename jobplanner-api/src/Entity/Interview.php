<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(),
        new Put(),
        new Delete(),
    ],
    normalizationContext: ['groups' => ['interview:read']],
    denormalizationContext: ['groups' => ['interview:write']],
    order: ['scheduledAt' => 'ASC'],
)]
#[ORM\Entity]
#[ORM\Table(name: 'interviews')]
class Interview
{
    public const TYPE_VIDEO = 'visio';
    public const TYPE_PHONE = 'tel';
    public const TYPE_ON_SITE = 'presentiel';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['interview:read', 'interview:write', 'application:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Application::class, inversedBy: 'interviews')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['interview:read', 'interview:write'])]
    private Application $application;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Assert\NotNull]
    #[Groups(['interview:read', 'interview:write', 'application:read'])]
    private \DateTimeImmutable $scheduledAt;

    #[ORM\Column(type: 'string', length: 50)]
    #[Groups(['interview:read', 'interview:write', 'application:read'])]
    private string $type = self::TYPE_VIDEO;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['interview:read', 'interview:write', 'application:read'])]
    private ?string $notes = null;

    #[ORM\Column(type: 'string', length: 512, nullable: true)]
    #[Groups(['interview:read', 'interview:write', 'application:read'])]
    private ?string $locationOrLink = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['interview:read', 'interview:write'])]
    private ?string $contactName = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $reminderSent = false;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $reminderSentAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getApplication(): Application
    {
        return $this->application;
    }

    public function setApplication(Application $application): self
    {
        $this->application = $application;

        return $this;
    }

    public function getScheduledAt(): \DateTimeImmutable
    {
        return $this->scheduledAt;
    }

    public function setScheduledAt(\DateTimeImmutable $scheduledAt): self
    {
        $this->scheduledAt = $scheduledAt;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;

        return $this;
    }

    public function getLocationOrLink(): ?string
    {
        return $this->locationOrLink;
    }

    public function setLocationOrLink(?string $locationOrLink): self
    {
        $this->locationOrLink = $locationOrLink;

        return $this;
    }

    public function getContactName(): ?string
    {
        return $this->contactName;
    }

    public function setContactName(?string $contactName): self
    {
        $this->contactName = $contactName;

        return $this;
    }

    public function isReminderSent(): bool
    {
        return $this->reminderSent;
    }

    public function setReminderSent(bool $reminderSent): self
    {
        $this->reminderSent = $reminderSent;
        $this->reminderSentAt = $reminderSent ? new \DateTimeImmutable() : null;

        return $this;
    }

    public function getReminderSentAt(): ?\DateTimeImmutable
    {
        return $this->reminderSentAt;
    }
}
