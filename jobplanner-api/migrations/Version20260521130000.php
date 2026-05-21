<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260521130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add template_type column on scheduled_follow_ups';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE scheduled_follow_ups ADD COLUMN template_type VARCHAR(50) NOT NULL DEFAULT 'follow_up'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE scheduled_follow_ups DROP COLUMN template_type');
    }
}
