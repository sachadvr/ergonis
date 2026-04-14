<?php

declare(strict_types=1);

namespace App\Entity;

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
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(processor: OwnedEntityProcessor::class),
        new Put(processor: OwnedEntityProcessor::class),
        new Patch(processor: OwnedEntityProcessor::class),
        new Delete(),
    ],
    normalizationContext: ['groups' => ['job_offer:read']],
    denormalizationContext: ['groups' => ['job_offer:write']],
    order: ['createdAt' => 'DESC'],
)]
#[ORM\Entity]
#[ORM\Table(name: 'job_offers')]
class JobOffer implements OwnedEntityInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['job_offer:read', 'application:read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Groups(['job_offer:read', 'job_offer:write', 'application:read'])]
    private string $title;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Groups(['job_offer:read', 'job_offer:write', 'application:read'])]
    private string $company;

    #[ORM\Column(type: 'string', length: 2048)]
    #[Groups(['job_offer:read', 'job_offer:write', 'application:read'])]
    private string $url = '';

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['job_offer:read', 'job_offer:write', 'application:read'])]
    private ?string $rawContent = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['job_offer:read', 'job_offer:write', 'application:read'])]
    private ?string $location = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['job_offer:read', 'job_offer:write', 'application:read'])]
    private ?string $notes = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['job_offer:read', 'job_offer:write', 'application:read'])]
    private ?string $interviewPrep = null;

    #[ORM\Column(type: 'string', length: 2048, nullable: true)]
    #[Groups(['job_offer:read', 'job_offer:write', 'application:read'])]
    private ?string $sourceUrl = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['job_offer:read', 'job_offer:write', 'application:read'])]
    private ?string $recruiterContactEmail = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['job_offer:read', 'job_offer:write', 'application:read'])]
    private ?string $jobSummary = null;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, nullable: true)]
    #[Groups(['job_offer:read', 'job_offer:write', 'application:read'])]
    private ?string $salaryMin = null;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, nullable: true)]
    #[Groups(['job_offer:read', 'job_offer:write', 'application:read'])]
    private ?string $salaryMax = null;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    #[Groups(['job_offer:read', 'job_offer:write', 'application:read'])]
    private ?string $salaryCurrency = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Groups(['job_offer:read', 'job_offer:write', 'application:read'])]
    private ?string $contractType = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Groups(['job_offer:read', 'job_offer:write', 'application:read'])]
    private ?string $remotePolicy = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['job_offer:read', 'job_offer:write', 'application:read'])]
    private ?array $details = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $owner = null;

    /**
     * @var Collection<int, Application>
     */
    #[ORM\OneToMany(targetEntity: Application::class, mappedBy: 'jobOffer', cascade: ['persist', 'remove'])]
    private Collection $applications;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->applications = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getCompany(): string
    {
        return $this->company;
    }

    public function setCompany(string $company): self
    {
        $this->company = $company;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getRawContent(): ?string
    {
        return $this->rawContent;
    }

    public function setRawContent(?string $rawContent): self
    {
        $this->rawContent = $rawContent;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): self
    {
        $this->location = $location;

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

    #[Groups(['job_offer:read', 'application:read'])]
    public function getInterviewPrep(): ?string
    {
        return $this->interviewPrep;
    }

    public function setInterviewPrep(?string $interviewPrep): self
    {
        $this->interviewPrep = $interviewPrep;

        return $this;
    }

    public function getSourceUrl(): ?string
    {
        return $this->sourceUrl;
    }

    public function setSourceUrl(?string $sourceUrl): self
    {
        $this->sourceUrl = $sourceUrl;

        return $this;
    }

    public function getRecruiterContactEmail(): ?string
    {
        return $this->recruiterContactEmail;
    }

    public function setRecruiterContactEmail(?string $recruiterContactEmail): self
    {
        $this->recruiterContactEmail = $recruiterContactEmail;

        return $this;
    }

    public function getJobSummary(): ?string
    {
        return $this->jobSummary;
    }

    public function setJobSummary(?string $jobSummary): self
    {
        $this->jobSummary = $jobSummary;

        return $this;
    }

    public function getSalaryMin(): ?string
    {
        return $this->salaryMin;
    }

    public function setSalaryMin(?string $salaryMin): self
    {
        $this->salaryMin = $salaryMin;

        return $this;
    }

    public function getSalaryMax(): ?string
    {
        return $this->salaryMax;
    }

    public function setSalaryMax(?string $salaryMax): self
    {
        $this->salaryMax = $salaryMax;

        return $this;
    }

    public function getSalaryCurrency(): ?string
    {
        return $this->salaryCurrency;
    }

    public function setSalaryCurrency(?string $salaryCurrency): self
    {
        $this->salaryCurrency = $salaryCurrency;

        return $this;
    }

    public function getContractType(): ?string
    {
        return $this->contractType;
    }

    public function setContractType(?string $contractType): self
    {
        $this->contractType = $contractType;

        return $this;
    }

    public function getRemotePolicy(): ?string
    {
        return $this->remotePolicy;
    }

    public function setRemotePolicy(?string $remotePolicy): self
    {
        $this->remotePolicy = $remotePolicy;

        return $this;
    }

    public function getDetails(): ?array
    {
        return $this->details;
    }

    public function setDetails(?array $details): self
    {
        $this->details = $details;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
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

    /**
     * @return Collection<int, Application>
     */
    public function getApplications(): Collection
    {
        return $this->applications;
    }
}
