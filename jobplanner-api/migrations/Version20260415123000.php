<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260415123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add seen state to recruiter emails';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE recruiter_emails ADD is_seen BOOLEAN NOT NULL DEFAULT FALSE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE recruiter_emails DROP is_seen');
    }
}
