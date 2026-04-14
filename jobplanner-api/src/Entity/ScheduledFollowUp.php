<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Patch(),
    ],
    normalizationContext: ['groups' => ['scheduled_follow_up:read']],
    denormalizationContext: ['groups' => ['scheduled_follow_up:write']],
    order: ['scheduledAt' => 'ASC'],
)]
#[ORM\Entity]
#[ORM\Table(name: 'scheduled_follow_ups')]
class ScheduledFollowUp
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_CANCELLED = 'cancelled';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['scheduled_follow_up:read', 'application:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Application::class, inversedBy: 'scheduledFollowUps')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['scheduled_follow_up:read'])]
    private Application $application;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['scheduled_follow_up:read', 'application:read'])]
    private \DateTimeImmutable $scheduledAt;

    #[ORM\Column(type: 'string', length: 20)]
    #[Groups(['scheduled_follow_up:read', 'application:read'])]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['scheduled_follow_up:read'])]
    private ?\DateTimeImmutable $cancelledAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['scheduled_follow_up:read', 'application:read'])]
    private ?string $generatedContent = null;

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

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getCancelledAt(): ?\DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    public function setCancelledAt(?\DateTimeImmutable $cancelledAt): self
    {
        $this->cancelledAt = $cancelledAt;

        return $this;
    }

    public function getGeneratedContent(): ?string
    {
        return $this->generatedContent;
    }

    public function setGeneratedContent(?string $generatedContent): self
    {
        $this->generatedContent = $generatedContent;

        return $this;
    }
}
