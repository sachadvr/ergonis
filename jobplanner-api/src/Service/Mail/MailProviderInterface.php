<?php

declare(strict_types=1);

namespace App\Service\Mail;

interface MailProviderInterface
{
    public function testConnection(): bool;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchEmailsSince(?\DateTimeImmutable $since): array;

    /**
     * @return string[]
     */
    public function listAvailableFolders(): array;
}
