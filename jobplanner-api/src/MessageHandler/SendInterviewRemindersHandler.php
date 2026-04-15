<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Mailer\UserMailerService;
use App\Message\SendInterviewRemindersMessage;
use App\Repository\InterviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;

#[AsMessageHandler]
final readonly class SendInterviewRemindersHandler
{
    public function __construct(
        private InterviewRepository $interviewRepository,
        private EntityManagerInterface $entityManager,
        private UserMailerService $userMailerService,
        private string $simulationMode = 'true',
    ) {
    }

    public function __invoke(SendInterviewRemindersMessage $message): void
    {
        $interviews = $this->interviewRepository->findUpcomingNeedingReminder(24);
        $isSimulation = filter_var($this->simulationMode, \FILTER_VALIDATE_BOOLEAN);

        foreach ($interviews as $interview) {
            $app = $interview->getApplication();
            $owner = $app->getOwner();
            if (null === $owner || '' === $owner->getUserIdentifier()) {
                continue;
            }

            $to = $owner->getUserIdentifier();
            if (null === $owner->getId()) {
                continue;
            }

            $from = $owner->getUserIdentifier();

            $jobOffer = $app->getJobOffer();
            $dateStr = $interview->getScheduledAt()->format('d/m/Y à H:i');
            $body = "Bonjour,\n\nRappel : entretien le {$dateStr} pour {$jobOffer->getTitle()} chez {$jobOffer->getCompany()}.";
            if ($interview->getLocationOrLink()) {
                $body .= "\n\nLien : ".$interview->getLocationOrLink();
            }

            if (!$isSimulation) {
                $email = (new Email())
                    ->from($from)
                    ->to($to)
                    ->subject("Rappel entretien : {$jobOffer->getTitle()}")
                    ->text($body);
                $this->userMailerService->send((int) $owner->getId(), $email);
            }

            $interview->setReminderSent(true);
        }

        $this->entityManager->flush();
    }
}
