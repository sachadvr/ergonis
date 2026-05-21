<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260521120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add follow_ups_enabled flag on applications and dedupe follow_up_rules per user';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE applications ADD COLUMN follow_ups_enabled BOOLEAN NOT NULL DEFAULT TRUE');

        $this->addSql(<<<'SQL'
            DELETE FROM follow_up_rules
            WHERE id NOT IN (
                SELECT min_id FROM (
                    SELECT MIN(id) AS min_id
                    FROM follow_up_rules
                    GROUP BY owner_id
                ) AS keepers
            )
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE applications DROP COLUMN follow_ups_enabled');
    }
}
