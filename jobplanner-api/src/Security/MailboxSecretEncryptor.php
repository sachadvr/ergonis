<?php

declare(strict_types=1);

namespace App\Security;

final readonly class MailboxSecretEncryptor
{
    private const PREFIX = 'enc:v1:';

    public function __construct(
        private string $secret,
    ) {
        if ('' === $this->secret) {
            throw new \InvalidArgumentException('A secret is required to encrypt mailbox credentials.');
        }
    }

    public function encrypt(?string $value): ?string
    {
        return self::encryptWithSecret($this->secret, $value);
    }

    public function decrypt(?string $value): ?string
    {
        return self::decryptWithSecret($this->secret, $value);
    }

    public function isEncrypted(string $value): bool
    {
        return str_starts_with($value, self::PREFIX);
    }

    public static function encryptWithSecret(string $secret, ?string $value): ?string
    {
        if (null === $value || self::isEncryptedValue($value)) {
            return $value;
        }

        $key = self::deriveKey($secret);
        $iv = random_bytes(16);

        $ciphertext = openssl_encrypt($value, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        if (false === $ciphertext) {
            throw new \RuntimeException('Failed to encrypt mailbox credential.');
        }

        $mac = hash_hmac('sha256', $iv.$ciphertext, $key, true);

        return self::PREFIX.self::encodeValue($iv.$mac.$ciphertext);
    }

    public static function decryptWithSecret(string $secret, ?string $value): ?string
    {
        if (null === $value || !self::isEncryptedValue($value)) {
            return $value;
        }

        $payload = self::decodeValue(substr($value, \strlen(self::PREFIX)));
        if (false === $payload || 48 > \strlen($payload)) {
            throw new \RuntimeException('Invalid mailbox credential payload.');
        }

        $iv = substr($payload, 0, 16);
        $mac = substr($payload, 16, 32);
        $ciphertext = substr($payload, 48);
        $key = self::deriveKey($secret);
        $expectedMac = hash_hmac('sha256', $iv.$ciphertext, $key, true);

        if (!hash_equals($expectedMac, $mac)) {
            throw new \RuntimeException('Mailbox credential integrity check failed.');
        }

        $plaintext = openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        if (false === $plaintext) {
            throw new \RuntimeException('Failed to decrypt mailbox credential.');
        }

        return $plaintext;
    }

    public static function isEncryptedValue(string $value): bool
    {
        return str_starts_with($value, self::PREFIX);
    }

    private static function deriveKey(string $secret): string
    {
        return hash('sha256', $secret, true);
    }

    private static function encodeValue(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function decodeValue(string $value): string|false
    {
        return base64_decode(strtr($value, '-_', '+/'), true);
    }
}
