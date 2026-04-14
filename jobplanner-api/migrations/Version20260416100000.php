<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260416100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store asynchronous CV fit analysis state on applications';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE applications ADD cv_fit_analysis_status VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE applications ADD cv_fit_analysis_result JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE applications ADD cv_fit_analysis_requested_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE applications ADD cv_fit_analysis_completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE applications DROP cv_fit_analysis_completed_at');
        $this->addSql('ALTER TABLE applications DROP cv_fit_analysis_requested_at');
        $this->addSql('ALTER TABLE applications DROP cv_fit_analysis_result');
        $this->addSql('ALTER TABLE applications DROP cv_fit_analysis_status');
    }
}
