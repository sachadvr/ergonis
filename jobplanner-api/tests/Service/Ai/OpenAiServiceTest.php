<?php

declare(strict_types=1);

namespace App\Tests\Service\Ai;

use App\Entity\Application;
use App\Entity\ApplicationStatus;
use App\Entity\JobOffer;
use App\Entity\User;
use App\Service\Ai\AiServiceInterface;
use App\Service\Ai\OpenAiService;
use App\Service\CompanyExtractor;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class OpenAiServiceTest extends AiServiceTestBase
{
    protected function createUnavailableService(): AiServiceInterface
    {
        return new OpenAiService($this->createStub(HttpClientInterface::class), new CompanyExtractor(), '');
    }

    protected function createService(): AiServiceInterface
    {
        return $this->createUnavailableService();
    }

    protected function expectedAnalysisSummary(): string
    {
        return 'No AI provider is configured.';
    }

    protected function expectedAnalysisRecommendation(): string
    {
        return 'provider_not_configured';
    }

    protected function usesProviderUnavailableAnalysisFallback(): bool
    {
        return true;
    }

    public function testExtractJobOfferFromContentUsesApiResponseWhenConfigured(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'choices' => [[
                'message' => ['content' => '{"job_title":"Backend Engineer","company_name":"Acme","location":{"city":"Paris","country":"France"}}'],
            ]],
        ]);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://api.openai.com/v1/chat/completions',
                $this->callback(static function (array $options): bool {
                    return 'Bearer test-key' === $options['headers']['Authorization']
                        && 'gpt-4o-mini' === $options['json']['model'];
                })
            )
            ->willReturn($response);

        $service = new OpenAiService($httpClient, new CompanyExtractor(), 'test-key');
        $result = $service->extractJobOfferFromContent('https://example.com/job', 'Fallback title', 'Some content');

        $this->assertSame('Backend Engineer', $result['title']);
        $this->assertSame('Acme', $result['company']);
        $this->assertSame('Paris, France', $result['location']);
        $this->assertSame('{"job_title":"Backend Engineer","company_name":"Acme","location":{"city":"Paris","country":"France"}}', $result['raw_response']);
    }

    public function testAnalyzeApplicationFitUsesApiResponseWhenConfigured(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'choices' => [[
                'message' => ['content' => '{"overall_fit":{"score":87,"level":"good","recommendation":"apply"},"summary":"Strong match","strong_matches":[],"gaps":[],"ats_keywords_to_add":[],"cv_customization_points":[],"motivation_letter_points":[],"interview_topics_to_prepare":[],"red_flags_or_unclear_points":[]}'],
            ]],
        ]);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->once())->method('request')->willReturn($response);

        $service = new OpenAiService($httpClient, new CompanyExtractor(), 'test-key');
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
