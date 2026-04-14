<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260224150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add RecruiterEmail columns: direction, is_favourite, is_deleted, is_draft, labels';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE recruiter_emails ADD direction VARCHAR(20) DEFAULT \'INCOMING\' NOT NULL');
        $this->addSql('ALTER TABLE recruiter_emails ADD is_favourite BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE recruiter_emails ADD is_deleted BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE recruiter_emails ADD is_draft BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE recruiter_emails ADD labels JSON DEFAULT \'[]\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE recruiter_emails DROP direction');
        $this->addSql('ALTER TABLE recruiter_emails DROP is_favourite');
        $this->addSql('ALTER TABLE recruiter_emails DROP is_deleted');
        $this->addSql('ALTER TABLE recruiter_emails DROP is_draft');
        $this->addSql('ALTER TABLE recruiter_emails DROP labels');
    }
}
