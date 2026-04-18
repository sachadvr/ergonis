<?php

declare(strict_types=1);

namespace App\Tests\Service\Ai;

use App\Entity\Application;
use App\Entity\ApplicationStatus;
use App\Entity\JobOffer;
use App\Entity\User;
use App\Service\Ai\AiServiceFactory;
use App\Service\Ai\AnthropicService;
use App\Service\Ai\ConfigurableAiService;
use App\Service\Ai\NullAiService;
use App\Service\Ai\OllamaService;
use App\Service\Ai\OpenAiService;
use App\Service\CompanyExtractor;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class AiServiceVariantsTest extends TestCase
{
    public function testAiServiceFactoryReturnsConfiguredImplementation(): void
    {
        $companyExtractor = new CompanyExtractor();
        $nullAi = new NullAiService($companyExtractor);
        $openAi = new OpenAiService($this->createStub(HttpClientInterface::class), $companyExtractor, '');
        $anthropic = new AnthropicService($this->createStub(HttpClientInterface::class), $companyExtractor, '');
        $ollama = new OllamaService($this->createStub(HttpClientInterface::class), $companyExtractor, $this->createStub(LoggerInterface::class));

        $factory = new AiServiceFactory($nullAi, $openAi, $anthropic, $ollama, 'openai');

        $this->assertSame($openAi, $factory->get());
    }

    public function testConfigurableAiServiceDelegatesToFactory(): void
    {
        $companyExtractor = new CompanyExtractor();
        $nullAi = new NullAiService($companyExtractor);
        $factory = new AiServiceFactory(
            $nullAi,
            new OpenAiService($this->createStub(HttpClientInterface::class), $companyExtractor, ''),
            new AnthropicService($this->createStub(HttpClientInterface::class), $companyExtractor, ''),
            new OllamaService($this->createStub(HttpClientInterface::class), $companyExtractor, $this->createStub(LoggerInterface::class)),
            'unknown',
        );

        $service = new ConfigurableAiService($factory);
        $application = $this->createApplication();

        $this->assertSame('Offer without title', $service->extractJobOfferFromContent('', '', '')['title']);
        $this->assertSame('No AI provider is configured.', $service->analyzeApplicationFit($application, 'CV')['summary']);
        $this->assertSame([], $service->suggestReplies('email'));
    }

    public function testNullAiServiceUsesCompanyExtractorAndFallbacks(): void
    {
        $service = new NullAiService(new CompanyExtractor());
        $application = $this->createApplication();

        $extraction = $service->extractJobOfferFromContent('', '', 'company: Acme');
        $analysis = $service->analyzeApplicationFit($application, 'cv');

        $this->assertSame('Offer without title', $extraction['title']);
        $this->assertSame('Acme', $extraction['company']);
        $this->assertSame(0, $analysis['overall_fit']['score']);
        $this->assertStringContainsString('No AI provider is configured.', $analysis['summary']);
        $this->assertSame([], $service->suggestReplies('email'));
    }

    public function testOpenAiServiceFallsBackWhenApiKeyIsMissing(): void
    {
        $service = new OpenAiService($this->createStub(HttpClientInterface::class), new CompanyExtractor(), '');

        $result = $service->extractJobOfferFromContent('', '', 'company: Acme');

        $this->assertSame('Offer without title', $result['title']);
        $this->assertSame('Acme', $result['company']);
    }

    public function testAnthropicServiceFallsBackWhenApiKeyIsMissing(): void
    {
        $service = new AnthropicService($this->createStub(HttpClientInterface::class), new CompanyExtractor(), '');

        $result = $service->extractJobOfferFromContent('', '', 'company: Acme');

        $this->assertSame('Offer without title', $result['title']);
        $this->assertSame('Acme', $result['company']);
    }

    private function createApplication(): Application
    {
        $user = (new User())->setEmail('candidate@example.com')->setPassword('secret');
        $jobOffer = (new JobOffer())->setTitle('Backend Engineer')->setCompany('Acme');
        $application = (new Application())->setOwner($user)->setJobOffer($jobOffer)->setStatus(ApplicationStatus::APPLIED);

        return $application;
    }
}
