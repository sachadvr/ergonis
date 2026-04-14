<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260418133000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Track whether application history entries were seen';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE application_history ADD is_seen BOOLEAN DEFAULT FALSE NOT NULL');
        $this->addSql('UPDATE application_history SET is_seen = TRUE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE application_history DROP is_seen');
    }
}
