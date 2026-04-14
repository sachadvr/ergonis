<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\State\OwnedEntityInterface;
use App\State\OwnedEntityProcessor;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(processor: OwnedEntityProcessor::class),
        new Put(),
        new Delete(),
    ],
    normalizationContext: ['groups' => ['follow_up_rule:read']],
    denormalizationContext: ['groups' => ['follow_up_rule:write']],
)]
#[ORM\Entity]
#[ORM\Table(name: 'follow_up_rules')]
class FollowUpRule implements OwnedEntityInterface
{
    public const TEMPLATE_FOLLOW_UP = 'follow_up';
    public const TEMPLATE_THANK_YOU = 'thank_you';
    public const TEMPLATE_SPONTANEOUS = 'spontaneous';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['follow_up_rule:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $owner = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\Positive]
    #[Groups(['follow_up_rule:read', 'follow_up_rule:write'])]
    private int $daysWithoutReply = 7;

    #[ORM\Column(type: 'string', length: 50)]
    #[Groups(['follow_up_rule:read', 'follow_up_rule:write'])]
    private string $templateType = self::TEMPLATE_FOLLOW_UP;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['follow_up_rule:read', 'follow_up_rule:write'])]
    private bool $enabled = true;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

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

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    public function getDaysWithoutReply(): int
    {
        return $this->daysWithoutReply;
    }

    public function setDaysWithoutReply(int $daysWithoutReply): self
    {
        $this->daysWithoutReply = $daysWithoutReply;

        return $this;
    }

    public function getTemplateType(): string
    {
        return $this->templateType;
    }

    public function setTemplateType(string $templateType): self
    {
        $this->templateType = $templateType;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
