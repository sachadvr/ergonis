<?php

declare(strict_types=1);

namespace App\Service\Ai;

use App\Entity\Application;
use App\Service\CompanyExtractor;

abstract class AbstractAiService implements AiServiceInterface
{
    public function __construct(
        protected readonly CompanyExtractor $companyExtractor,
    ) {
    }

    /**
     * @return array{title: string, company: string, location: null}
     */
    protected function buildFallbackExtraction(string $title, string $content): array
    {
        return [
            'title' => '' !== $title ? $title : 'Offre sans titre',
            'company' => $this->companyExtractor->extract($content, $title),
            'location' => null,
        ];
    }

    /**
     * @return array{title: string, company: string, location: ?string, full_data: array<string, mixed>, raw_response: string}
     */
    protected function buildExtractionResult(array $decoded, string $title, string $content, string $rawResponse): array
    {
        return [
            'title' => $decoded['job_title'] ?? ('' !== $title ? $title : 'Offre sans titre'),
            'company' => $decoded['company_name'] ?? $this->companyExtractor->extract($content, $title),
            'location' => $this->buildLocation($decoded['location'] ?? null),
            'full_data' => $decoded,
            'raw_response' => $rawResponse,
        ];
    }

    protected function extractJsonObject(string $text): ?array
    {
        if (preg_match('/\{.*\}/s', $text, $matches)) {
            $text = $matches[0];
        }

        $decoded = json_decode($text, true);

        return is_array($decoded) ? $decoded : null;
    }

    protected function buildLocation(mixed $location): ?string
    {
        if (!is_array($location)) {
            return null;
        }

        $parts = array_filter([$location['city'] ?? null, $location['country'] ?? null]);
        if ([] === $parts) {
            return null;
        }

        return implode(', ', $parts);
    }

    protected function buildApplicationFitContext(Application $application, string $cvText): string
    {
        $jobOffer = $application->getJobOffer();
        $details = $jobOffer->getDetails() ?? [];

        $payload = [
            'application' => [
                'id' => $application->getId(),
                'status' => $application->getStatus()->value,
                'applied_at' => $application->getAppliedAt()?->format(DATE_ATOM),
            ],
            'job_offer' => [
                'title' => $jobOffer->getTitle(),
                'company' => $jobOffer->getCompany(),
                'location' => $jobOffer->getLocation(),
                'job_summary' => $jobOffer->getJobSummary(),
                'contract_type' => $jobOffer->getContractType(),
                'remote_policy' => $jobOffer->getRemotePolicy(),
                'salary_min' => $jobOffer->getSalaryMin(),
                'salary_max' => $jobOffer->getSalaryMax(),
                'salary_currency' => $jobOffer->getSalaryCurrency(),
                'recruiter_contact_email' => $jobOffer->getRecruiterContactEmail(),
                'details' => $details,
                'raw_content' => $jobOffer->getRawContent(),
            ],
            'cv_text' => $this->limitText($cvText, 12000),
        ];

        return (string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    protected function limitText(string $text, int $limit): string
    {
        return strlen($text) > $limit ? substr($text, 0, $limit) : $text;
    }

    /**
     * @return array<int, string>
     */
    protected function parseReplySuggestions(string $text): array
    {
        $lines = array_filter(array_map(static function (string $line): string {
            return trim(ltrim($line, '- '));
        }, explode("\n", trim($text))));

        return array_values(array_slice($lines, 0, 3));
    }

    protected function getFallbackEmail(string $poste, string $entreprise): string
    {
        return "Bonjour,\n\nJ'ai le plaisir de vous contacter concernant ma candidature au poste de {$poste} chez {$entreprise}.\n\nJe reste à votre disposition pour tout complément d'information.\n\nCordialement";
    }
}
