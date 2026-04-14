<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260224141237 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user_mailbox_settings table for IMAP configuration';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_mailbox_settings (
            id SERIAL PRIMARY KEY,
            user_id INT NOT NULL,
            imap_host VARCHAR(255) NOT NULL,
            imap_port INT DEFAULT 993 NOT NULL,
            imap_encryption VARCHAR(10) DEFAULT \'ssl\' NOT NULL,
            imap_user VARCHAR(255) NOT NULL,
            imap_password VARCHAR(255) NOT NULL,
            imap_folder VARCHAR(50) DEFAULT NULL,
            is_active BOOLEAN DEFAULT true NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            CONSTRAINT FK_user_mailbox_settings_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
            CONSTRAINT UQ_user_mailbox_settings_user UNIQUE (user_id)
        )');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE user_mailbox_settings');
    }
}
