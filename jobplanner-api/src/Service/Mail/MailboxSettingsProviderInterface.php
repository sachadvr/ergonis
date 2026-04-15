<?php

declare(strict_types=1);

namespace App\Service\Mail;

use App\Entity\UserMailboxSettings;

interface MailboxSettingsProviderInterface
{
    public function findByUserId(int $userId): ?UserMailboxSettings;
}
