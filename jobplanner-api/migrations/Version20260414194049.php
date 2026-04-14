<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260414194049 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE job_offers ADD job_summary TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE job_offers ADD salary_min NUMERIC(12, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE job_offers ADD salary_max NUMERIC(12, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE job_offers ADD salary_currency VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE job_offers ADD contract_type VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE job_offers ADD remote_policy VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE job_offers ADD details JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE job_offers DROP job_summary');
        $this->addSql('ALTER TABLE job_offers DROP salary_min');
        $this->addSql('ALTER TABLE job_offers DROP salary_max');
        $this->addSql('ALTER TABLE job_offers DROP salary_currency');
        $this->addSql('ALTER TABLE job_offers DROP contract_type');
        $this->addSql('ALTER TABLE job_offers DROP remote_policy');
        $this->addSql('ALTER TABLE job_offers DROP details');
    }
}
