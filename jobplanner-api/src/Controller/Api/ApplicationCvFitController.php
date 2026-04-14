<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Application;
use App\Entity\User;
use App\Message\AnalyzeApplicationCvFitMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class ApplicationCvFitController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    #[Route('/applications/{id<\d+>}/cv-fit', name: 'api_application_cv_fit', methods: ['POST'])]
    public function analyze(Request $request, int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Authentification requise'], Response::HTTP_UNAUTHORIZED);
        }

        $application = $this->entityManager->getRepository(Application::class)->find($id);
        if (!$application instanceof Application) {
            return new JsonResponse(['error' => 'Candidature introuvable'], Response::HTTP_NOT_FOUND);
        }

        $file = $request->files->get('cv') ?? $request->files->get('pdf');
        if (null === $file) {
            return new JsonResponse(['error' => 'Un fichier PDF est requis dans le champ cv'], Response::HTTP_BAD_REQUEST);
        }

        if (!$file instanceof UploadedFile) {
            return new JsonResponse(['error' => 'Fichier PDF invalide'], Response::HTTP_BAD_REQUEST);
        }

        if (($file->getSize() ?? 0) <= 0) {
            return new JsonResponse(['error' => 'Le fichier PDF est vide'], Response::HTTP_BAD_REQUEST);
        }

        $storageDir = $this->getParameter('kernel.project_dir').'/var/cv-fit';
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0775, true);
        }

        $storedFilename = uniqid('cv-fit-', true).'.pdf';
        $storedPath = $storageDir.'/'.$storedFilename;
        $file->move($storageDir, $storedFilename);

        $application->setCvFitAnalysisStatus('queued');
        $application->setCvFitAnalysisRequestedAt(new \DateTimeImmutable());
        $application->setCvFitAnalysisResult(null);
        $application->setCvFitAnalysisCompletedAt(null);
        $this->entityManager->flush();

        $this->messageBus->dispatch(new AnalyzeApplicationCvFitMessage(
            applicationId: (int) $application->getId(),
            userId: (int) $user->getId(),
            filePath: $storedPath,
            originalFilename: $file->getClientOriginalName(),
            mimeType: $file->getClientMimeType() ?? 'application/pdf',
        ));

        return new JsonResponse([
            'status' => 'queued',
            'applicationId' => $application->getId(),
        ], Response::HTTP_ACCEPTED);
    }
}
