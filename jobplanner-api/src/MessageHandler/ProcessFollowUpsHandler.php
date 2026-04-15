<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\ProcessFollowUpsMessage;
use App\Repository\ScheduledFollowUpRepository;
use App\Service\FollowUpProcessorService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class ProcessFollowUpsHandler
{
    public function __construct(
        private ScheduledFollowUpRepository $followUpRepository,
        private FollowUpProcessorService $processor,
    ) {
    }

    public function __invoke(ProcessFollowUpsMessage $message): void
    {
        $pending = $this->followUpRepository->findPendingDueNow();
        foreach ($pending as $followUp) {
            $this->processor->process($followUp);
        }
    }
}
