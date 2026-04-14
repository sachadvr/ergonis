<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260224180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add SMTP columns to user_mailbox_settings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_mailbox_settings ADD smtp_host VARCHAR(255) DEFAULT \'\' NOT NULL');
        $this->addSql('ALTER TABLE user_mailbox_settings ADD smtp_port INT DEFAULT 587 NOT NULL');
        $this->addSql('ALTER TABLE user_mailbox_settings ADD smtp_encryption VARCHAR(10) DEFAULT \'tls\' NOT NULL');
        $this->addSql('ALTER TABLE user_mailbox_settings ADD smtp_user VARCHAR(255) DEFAULT \'\' NOT NULL');
        $this->addSql('ALTER TABLE user_mailbox_settings ADD smtp_password VARCHAR(255) DEFAULT \'\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_mailbox_settings DROP smtp_host');
        $this->addSql('ALTER TABLE user_mailbox_settings DROP smtp_port');
        $this->addSql('ALTER TABLE user_mailbox_settings DROP smtp_encryption');
        $this->addSql('ALTER TABLE user_mailbox_settings DROP smtp_user');
        $this->addSql('ALTER TABLE user_mailbox_settings DROP smtp_password');
    }
}
