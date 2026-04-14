<?php

declare(strict_types=1);

namespace App\DTO;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\State\NotificationProvider;

#[ApiResource(
    shortName: 'Notification',
    operations: [new GetCollection(provider: NotificationProvider::class)],
    paginationEnabled: false,
)]
final readonly class NotificationItem
{
    public function __construct(
        public int $id,
        public string $type,
        public string $title,
        public string $message,
        public string $createdAt,
        public int $applicationId,
        public string $applicationTitle,
        public string $sender,
        public string $subject,
        public bool $isSeen,
        public string $href,
    ) {
    }
}
