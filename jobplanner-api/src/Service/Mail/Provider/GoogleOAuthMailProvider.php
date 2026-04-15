<?php

declare(strict_types=1);

namespace App\Service\Mail\Provider;

use App\Entity\UserMailboxSettings;
use App\Service\Mail\EmailMessageMapper;
use App\Service\Mail\TokenRefreshService;
use Psr\Log\LoggerInterface;

final class GoogleOAuthMailProvider extends AbstractOAuthMailProvider
{
    public function __construct(
        UserMailboxSettings $settings,
        TokenRefreshService $tokenRefreshService,
        EmailMessageMapper $messageMapper,
        LoggerInterface $logger,
    ) {
        parent::__construct($settings, $tokenRefreshService, $messageMapper, $logger);
    }

    protected function resolveHost(): string
    {
        return 'imap.gmail.com';
    }
}
