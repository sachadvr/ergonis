<?php

declare(strict_types=1);

namespace App\Service\Ai;

use App\Entity\Application;

/**
 * Délègue vers le service IA configuré (OpenAI, Anthropic ou Null).
 */
final readonly class ConfigurableAiService implements AiServiceInterface
{
    public function __construct(
        private AiServiceFactory $factory,
    ) {
    }

    public function extractJobOfferFromContent(string $url, string $title, string $content): array
    {
        return $this->factory->get()->extractJobOfferFromContent($url, $title, $content);
    }

    public function analyzeApplicationFit(Application $application, string $cvText): array
    {
        return $this->factory->get()->analyzeApplicationFit($application, $cvText);
    }

    public function generateFollowUpEmail(Application $application, string $tone = 'professionnel'): string
    {
        return $this->factory->get()->generateFollowUpEmail($application, $tone);
    }

    public function summarizeEmail(string $emailBody): string
    {
        return $this->factory->get()->summarizeEmail($emailBody);
    }

    public function suggestReplies(string $emailBody): array
    {
        return $this->factory->get()->suggestReplies($emailBody);
    }
}
