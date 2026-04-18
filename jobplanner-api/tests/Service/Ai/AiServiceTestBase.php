<?php

declare(strict_types=1);

namespace App\Tests\Service\Ai;

use App\Entity\Application;
use App\Entity\ApplicationStatus;
use App\Entity\JobOffer;
use App\Entity\User;
use App\Service\Ai\AiServiceInterface;
use PHPUnit\Framework\TestCase;

abstract class AiServiceTestBase extends TestCase
{
    abstract protected function createUnavailableService(): AiServiceInterface;

    abstract protected function createService(): AiServiceInterface;

    abstract protected function expectedAnalysisSummary(): string;

    abstract protected function expectedAnalysisRecommendation(): string;

    public function testExtractJobOfferFromContentFallsBackToCompanyExtractor(): void
    {
        $result = $this->createUnavailableService()->extractJobOfferFromContent(
            'https://example.com/job',
            'Backend Engineer',
            'Company: Acme Corp'
        );

        $this->assertSame('Backend Engineer', $result['title']);
        $this->assertSame('Acme Corp', $result['company']);
        $this->assertNull($result['location']);
    }

    public function testAnalyzeApplicationFitFallsBackWhenProviderUnavailable(): void
    {
        $result = $this->createUnavailableService()->analyzeApplicationFit($this->createApplication(), 'CV text');

        $this->assertSame($this->expectedAnalysisSummary(), $result['summary']);
        $this->assertSame($this->expectedAnalysisRecommendation(), $result['overall_fit']['recommendation']);
    }

    public function testGenerateFollowUpEmailFallsBackWhenProviderUnavailable(): void
    {
        $email = $this->createUnavailableService()->generateFollowUpEmail($this->createApplication());

        $this->assertStringContainsString('Backend Engineer', $email);
        $this->assertStringContainsString('Acme Corp', $email);
    }

    public function testSummarizeEmailShortBodiesAreReturnedAsIs(): void
    {
        $summary = $this->createService()->summarizeEmail('Short body');

        $this->assertSame('Short body', $summary);
    }

    public function testSuggestRepliesReturnsEmptyWhenProviderUnavailable(): void
    {
        $this->assertSame([], $this->createUnavailableService()->suggestReplies('Any email body'));
    }

    protected function createApplication(): Application
    {
        $jobOffer = (new JobOffer())
            ->setTitle('Backend Engineer')
            ->setCompany('Acme Corp')
            ->setLocation('Remote')
            ->setJobSummary('Build APIs')
            ->setContractType('CDI')
            ->setRemotePolicy('Hybrid')
            ->setRecruiterContactEmail('recruiter@example.com');

        return (new Application())
            ->setJobOffer($jobOffer)
            ->setStatus(ApplicationStatus::APPLIED)
            ->setOwner((new User())->setEmail('candidate@example.com')->setPassword('secret'));
    }
}
