<?php

declare(strict_types=1);

namespace App\Service\Mail\Provider;

use App\Service\Mail\EmailMessageMapper;
use App\Service\Mail\MailProviderInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class MailpitMailProvider implements MailProviderInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly EmailMessageMapper $messageMapper,
        private readonly LoggerInterface $logger,
        private readonly string $mailpitUrl,
    ) {
    }

    public function testConnection(): bool
    {
        try {
            $this->httpClient->request('GET', $this->buildInfoUrl())->toArray();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function fetchEmailsSince(?\DateTimeImmutable $since): array
    {
        try {
            $list = $this->httpClient->request('GET', $this->buildMessagesUrl())->toArray();
        } catch (\Throwable $e) {
            $this->logger->error('Mailpit list fetch failed', ['error' => $e->getMessage()]);

            return [];
        }

        if (!isset($list['messages']) || !is_array($list['messages'])) {
            return [];
        }

        $emails = [];
        foreach ($list['messages'] as $message) {
            if (!is_array($message)) {
                continue;
            }

            $id = (string) ($message['id'] ?? '');
            if ('' === $id) {
                continue;
            }

            try {
                $detail = $this->httpClient->request('GET', $this->buildMessageUrl($id))->toArray();
            } catch (\Throwable $e) {
                $this->logger->warning('Mailpit message fetch failed', [
                    'messageId' => $id,
                    'error' => $e->getMessage(),
                ]);

                continue;
            }

            if (null !== $since && !$this->isEmailAfterOrEqualTo((string) ($detail['Date'] ?? 'now'), $since)) {
                continue;
            }

            $emails[] = $this->messageMapper->mapMailpitMessage($detail, $id);
        }

        return $emails;
    }

    public function listAvailableFolders(): array
    {
        return [];
    }

    private function buildInfoUrl(): string
    {
        return rtrim($this->mailpitUrl, '/').'/api/v1/info';
    }

    private function buildMessagesUrl(): string
    {
        return rtrim($this->mailpitUrl, '/').'/api/v1/messages';
    }

    private function buildMessageUrl(string $id): string
    {
        return rtrim($this->mailpitUrl, '/').'/api/v1/message/'.rawurlencode($id);
    }

    private function isEmailAfterOrEqualTo(string $date, \DateTimeImmutable $since): bool
    {
        try {
            return new \DateTimeImmutable($date) >= $since;
        } catch (\Throwable) {
            return true;
        }
    }
}
