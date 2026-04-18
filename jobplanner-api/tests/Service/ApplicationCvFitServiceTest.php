<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Application;
use App\Entity\ApplicationStatus;
use App\Entity\JobOffer;
use App\Entity\User;
use App\Service\Ai\AiServiceInterface;
use App\Service\ApplicationCvFitService;
use App\Service\PdfTextExtractor;
use App\Tests\Support\TestEntityHelpers;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ApplicationCvFitServiceTest extends TestCase
{
    use TestEntityHelpers;

    private PdfTextExtractor&MockObject $pdfTextExtractor;
    private AiServiceInterface&MockObject $aiService;
    private ApplicationCvFitService $service;

    protected function setUp(): void
    {
        $this->pdfTextExtractor = $this->createMock(PdfTextExtractor::class);
        $this->aiService = $this->createMock(AiServiceInterface::class);
        $this->service = new ApplicationCvFitService($this->pdfTextExtractor, $this->aiService);
    }

    public function testAnalyzeReturnsAggregatedResult(): void
    {
        $owner = $this->createUser(10, 'candidate@example.com');
        $jobOffer = $this->createJobOffer(20);
        $application = $this->createApplication(30, $owner, $jobOffer);
        $application->setStatus(ApplicationStatus::APPLIED);

        $file = $this->createPdfUpload();

        $this->pdfTextExtractor->expects($this->once())
            ->method('extract')
            ->with($file)
            ->willReturn('CV text');

        $this->aiService->expects($this->once())
            ->method('analyzeApplicationFit')
            ->with($application, 'CV text')
            ->willReturn([
                'overall_fit' => ['score' => 82, 'level' => 'strong', 'recommendation' => 'apply'],
                'summary' => 'Good fit.',
                'strong_matches' => ['Symfony'],
                'gaps' => [],
                'ats_keywords_to_add' => [],
                'cv_customization_points' => [],
                'motivation_letter_points' => [],
                'interview_topics_to_prepare' => [],
                'red_flags_or_unclear_points' => [],
            ]);

        $result = $this->service->analyze($application, $owner, $file);

        $this->assertSame(30, $result['application']['id']);
        $this->assertSame('CV text', $result['cv']['textPreview']);
        $this->assertSame(82, $result['analysis']['overall_fit']['score']);
    }

    public function testAnalyzeThrowsWhenApplicationDoesNotBelongToUser(): void
    {
        $owner = $this->createUser(10, 'owner@example.com');
        $otherUser = $this->createUser(99, 'other@example.com');
        $jobOffer = $this->createJobOffer(20);
        $application = $this->createApplication(30, $owner, $jobOffer);

        $this->pdfTextExtractor->expects($this->never())->method('extract');
        $this->aiService->expects($this->never())->method('analyzeApplicationFit');

        $this->expectException(NotFoundHttpException::class);

        $this->service->analyze($application, $otherUser, $this->createPdfUpload());
    }

    public function testAnalyzeRejectsNonPdfFile(): void
    {
        $owner = $this->createUser(10, 'candidate@example.com');
        $jobOffer = $this->createJobOffer(20);
        $application = $this->createApplication(30, $owner, $jobOffer);

        $this->pdfTextExtractor->expects($this->never())->method('extract');
        $this->aiService->expects($this->never())->method('analyzeApplicationFit');

        $this->expectException(BadRequestHttpException::class);

        $this->service->analyze($application, $owner, $this->createPdfUpload('cv.txt', 'text/plain', 'plain text'));
    }

    private function createApplication(int $id, User $owner, JobOffer $jobOffer): Application
    {
        $application = new Application();
        $application->setJobOffer($jobOffer);
        $application->setOwner($owner);
        $this->setEntityId($application, $id);

        return $application;
    }

    private function createJobOffer(int $id): JobOffer
    {
        $jobOffer = new JobOffer();
        $jobOffer->setTitle('Backend Engineer');
        $jobOffer->setCompany('Acme');
        $this->setEntityId($jobOffer, $id);

        return $jobOffer;
    }

    private function createUser(int $id, string $email): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword('secret');
        $this->setEntityId($user, $id);

        return $user;
    }
}
