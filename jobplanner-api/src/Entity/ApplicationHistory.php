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
    normalizationContext: ['groups' => ['application_history:read']],
    denormalizationContext: ['groups' => ['application_history:write']],
    order: ['createdAt' => 'DESC'],
)]
#[ORM\Entity]
#[ORM\Table(name: 'application_history')]
class ApplicationHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['application_history:read', 'application:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Application::class, inversedBy: 'history')]
    #[ORM\JoinColumn(nullable: false)]
    private Application $application;

    #[ORM\Column(type: 'string', length: 50, enumType: ApplicationHistoryActionType::class)]
    #[Groups(['application_history:read', 'application:read'])]
    private ApplicationHistoryActionType $actionType;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['application_history:read', 'application:read'])]
    private ?string $description = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['application_history:read', 'application:read', 'application_history:write'])]
    private bool $isSeen = false;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['application_history:read', 'application:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

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

    public function getActionType(): ApplicationHistoryActionType
    {
        return $this->actionType;
    }

    public function setActionType(ApplicationHistoryActionType $actionType): self
    {
        $this->actionType = $actionType;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isSeen(): bool
    {
        return $this->isSeen;
    }

    public function setIsSeen(bool $isSeen): self
    {
        $this->isSeen = $isSeen;

        return $this;
    }
}
