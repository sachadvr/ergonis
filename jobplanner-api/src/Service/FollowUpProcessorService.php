<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ScheduledFollowUp;
use App\Mailer\UserMailerService;
use App\Service\Ai\AiServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mime\Email;

final readonly class FollowUpProcessorService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AiServiceInterface $aiService,
        private UserMailerService $userMailerService,
        private string $simulationMode = 'true',
    ) {
    }

    public function process(ScheduledFollowUp $followUp): void
    {
        $application = $followUp->getApplication();
        $jobOffer = $application->getJobOffer();
        $to = $jobOffer->getRecruiterContactEmail();

        if (null === $to || '' === $to) {
            $followUp->setStatus(ScheduledFollowUp::STATUS_CANCELLED);
            $followUp->setCancelledAt(new \DateTimeImmutable());
            $this->entityManager->flush();

            return;
        }

        $content = $followUp->getGeneratedContent();
        if (null === $content || '' === $content) {
            $content = $this->aiService->generateFollowUpEmail($application, 'professionnel');
            $followUp->setGeneratedContent($content);
        }

        $isSimulation = filter_var($this->simulationMode, \FILTER_VALIDATE_BOOLEAN);
        if (!$isSimulation) {
            $owner = $application->getOwner();
            if (null === $owner || null === $owner->getId()) {
                throw new \RuntimeException('No owner found for follow-up '.$followUp->getId());
            }

            $from = $owner->getUserIdentifier();

            $subject = 'Re: Application for '.$jobOffer->getTitle();

            $email = (new Email())
                ->from($from)
                ->to($to)
                ->subject($subject)
                ->text($content);

            $this->userMailerService->send((int) $owner->getId(), $email);
        }

        $followUp->setStatus(ScheduledFollowUp::STATUS_SENT);
        $this->entityManager->flush();
    }
}
