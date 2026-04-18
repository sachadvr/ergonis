<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\JobOfferFromExtensionInput;
use App\Entity\Application;
use App\Entity\ApplicationHistory;
use App\Entity\ApplicationHistoryActionType;
use App\Entity\ApplicationStatus;
use App\Entity\JobOffer;
use App\Entity\User;
use App\Exception\ValidationFailedException;
use App\Service\Ai\AiServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class JobOfferFromExtensionService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
        private readonly AiServiceInterface $aiService,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @throws ValidationFailedException
     *
     * @return array{jobOffer: array{id: int, title: string, company: string}, application: null|array{id: int, status: string}}
     */
    public function createFromExtension(JobOfferFromExtensionInput $input, User $user): array
    {
        $this->validateInput($input);

        $jobOffer = $this->buildJobOffer($input, $user);
        $this->entityManager->persist($jobOffer);

        $application = $input->createApplication ? $this->createApplication($jobOffer, $user) : null;
        if (null !== $application) {
            $this->entityManager->persist($application);
            $history = $this->createApplicationHistory($application);
            $this->entityManager->persist($history);
        }

        $this->entityManager->flush();

        return $this->buildResponse($jobOffer, $application);
    }

    /**
     * @throws ValidationFailedException
     */
    private function validateInput(JobOfferFromExtensionInput $input): void
    {
        $violations = $this->validator->validate($input);
        if ($violations->count() > 0) {
            throw new ValidationFailedException($violations);
        }
    }

    private function buildJobOffer(JobOfferFromExtensionInput $input, User $user): JobOffer
    {
        $this->logger->warning('Extension job offer payload size', [
            'url_len' => strlen($input->url),
            'title_len' => strlen($input->title),
            'content_len' => strlen($input->content),
            'content_preview' => substr($input->content, 0, 200),
        ]);

        $extracted = $this->aiService->extractJobOfferFromContent($input->url, $input->getResolvedTitle(), $input->content);

        $fullData = $extracted['full_data'] ?? [];

        $jobOffer = new JobOffer();
        $jobOffer->setTitle($extracted['title']);
        $jobOffer->setCompany($extracted['company']);
        $jobOffer->setLocation($extracted['location'] ?? null);
        $jobOffer->setUrl($input->url);
        $jobOffer->setRawContent('' !== $input->content ? $input->content : null);
        $jobOffer->setSourceUrl('' !== $input->url ? $input->url : null);
        $jobOffer->setOwner($user);

        if (!empty($fullData)) {
            $jobOffer->setJobSummary($fullData['job_summary'] ?? null);
            $jobOffer->setRecruiterContactEmail($this->normalizeNullableString($fullData['recruiter_contact_email'] ?? null));

            if (isset($fullData['salary'])) {
                $salary = $fullData['salary'];
                $jobOffer->setSalaryMin($this->normalizeNullableDecimal($salary['min'] ?? null));
                $jobOffer->setSalaryMax($this->normalizeNullableDecimal($salary['max'] ?? null));
                $jobOffer->setSalaryCurrency($this->normalizeNullableString($salary['currency'] ?? null));
            }

            if (isset($fullData['contract'])) {
                $jobOffer->setContractType($this->normalizeNullableString($fullData['contract']['type'] ?? null));
            }

            if (isset($fullData['location'])) {
                $jobOffer->setRemotePolicy($this->normalizeNullableString($fullData['location']['remote_policy'] ?? null));
            }

            $jobOffer->setDetails($fullData);
        }

        $violations = $this->validator->validate($jobOffer);
        if ($violations->count() > 0) {
            throw new ValidationFailedException($violations);
        }

        return $jobOffer;
    }

    private function normalizeNullableDecimal(mixed $value): ?string
    {
        if (null === $value || '' === $value) {
            return null;
        }

        return is_numeric($value) ? (string) $value : null;
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        if (null === $value) {
            return null;
        }

        $normalized = trim((string) $value);

        return '' === $normalized ? null : $normalized;
    }

    private function createApplication(JobOffer $jobOffer, User $user): Application
    {
        $application = new Application();
        $application->setJobOffer($jobOffer);
        $application->setOwner($user);
        $application->setStatus(ApplicationStatus::WISHLIST);
        $application->setLastActivityAt(new \DateTimeImmutable());

        return $application;
    }

    private function createApplicationHistory(Application $application): ApplicationHistory
    {
        $history = new ApplicationHistory();
        $history->setApplication($application);
        $history->setActionType(ApplicationHistoryActionType::IMPORTED_FROM_EXTENSION);
        $history->setDescription('Application created from the browser extension');

        return $history;
    }

    /**
     * @return array{jobOffer: array{id: int, title: string, company: string}, application: null|array{id: int, status: string}}
     */
    private function buildResponse(JobOffer $jobOffer, ?Application $application): array
    {
        $response = [
            'jobOffer' => [
                'id' => $jobOffer->getId(),
                'title' => $jobOffer->getTitle(),
                'company' => $jobOffer->getCompany(),
            ],
            'application' => null,
        ];

        if (null !== $application) {
            $response['application'] = [
                'id' => $application->getId(),
                'status' => ApplicationStatus::WISHLIST->value,
            ];
        }

        return $response;
    }
}
