<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Security\MailboxSecretEncryptor;
use PHPUnit\Framework\TestCase;

final class MailboxSecretEncryptorTest extends TestCase
{
    public function testEncryptsAndDecryptsValues(): void
    {
        $encryptor = new MailboxSecretEncryptor('test-secret');

        $ciphertext = $encryptor->encrypt('mailbox-password');

        $this->assertNotSame('mailbox-password', $ciphertext);
        $this->assertSame('mailbox-password', $encryptor->decrypt($ciphertext));
    }

    public function testLeavesLegacyPlaintextUntouchedWhenDecrypting(): void
    {
        $encryptor = new MailboxSecretEncryptor('test-secret');

        $this->assertSame('legacy-password', $encryptor->decrypt('legacy-password'));
    }

    public function testEncryptIsIdempotent(): void
    {
        $encryptor = new MailboxSecretEncryptor('test-secret');

        $ciphertext = $encryptor->encrypt('mailbox-password');

        $this->assertSame($ciphertext, $encryptor->encrypt($ciphertext));
    }
}
