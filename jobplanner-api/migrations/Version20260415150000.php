<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260415150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Link recruiter emails to their owning user';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE recruiter_emails ADD owner_id INT DEFAULT NULL');
        $this->addSql('DROP INDEX uniq_575dbc68537a1329');
        $this->addSql('CREATE INDEX IDX_3B10F4E96B9B1C47 ON recruiter_emails (owner_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_RECRUITER_EMAIL_MESSAGE_OWNER ON recruiter_emails (message_id, owner_id)');
        $this->addSql('ALTER TABLE recruiter_emails ADD CONSTRAINT FK_3B10F4E96B9B1C47 FOREIGN KEY (owner_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('UPDATE recruiter_emails re SET owner_id = a.owner_id FROM applications a WHERE re.application_id = a.id AND re.owner_id IS NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE recruiter_emails DROP CONSTRAINT FK_3B10F4E96B9B1C47');
        $this->addSql('DROP INDEX UNIQ_RECRUITER_EMAIL_MESSAGE_OWNER');
        $this->addSql('DROP INDEX IDX_3B10F4E96B9B1C47');
        $this->addSql('CREATE UNIQUE INDEX uniq_575dbc68537a1329 ON recruiter_emails (message_id)');
        $this->addSql('ALTER TABLE recruiter_emails DROP owner_id');
    }
}
