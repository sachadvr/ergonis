<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\State\OwnedEntityInterface;
use App\State\OwnedEntityProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(processor: OwnedEntityProcessor::class),
        new Put(),
        new Patch(),
        new Delete(),
    ],
    normalizationContext: ['groups' => ['application:read']],
    denormalizationContext: ['groups' => ['application:write']],
    order: ['appliedAt' => 'DESC', 'createdAt' => 'DESC'],
)]
#[ORM\Entity]
#[ORM\Table(name: 'applications')]
class Application implements OwnedEntityInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['application:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: JobOffer::class, inversedBy: 'applications')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['application:read', 'application:write'])]
    private JobOffer $jobOffer;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $owner = null;

    #[ORM\Column(type: 'string', enumType: ApplicationStatus::class)]
    #[Groups(['application:read', 'application:write'])]
    private ApplicationStatus $status = ApplicationStatus::WISHLIST;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Groups(['application:read', 'application:write'])]
    private int $pipelinePosition = 0;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['application:read', 'application:write'])]
    private ?\DateTimeImmutable $appliedAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['application:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['application:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['application:read'])]
    private ?\DateTimeImmutable $lastActivityAt = null;

    /**
     * @var Collection<int, ApplicationHistory>
     */
    #[ORM\OneToMany(targetEntity: ApplicationHistory::class, mappedBy: 'application', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    #[Groups(['application:read'])]
    private Collection $history;

    /**
     * @var Collection<int, RecruiterEmail>
     */
    #[ApiProperty(readableLink: true)]
    #[ORM\OneToMany(targetEntity: RecruiterEmail::class, mappedBy: 'application', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['receivedAt' => 'DESC'])]
    #[Groups(['application:read'])]
    private Collection $recruiterEmails;

    /**
     * @var Collection<int, ScheduledFollowUp>
     */
    #[ORM\OneToMany(targetEntity: ScheduledFollowUp::class, mappedBy: 'application', cascade: ['persist', 'remove'])]
    #[Groups(['application:read'])]
    private Collection $scheduledFollowUps;

    #[ORM\Column(type: 'string', length: 32, nullable: true)]
    #[Groups(['application:read'])]
    private ?string $cvFitAnalysisStatus = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['application:read'])]
    private ?array $cvFitAnalysisResult = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['application:read'])]
    private ?\DateTimeImmutable $cvFitAnalysisRequestedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['application:read'])]
    private ?\DateTimeImmutable $cvFitAnalysisCompletedAt = null;

    /**
     * @var Collection<int, Interview>
     */
    #[ORM\OneToMany(targetEntity: Interview::class, mappedBy: 'application', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['scheduledAt' => 'ASC'])]
    #[Groups(['application:read'])]
    private Collection $interviews;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->history = new ArrayCollection();
        $this->recruiterEmails = new ArrayCollection();
        $this->scheduledFollowUps = new ArrayCollection();
        $this->interviews = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getJobOffer(): JobOffer
    {
        return $this->jobOffer;
    }

    public function setJobOffer(JobOffer $jobOffer): self
    {
        $this->jobOffer = $jobOffer;

        return $this;
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

    public function getStatus(): ApplicationStatus
    {
        return $this->status;
    }

    public function setStatus(ApplicationStatus $status): self
    {
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getPipelinePosition(): int
    {
        return $this->pipelinePosition;
    }

    public function setPipelinePosition(int $pipelinePosition): self
    {
        $this->pipelinePosition = $pipelinePosition;

        return $this;
    }

    public function getAppliedAt(): ?\DateTimeImmutable
    {
        return $this->appliedAt;
    }

    public function setAppliedAt(?\DateTimeImmutable $appliedAt): self
    {
        $this->appliedAt = $appliedAt;

        return $this;
    }

    #[Groups(['application:read'])]
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    #[Groups(['application:read'])]
    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getLastActivityAt(): ?\DateTimeImmutable
    {
        return $this->lastActivityAt;
    }

    public function setLastActivityAt(?\DateTimeImmutable $lastActivityAt): self
    {
        $this->lastActivityAt = $lastActivityAt;

        return $this;
    }

    /**
     * @return Collection<int, ApplicationHistory>
     */
    public function getHistory(): Collection
    {
        return $this->history;
    }

    /**
     * @return Collection<int, RecruiterEmail>
     */
    public function getRecruiterEmails(): Collection
    {
        return $this->recruiterEmails;
    }

    /**
     * @return Collection<int, ScheduledFollowUp>
     */
    public function getScheduledFollowUps(): Collection
    {
        return $this->scheduledFollowUps;
    }

    public function getCvFitAnalysisStatus(): ?string
    {
        return $this->cvFitAnalysisStatus;
    }

    public function setCvFitAnalysisStatus(?string $cvFitAnalysisStatus): self
    {
        $this->cvFitAnalysisStatus = $cvFitAnalysisStatus;

        return $this;
    }

    public function getCvFitAnalysisResult(): ?array
    {
        return $this->cvFitAnalysisResult;
    }

    public function setCvFitAnalysisResult(?array $cvFitAnalysisResult): self
    {
        $this->cvFitAnalysisResult = $cvFitAnalysisResult;

        return $this;
    }

    public function getCvFitAnalysisRequestedAt(): ?\DateTimeImmutable
    {
        return $this->cvFitAnalysisRequestedAt;
    }

    public function setCvFitAnalysisRequestedAt(?\DateTimeImmutable $cvFitAnalysisRequestedAt): self
    {
        $this->cvFitAnalysisRequestedAt = $cvFitAnalysisRequestedAt;

        return $this;
    }

    public function getCvFitAnalysisCompletedAt(): ?\DateTimeImmutable
    {
        return $this->cvFitAnalysisCompletedAt;
    }

    public function setCvFitAnalysisCompletedAt(?\DateTimeImmutable $cvFitAnalysisCompletedAt): self
    {
        $this->cvFitAnalysisCompletedAt = $cvFitAnalysisCompletedAt;

        return $this;
    }

    /**
     * @return Collection<int, Interview>
     */
    public function getInterviews(): Collection
    {
        return $this->interviews;
    }

    public function clearInterviews(): self
    {
        $this->interviews->clear();

        return $this;
    }
}
