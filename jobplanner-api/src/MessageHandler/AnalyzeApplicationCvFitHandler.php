<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Application;
use App\Message\AnalyzeApplicationCvFitMessage;
use App\Service\Ai\AiServiceInterface;
use App\Service\ApplicationCvFitNotificationPublisher;
use App\Service\PdfTextExtractor;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class AnalyzeApplicationCvFitHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AiServiceInterface $aiService,
        private PdfTextExtractor $pdfTextExtractor,
        private ApplicationCvFitNotificationPublisher $notificationPublisher,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(AnalyzeApplicationCvFitMessage $message): void
    {
        $application = $this->entityManager->getRepository(Application::class)->find($message->applicationId);
        if (!$application instanceof Application || $application->getOwner()?->getId() !== $message->userId) {
            $this->logger->warning('CV fit application not found', ['applicationId' => $message->applicationId]);
            $this->cleanup($message->filePath);

            return;
        }

        $application->setCvFitAnalysisStatus('processing');
        $application->setCvFitAnalysisRequestedAt(new \DateTimeImmutable());
        $application->setCvFitAnalysisResult(null);
        $application->setCvFitAnalysisCompletedAt(null);
        $this->entityManager->flush();
        $this->notificationPublisher->publish($application);

        try {
            $file = new UploadedFile($message->filePath, $message->originalFilename, $message->mimeType, null, true);
            $cvText = $this->pdfTextExtractor->extract($file);
            $analysis = $this->aiService->analyzeApplicationFit($application, $cvText);

            $application->setCvFitAnalysisStatus('completed');
            $application->setCvFitAnalysisResult($analysis);
            $application->setCvFitAnalysisCompletedAt(new \DateTimeImmutable());
            $this->entityManager->flush();
            $this->notificationPublisher->publish($application);

            $this->logger->info('CV fit analysis completed', [
                'applicationId' => $message->applicationId,
            ]);
        } catch (\Throwable $exception) {
            $application->setCvFitAnalysisStatus('failed');
            $application->setCvFitAnalysisResult([
                'summary' => 'AI analysis unavailable.',
                'error' => $exception->getMessage(),
            ]);
            $application->setCvFitAnalysisCompletedAt(new \DateTimeImmutable());
            $this->entityManager->flush();
            $this->notificationPublisher->publish($application);

            $this->logger->warning('CV fit analysis failed', [
                'applicationId' => $message->applicationId,
                'error' => $exception->getMessage(),
            ]);
        } finally {
            $this->cleanup($message->filePath);
        }
    }

    private function cleanup(string $filePath): void
    {
        if (is_file($filePath)) {
            @unlink($filePath);
        }
    }
}
