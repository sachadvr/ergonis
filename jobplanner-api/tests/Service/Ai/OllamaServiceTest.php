<?php

declare(strict_types=1);

namespace App\Tests\Service\Ai;

use App\Service\Ai\AiServiceInterface;
use App\Service\Ai\OllamaService;
use App\Service\CompanyExtractor;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class OllamaServiceTest extends AiServiceTestBase
{
    protected function createUnavailableService(): AiServiceInterface
    {
        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('request')->willThrowException(new \RuntimeException('boom'));

        return new OllamaService($httpClient, new CompanyExtractor(), $this->createStub(LoggerInterface::class));
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
}
