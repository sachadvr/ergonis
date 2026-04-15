<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Extrait le nom de l'entreprise depuis un contenu brut (offre d'emploi, titre).
 * Peut être complété par l'IA (AiService) pour une extraction plus précise.
 */
final class CompanyExtractor
{
    private const PATTERN_COMPANY_IN_CONTENT = '/(?:entreprise|société|company|chez)\s*[:：]\s*([^\n,]+)/iu';
    private const PATTERN_COMPANY_IN_TITLE = '/^([^-–—]+?)\s*[-–—]\s/i';
    private const FALLBACK_COMPANY = 'Entreprise non spécifiée';

    public function extract(string $content, string $title): string
    {
        $company = $this->extractFromContent($content);
        if (null !== $company) {
            return $company;
        }

        $company = $this->extractFromTitle($title);
        if (null !== $company) {
            return $company;
        }

        return self::FALLBACK_COMPANY;
    }

    private function extractFromContent(string $content): ?string
    {
        if (preg_match(self::PATTERN_COMPANY_IN_CONTENT, $content, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    private function extractFromTitle(string $title): ?string
    {
        if (preg_match(self::PATTERN_COMPANY_IN_TITLE, $title, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }
}
