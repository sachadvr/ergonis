<?php

declare(strict_types=1);

namespace App\Service\Ai;

use App\Entity\Application;
use App\Service\CompanyExtractor;

/**
 * Implémentation de repli sans API IA configurée.
 * Utilise CompanyExtractor pour une extraction basique.
 */
final class NullAiService implements AiServiceInterface
{
    public function __construct(
        private readonly CompanyExtractor $companyExtractor,
    ) {
    }

    public function extractJobOfferFromContent(string $url, string $title, string $content): array
    {
        return [
            'title' => '' !== $title ? $title : 'Offre sans titre',
            'company' => $this->companyExtractor->extract($content, $title),
            'location' => null,
        ];
    }

    public function analyzeApplicationFit(Application $application, string $cvText): array
    {
        return [
            'overall_fit' => [
                'score' => 0,
                'level' => 'unknown',
                'recommendation' => 'provider_not_configured',
            ],
            'summary' => 'Aucun provider IA n\'est configuré.',
            'strong_matches' => [],
            'gaps' => [],
            'ats_keywords_to_add' => [],
            'cv_customization_points' => [],
            'motivation_letter_points' => [],
            'interview_topics_to_prepare' => [],
            'red_flags_or_unclear_points' => [],
        ];
    }

    public function generateFollowUpEmail(Application $application, string $tone = 'professionnel'): string
    {
        $jobTitle = $application->getJobOffer()->getTitle();
        $company = $application->getJobOffer()->getCompany();

        return "Bonjour,\n\nJ'ai le plaisir de vous contacter concernant ma candidature au poste de {$jobTitle} chez {$company}.\n\nJe reste à votre disposition pour tout complément d'information.\n\nCordialement";
    }

    public function summarizeEmail(string $emailBody): string
    {
        return substr($emailBody, 0, 500).(strlen($emailBody) > 500 ? '...' : '');
    }

    public function suggestReplies(string $emailBody): array
    {
        return [];
    }
}
