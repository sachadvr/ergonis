<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Extracts the name of the company from the raw content (job offer, title).
 * Can be complemented by the AI (AiService) for a more precise extraction.
 */
final class CompanyExtractor
{
    private const PATTERN_COMPANY_IN_CONTENT = '/(?:entreprise|société|company|company name|employer|organization|organisation|firm|chez)\s*[:：]\s*([^\n,]+)/iu';
    private const PATTERN_COMPANY_IN_TITLE = '/^([^-–—]+?)\s*[-–—]\s/i';
    private const FALLBACK_COMPANY = 'Company not specified';

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
