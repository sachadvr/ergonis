<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\State\RecruiterEmailProcessor;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(processor: RecruiterEmailProcessor::class, denormalizationContext: ['groups' => ['recruiter_email:write']]),
        new Patch(denormalizationContext: ['groups' => ['recruiter_email:write']]),
    ],
    normalizationContext: ['groups' => ['recruiter_email:read']],
    denormalizationContext: ['groups' => ['recruiter_email:write']],
    order: ['receivedAt' => 'DESC'],
)]
#[ApiFilter(SearchFilter::class, properties: ['application' => 'exact'])]
#[ORM\Entity]
#[ORM\Table(name: 'recruiter_emails', uniqueConstraints: [new ORM\UniqueConstraint(name: 'uniq_recruiter_email_message_owner', columns: ['message_id', 'owner_id'])])]
class RecruiterEmail
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['recruiter_email:read', 'recruiter_email:write', 'application:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Application::class, inversedBy: 'recruiterEmails')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['recruiter_email:write'])]
    private Application $application;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $owner = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['recruiter_email:read', 'recruiter_email:write', 'application:read'])]
    #[Assert\NotBlank(groups: ['recruiter_email:write'])]
    private string $sender;

    #[ORM\Column(type: 'string', length: 1024)]
    #[Groups(['recruiter_email:read', 'recruiter_email:write', 'application:read'])]
    #[Assert\NotBlank(groups: ['recruiter_email:write'])]
    private string $subject;

    #[ORM\Column(type: 'text')]
    #[Groups(['recruiter_email:read', 'recruiter_email:write', 'application:read'])]
    #[Assert\NotBlank(groups: ['recruiter_email:write'])]
    private string $body;

    #[ORM\Column(type: 'text')]
    private string $messageId = '';

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['recruiter_email:read', 'recruiter_email:write', 'application:read'])]
    private \DateTimeImmutable $receivedAt;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['recruiter_email:read', 'application:read'])]
    private ?string $aiSummary = null;

    #[ORM\Column(type: 'string', length: 20)]
    #[Groups(['recruiter_email:read', 'recruiter_email:write', 'application:read'])]
    private string $direction = 'INCOMING';

    #[ORM\Column(type: 'boolean')]
    #[Groups(['recruiter_email:read', 'recruiter_email:write', 'application:read'])]
    private bool $isFavourite = false;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['recruiter_email:read', 'recruiter_email:write', 'application:read'])]
    private bool $isDeleted = false;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['recruiter_email:read', 'recruiter_email:write', 'application:read'])]
    private bool $isDraft = false;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['recruiter_email:read', 'recruiter_email:write', 'application:read'])]
    private bool $isSeen = false;

    #[ORM\Column(type: 'json')]
    #[Groups(['recruiter_email:read', 'recruiter_email:write', 'application:read'])]
    private array $labels = [];

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

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

    public function getSender(): string
    {
        return $this->sender;
    }

    public function setSender(string $sender): self
    {
        $this->sender = $sender;

        return $this;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function getMessageId(): string
    {
        return $this->messageId;
    }

    public function setMessageId(string $messageId): self
    {
        $this->messageId = $messageId;

        return $this;
    }

    public function getReceivedAt(): \DateTimeImmutable
    {
        return $this->receivedAt;
    }

    public function setReceivedAt(\DateTimeImmutable $receivedAt): self
    {
        $this->receivedAt = $receivedAt;

        return $this;
    }

    public function getAiSummary(): ?string
    {
        return $this->aiSummary;
    }

    public function setAiSummary(?string $aiSummary): self
    {
        $this->aiSummary = $aiSummary;

        return $this;
    }

    public function getDirection(): string
    {
        return $this->direction;
    }

    public function setDirection(string $direction): self
    {
        $this->direction = $direction;

        return $this;
    }

    public function isFavourite(): bool
    {
        return $this->isFavourite;
    }

    public function setIsFavourite(bool $isFavourite): self
    {
        $this->isFavourite = $isFavourite;

        return $this;
    }

    public function isDeleted(): bool
    {
        return $this->isDeleted;
    }

    public function setIsDeleted(bool $isDeleted): self
    {
        $this->isDeleted = $isDeleted;

        return $this;
    }

    public function isDraft(): bool
    {
        return $this->isDraft;
    }

    public function setIsDraft(bool $isDraft): self
    {
        $this->isDraft = $isDraft;

        return $this;
    }

    public function isSeen(): bool
    {
        return $this->isSeen;
    }

    public function getIsSeen(): bool
    {
        return $this->isSeen;
    }

    public function setIsSeen(bool $isSeen): self
    {
        $this->isSeen = $isSeen;

        return $this;
    }

    public function getLabels(): array
    {
        return $this->labels;
    }

    public function setLabels(array $labels): self
    {
        $this->labels = $labels;

        return $this;
    }
}
