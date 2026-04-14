<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
    ],
    normalizationContext: ['groups' => ['ai_log:read']],
    order: ['createdAt' => 'DESC'],
)]
#[ORM\Entity]
#[ORM\Table(name: 'ai_generation_logs')]
class AiGenerationLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['ai_log:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    #[ORM\Column(type: 'string', length: 50)]
    #[Groups(['ai_log:read'])]
    private string $type;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['ai_log:read'])]
    private ?string $prompt = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['ai_log:read'])]
    private ?int $tokensUsed = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['ai_log:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

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

    public function getPrompt(): ?string
    {
        return $this->prompt;
    }

    public function setPrompt(?string $prompt): self
    {
        $this->prompt = $prompt;

        return $this;
    }

    public function getTokensUsed(): ?int
    {
        return $this->tokensUsed;
    }

    public function setTokensUsed(?int $tokensUsed): self
    {
        $this->tokensUsed = $tokensUsed;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
