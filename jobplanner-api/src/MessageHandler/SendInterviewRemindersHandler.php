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
    ) {
    }

    public function __invoke(SendInterviewRemindersMessage $message): void
    {
        $interviews = $this->interviewRepository->findUpcomingNeedingReminder(24);

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
            $body = "Hello,\n\nReminder: interview on {$dateStr} for {$jobOffer->getTitle()} at {$jobOffer->getCompany()}.";
            if ($interview->getLocationOrLink()) {
                $body .= "\n\nLink: ".$interview->getLocationOrLink();
            }

            $email = (new Email())
                ->from($from)
                ->to($to)
                ->subject("Reminder: interview on {$jobOffer->getTitle()}")
                ->text($body);
            $this->userMailerService->send((int) $owner->getId(), $email);

            $interview->setReminderSent(true);
        }

        $this->entityManager->flush();
    }
}
