<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260216145050 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER INDEX idx_ai_user RENAME TO IDX_70CF1573A76ED395');
        $this->addSql('ALTER INDEX idx_history_app RENAME TO IDX_CC0475783E030ACD');
        $this->addSql('ALTER INDEX idx_app_job RENAME TO IDX_F7C966F03481D195');
        $this->addSql('ALTER INDEX idx_app_owner RENAME TO IDX_F7C966F07E3C61F9');
        $this->addSql('ALTER INDEX idx_rule_owner RENAME TO IDX_14A502617E3C61F9');
        $this->addSql('ALTER INDEX idx_int_app RENAME TO IDX_3A7526823E030ACD');
        $this->addSql('ALTER TABLE job_offers ADD recruiter_contact_email VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER INDEX idx_offer_owner RENAME TO IDX_8A4229A67E3C61F9');
        $this->addSql('ALTER INDEX uniq_email_msg RENAME TO UNIQ_575DBC68537A1329');
        $this->addSql('ALTER INDEX idx_email_app RENAME TO IDX_575DBC683E030ACD');
        $this->addSql('ALTER INDEX idx_sched_app RENAME TO IDX_7CBE24873E030ACD');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER INDEX idx_70cf1573a76ed395 RENAME TO idx_ai_user');
        $this->addSql('ALTER INDEX idx_cc0475783e030acd RENAME TO idx_history_app');
        $this->addSql('ALTER INDEX idx_f7c966f03481d195 RENAME TO idx_app_job');
        $this->addSql('ALTER INDEX idx_f7c966f07e3c61f9 RENAME TO idx_app_owner');
        $this->addSql('ALTER INDEX idx_14a502617e3c61f9 RENAME TO idx_rule_owner');
        $this->addSql('ALTER INDEX idx_3a7526823e030acd RENAME TO idx_int_app');
        $this->addSql('ALTER TABLE job_offers DROP recruiter_contact_email');
        $this->addSql('ALTER INDEX idx_8a4229a67e3c61f9 RENAME TO idx_offer_owner');
        $this->addSql('ALTER INDEX idx_575dbc683e030acd RENAME TO idx_email_app');
        $this->addSql('ALTER INDEX uniq_575dbc68537a1329 RENAME TO uniq_email_msg');
        $this->addSql('ALTER INDEX idx_7cbe24873e030acd RENAME TO idx_sched_app');
    }
}
