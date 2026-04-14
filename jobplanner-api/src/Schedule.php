<?php

declare(strict_types=1);

namespace App;

use App\Message\ProcessFollowUpsMessage;
use App\Message\SendInterviewRemindersMessage;
use App\Message\SyncAllMailboxesMessage;
use App\Repository\UserMailboxSettingsRepository;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule as SymfonySchedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

#[AsSchedule]
final class Schedule implements ScheduleProviderInterface
{
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly UserMailboxSettingsRepository $mailboxSettingsRepository,
    ) {
    }

    public function getSchedule(): SymfonySchedule
    {
        $schedule = (new SymfonySchedule())
            ->stateful($this->cache)
            ->processOnlyLastMissedRun(true);

        $schedule->add(
            // Sync all mailboxes every minute
            RecurringMessage::every('5 minutes', new SyncAllMailboxesMessage()),

            // Process follow-ups every 15 minutes
            RecurringMessage::every('30 minutes', new ProcessFollowUpsMessage()),

            // Send interview reminders every hour
            RecurringMessage::every('1 hour', new SendInterviewRemindersMessage())
        );

        return $schedule;
    }
}
