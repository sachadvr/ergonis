<?php

declare(strict_types=1);

namespace App\Tests\Service\Ai;

use App\Entity\Application;
use App\Entity\ApplicationStatus;
use App\Entity\JobOffer;
use App\Entity\User;
use App\Service\Ai\AiServiceInterface;
use App\Service\Ai\AnthropicService;
use App\Service\CompanyExtractor;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class AnthropicServiceTest extends AiServiceTestBase
{
    protected function createUnavailableService(): AiServiceInterface
    {
        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('request')->willThrowException(new \RuntimeException('boom'));

        return new AnthropicService($httpClient, new CompanyExtractor(), '');
    }

    protected function createService(): AiServiceInterface
    {
        return $this->createUnavailableService();
    }

    protected function expectedAnalysisSummary(): string
    {
        return 'AI analysis unavailable.';
    }

    protected function expectedAnalysisRecommendation(): string
    {
        return 'analysis_failed';
    }

    protected function usesProviderUnavailableAnalysisFallback(): bool
    {
        return true;
    }

    public function testExtractJobOfferFromContentUsesApiResponseWhenConfigured(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'content' => [[
                'text' => '{"job_title":"Backend Engineer","company_name":"Acme","location":{"city":"Paris","country":"France"}}',
            ]],
        ]);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->once())->method('request')->willReturn($response);

        $service = new AnthropicService($httpClient, new CompanyExtractor(), 'test-key');
        $result = $service->extractJobOfferFromContent('https://example.com/job', 'Fallback title', 'Some content');

        $this->assertSame('Backend Engineer', $result['title']);
        $this->assertSame('Acme', $result['company']);
        $this->assertSame('Paris, France', $result['location']);
    }

    public function testAnalyzeApplicationFitUsesApiResponseWhenConfigured(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'content' => [[
                'text' => '{"overall_fit":{"score":87,"level":"good","recommendation":"apply"},"summary":"Strong match","strong_matches":[],"gaps":[],"ats_keywords_to_add":[],"cv_customization_points":[],"motivation_letter_points":[],"interview_topics_to_prepare":[],"red_flags_or_unclear_points":[]}',
            ]],
        ]);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->once())->method('request')->willReturn($response);

        $service = new AnthropicService($httpClient, new CompanyExtractor(), 'test-key');
        $result = $service->analyzeApplicationFit($this->createApplication(), 'CV text here');

        $this->assertSame('Strong match', $result['summary']);
        $this->assertSame(87, $result['overall_fit']['score']);
        $this->assertSame('apply', $result['overall_fit']['recommendation']);
    }

    protected function createApplication(): Application
    {
        $jobOffer = (new JobOffer())
            ->setTitle('Backend Engineer')
            ->setCompany('Acme Corp');

        return (new Application())
            ->setJobOffer($jobOffer)
            ->setStatus(ApplicationStatus::APPLIED)
            ->setOwner((new User())->setEmail('candidate@example.com')->setPassword('secret'));
    }
}
