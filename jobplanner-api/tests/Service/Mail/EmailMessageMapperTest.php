<?php

declare(strict_types=1);

namespace App\Tests\Service\Mail;

use App\Service\Mail\EmailMessageMapper;
use PHPUnit\Framework\TestCase;
use Webklex\PHPIMAP\Message;

final class EmailMessageMapperTest extends TestCase
{
    public function testMapReturnsNormalizedPayload(): void
    {
        $message = Message::fromString(<<<'MAIL'
From: Sender <sender@example.com>
To: Recipient 1 <recipient1@example.com>, Recipient 2 <recipient2@example.com>
Subject: Subject
Date: Wed, 15 Apr 2026 10:00:00 +0000
Message-ID: <message-id>

Plain text
MAIL);

        $result = (new EmailMessageMapper())->map($message);

        $this->assertSame('message-id', $result['messageId']);
        $this->assertSame('sender@example.com', $result['fromAddress']);
        $this->assertSame('recipient1@example.com, recipient2@example.com', $result['toAddresses']);
        $this->assertSame('Subject', $result['subject']);
        $this->assertSame('Plain text', $result['textPlain']);
        $this->assertSame('', $result['textHtml']);
        $this->assertSame('2026-04-15 10:00:00', $result['date']);
    }

    public function testMapMailpitMessageReturnsNormalizedPayload(): void
    {
        $result = (new EmailMessageMapper())->mapMailpitMessage([
            'MessageID' => 'mailpit-id',
            'From' => ['Address' => 'sender@example.com'],
            'To' => [
                ['Address' => 'recipient1@example.com'],
                ['Address' => 'recipient2@example.com'],
            ],
            'Subject' => 'Subject',
            'Text' => 'Plain text',
            'HTML' => '<p>HTML</p>',
            'Date' => '2026-04-15 10:00:00',
        ], 'fallback-id');

        $this->assertSame('mailpit-id', $result['messageId']);
        $this->assertSame('sender@example.com', $result['fromAddress']);
        $this->assertSame('recipient1@example.com, recipient2@example.com', $result['toAddresses']);
    }
}
