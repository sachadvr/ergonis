<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260413115827 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_mailbox_settings ADD oauth_provider VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE user_mailbox_settings ADD access_token TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE user_mailbox_settings ADD refresh_token TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE user_mailbox_settings ADD token_expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE user_mailbox_settings ALTER imap_host DROP NOT NULL');
        $this->addSql('ALTER TABLE user_mailbox_settings ALTER imap_user DROP NOT NULL');
        $this->addSql('ALTER TABLE user_mailbox_settings ALTER imap_password DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_mailbox_settings DROP oauth_provider');
        $this->addSql('ALTER TABLE user_mailbox_settings DROP access_token');
        $this->addSql('ALTER TABLE user_mailbox_settings DROP refresh_token');
        $this->addSql('ALTER TABLE user_mailbox_settings DROP token_expires_at');
        $this->addSql('ALTER TABLE user_mailbox_settings ALTER imap_host SET NOT NULL');
        $this->addSql('ALTER TABLE user_mailbox_settings ALTER imap_user SET NOT NULL');
        $this->addSql('ALTER TABLE user_mailbox_settings ALTER imap_password SET NOT NULL');
    }
}
