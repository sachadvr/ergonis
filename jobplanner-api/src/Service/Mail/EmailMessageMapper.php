<?php

declare(strict_types=1);

namespace App\Service\Mail;

use Webklex\PHPIMAP\Message;

final class EmailMessageMapper
{
    /**
     * @return array<string, mixed>
     */
    public function map(Message $message): array
    {
        $fromAttr = $message->getFrom();
        $toAttr = $message->getTo();

        $toAddresses = [];
        if (null !== $toAttr) {
            foreach ($toAttr->all() as $addr) {
                $toAddresses[] = $this->extractAddressFromAttribute($addr);
            }
        }

        return [
            'messageId' => (string) ($message->getMessageId() ?: $message->getUid()),
            'fromAddress' => $this->extractAddressFromAttribute($fromAttr?->first()),
            'toAddresses' => implode(', ', array_filter($toAddresses, static fn (string $address): bool => '' !== $address)),
            'subject' => (string) $message->getSubject(),
            'textPlain' => $message->getTextBody() ?: '',
            'textHtml' => $message->getHTMLBody() ?: '',
            'date' => (string) $message->getDate(),
        ];
    }

    /**
     * @param array<string, mixed> $message
     *
     * @return array<string, mixed>
     */
    public function mapMailpitMessage(array $message, string $fallbackId): array
    {
        return [
            'messageId' => (string) ($message['MessageID'] ?? $fallbackId),
            'fromAddress' => (string) ($message['From']['Address'] ?? ''),
            'toAddresses' => $this->formatRecipients($message['To'] ?? []),
            'subject' => (string) ($message['Subject'] ?? ''),
            'textPlain' => (string) ($message['Text'] ?? ''),
            'textHtml' => (string) ($message['HTML'] ?? ''),
            'date' => (string) ($message['Date'] ?? 'now'),
        ];
    }

    private function extractAddressFromAttribute(mixed $address): string
    {
        if (null === $address) {
            return '';
        }

        if (is_object($address)) {
            return $address->mail ?? ($address->email ?? '');
        }

        return (string) $address;
    }

    private function formatRecipients(mixed $recipients): string
    {
        if (!is_array($recipients)) {
            return '';
        }

        $addresses = [];
        foreach ($recipients as $recipient) {
            if (!is_array($recipient)) {
                continue;
            }

            $address = trim((string) ($recipient['Address'] ?? ''));
            if ('' !== $address) {
                $addresses[] = $address;
            }
        }

        return implode(', ', $addresses);
    }
}
