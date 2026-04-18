<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Security\MailboxSecretEncryptor;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260418173000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Encrypt mailbox credentials stored in user_mailbox_settings';
    }

    public function up(Schema $schema): void
    {
        $newSecret = $this->resolveMailboxSecret();
        $legacySecret = $this->resolveLegacySecret();
        $encryptor = new MailboxSecretEncryptor($newSecret);

        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, imap_password, smtp_password, access_token, refresh_token FROM user_mailbox_settings'
        );

        foreach ($rows as $row) {
            $updates = [];

            foreach (['imap_password', 'smtp_password', 'access_token', 'refresh_token'] as $column) {
                $value = $row[$column] ?? null;
                if (null === $value) {
                    continue;
                }

                $plaintext = $value;

                if (MailboxSecretEncryptor::isEncryptedValue($value)) {
                    try {
                        $plaintext = MailboxSecretEncryptor::decryptWithSecret($newSecret, $value);
                    } catch (\RuntimeException $e) {
                        if (null === $legacySecret) {
                            throw $e;
                        }

                        $plaintext = MailboxSecretEncryptor::decryptWithSecret($legacySecret, $value);
                    }
                }

                $updates[$column] = $encryptor->encrypt($plaintext);
            }

            if ([] === $updates) {
                continue;
            }

            $this->connection->update('user_mailbox_settings', $updates, ['id' => $row['id']]);
        }
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(true, 'This migration cannot be reversed safely.');
    }

    private function resolveMailboxSecret(): string
    {
        $secret = $_SERVER['MAILBOX_ENCRYPTION_KEY'] ?? $_ENV['MAILBOX_ENCRYPTION_KEY'] ?? '';

        if ('' === $secret) {
            throw new \RuntimeException('MAILBOX_ENCRYPTION_KEY is required to encrypt mailbox credentials.');
        }

        return $secret;
    }

    private function resolveLegacySecret(): ?string
    {
        $secret = $_SERVER['APP_SECRET'] ?? $_ENV['APP_SECRET'] ?? '';

        return '' === $secret ? null : $secret;
    }
}
