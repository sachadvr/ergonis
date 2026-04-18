<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260418161312 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename mailbox and AI log ownership columns from user to owner';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ai_generation_logs DROP CONSTRAINT fk_ai_user');
        $this->addSql('DROP INDEX idx_70cf1573a76ed395');
        $this->addSql('ALTER TABLE ai_generation_logs RENAME COLUMN user_id TO owner_id');
        $this->addSql('ALTER TABLE ai_generation_logs ADD CONSTRAINT FK_70CF15737E3C61F9 FOREIGN KEY (owner_id) REFERENCES users (id) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_70CF15737E3C61F9 ON ai_generation_logs (owner_id)');
        $this->addSql('ALTER TABLE user_mailbox_settings DROP CONSTRAINT fk_23f5ef9fa76ed395');
        $this->addSql('ALTER TABLE user_mailbox_settings DROP CONSTRAINT uniq_23f5ef9fa76ed395');
        $this->addSql('ALTER TABLE user_mailbox_settings RENAME COLUMN user_id TO owner_id');
        $this->addSql('ALTER TABLE user_mailbox_settings ADD CONSTRAINT FK_23F5EF9F7E3C61F9 FOREIGN KEY (owner_id) REFERENCES users (id) NOT DEFERRABLE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_23F5EF9F7E3C61F9 ON user_mailbox_settings (owner_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ai_generation_logs DROP CONSTRAINT FK_70CF15737E3C61F9');
        $this->addSql('DROP INDEX IDX_70CF15737E3C61F9');
        $this->addSql('ALTER TABLE ai_generation_logs RENAME COLUMN owner_id TO user_id');
        $this->addSql('ALTER TABLE ai_generation_logs ADD CONSTRAINT fk_ai_user FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_70cf1573a76ed395 ON ai_generation_logs (user_id)');
        $this->addSql('ALTER TABLE user_mailbox_settings DROP CONSTRAINT FK_23F5EF9F7E3C61F9');
        $this->addSql('ALTER TABLE user_mailbox_settings DROP CONSTRAINT uniq_23f5ef9fa76ed395');
        $this->addSql('ALTER TABLE user_mailbox_settings RENAME COLUMN owner_id TO user_id');
        $this->addSql('ALTER TABLE user_mailbox_settings ADD CONSTRAINT fk_23f5ef9fa76ed395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX uniq_23f5ef9fa76ed395 ON user_mailbox_settings (user_id)');
    }
}
