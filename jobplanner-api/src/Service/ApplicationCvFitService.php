<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Application;
use App\Entity\User;
use App\Service\Ai\AiServiceInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class ApplicationCvFitService
{
    public function __construct(
        private PdfTextExtractor $pdfTextExtractor,
        private AiServiceInterface $aiService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function analyze(Application $application, User $user, UploadedFile $pdfFile): array
    {
        $owner = $application->getOwner();
        if (null === $owner || $owner->getId() !== $user->getId()) {
            throw new NotFoundHttpException('Candidature introuvable.');
        }

        if (!str_contains((string) $pdfFile->getMimeType(), 'pdf') && 'pdf' !== strtolower((string) $pdfFile->getClientOriginalExtension())) {
            throw new BadRequestHttpException('Le fichier doit être un PDF.');
        }

        $cvText = $this->pdfTextExtractor->extract($pdfFile);
        $analysis = $this->aiService->analyzeApplicationFit($application, $cvText);

        return [
            'application' => [
                'id' => $application->getId(),
                'jobOffer' => [
                    'id' => $application->getJobOffer()->getId(),
                    'title' => $application->getJobOffer()->getTitle(),
                    'company' => $application->getJobOffer()->getCompany(),
                ],
            ],
            'cv' => [
                'filename' => $pdfFile->getClientOriginalName(),
                'mimeType' => $pdfFile->getMimeType(),
                'textLength' => strlen($cvText),
                'textPreview' => substr($cvText, 0, 2000),
            ],
            'analysis' => $analysis,
        ];
    }
}
