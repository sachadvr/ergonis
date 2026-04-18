<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\CompanyExtractor;
use PHPUnit\Framework\TestCase;

final class CompanyExtractorTest extends TestCase
{
    public function testExtractReturnsCompanyFromContent(): void
    {
        $extractor = new CompanyExtractor();

        $company = $extractor->extract(
            "Job details\nCompany: Acme Corp\nLocation: Remote",
            'Backend Engineer - Example'
        );

        $this->assertSame('Acme Corp', $company);
    }

    public function testExtractFallsBackToTitle(): void
    {
        $extractor = new CompanyExtractor();

        $company = $extractor->extract(
            'No company mentioned here.',
            'Acme Corp - Backend Engineer'
        );

        $this->assertSame('Acme Corp', $company);
    }

    public function testExtractReturnsFallbackWhenNothingMatches(): void
    {
        $extractor = new CompanyExtractor();

        $company = $extractor->extract('Nothing useful here.', 'Backend Engineer');

        $this->assertSame('Company not specified', $company);
    }
}
