<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Application;
use App\Repository\ApplicationRepository;
use App\Repository\RecruiterEmailRepository;

/**
 * Associates the received emails to existing applications.
 * Criteria: sender (domain), content (company, job title).
 */
final readonly class EmailMatchingService
{
    private const EMAIL_DOMAIN_PATTERN = '/@([\w.-]+\.[a-z]{2,})/i';
    private const EMAIL_ADDRESS_PATTERN = '/([a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,})/i';

    public function __construct(
        private ApplicationRepository $applicationRepository,
        private RecruiterEmailRepository $recruiterEmailRepository,
    ) {
    }

    public function findMatchingApplication(string $sender, string $subject, string $body, int $userId): ?Application
    {
        $applications = $this->applicationRepository->findActiveByUser($userId);
        $searchContext = $this->buildSearchContext($sender, $subject, $body);

        foreach ($applications as $application) {
            if ($this->matches($application, $searchContext)) {
                return $application;
            }
        }

        return null;
    }

    public function isAlreadyProcessed(string $messageId, int $userId): bool
    {
        return $this->recruiterEmailRepository->isAlreadyProcessed($messageId, $userId);
    }

    /**
     * @param array{senderEmail: string, senderDomain: string, normalizedText: string} $searchContext
     */
    private function matches(Application $application, array $searchContext): bool
    {
        $jobOffer = $application->getJobOffer();
        $recruiterEmail = $this->normalizeEmail((string) ($jobOffer->getRecruiterContactEmail() ?? ''));
        $recruiterDomain = $this->extractDomain($recruiterEmail);
        $companyNeedle = $this->normalizeForMatch($jobOffer->getCompany());
        $jobTitleNeedle = $this->normalizeForMatch($jobOffer->getTitle());

        if ('' !== $recruiterEmail && $recruiterEmail === $searchContext['senderEmail']) {
            return true;
        }

        if ('' !== $recruiterDomain && str_contains($searchContext['senderDomain'], $recruiterDomain)) {
            return true;
        }

        if ('' !== $companyNeedle && str_contains($searchContext['senderDomain'], $companyNeedle)) {
            return true;
        }

        if ('' !== $companyNeedle && str_contains($searchContext['normalizedText'], $companyNeedle)) {
            return true;
        }

        return '' !== $jobTitleNeedle && str_contains($searchContext['normalizedText'], $jobTitleNeedle);
    }

    /**
     * @return array{senderEmail: string, senderDomain: string, normalizedText: string}
     */
    private function buildSearchContext(string $sender, string $subject, string $body): array
    {
        $senderEmail = $this->extractEmailAddress($sender);

        return [
            'senderEmail' => $senderEmail,
            'senderDomain' => $this->extractDomain($senderEmail),
            'normalizedText' => $this->normalizeForMatch($subject.' '.$body),
        ];
    }

    private function extractEmailAddress(string $value): string
    {
        if (preg_match(self::EMAIL_ADDRESS_PATTERN, $value, $matches)) {
            return $this->normalizeEmail($matches[1]);
        }

        return $this->normalizeEmail($value);
    }

    private function normalizeEmail(string $value): string
    {
        return strtolower(trim($value));
    }

    private function extractDomain(string $email): string
    {
        if (preg_match(self::EMAIL_DOMAIN_PATTERN, $email, $matches)) {
            return strtolower($matches[1]);
        }

        return '';
    }

    private function normalizeForMatch(string $value): string
    {
        return strtolower(preg_replace('/\s+/', '', $value));
    }
}
