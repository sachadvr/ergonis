<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260415133000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add last synced timestamp to mailbox settings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_mailbox_settings ADD last_synced_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_mailbox_settings DROP last_synced_at');
    }
}
