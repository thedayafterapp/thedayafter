<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240101000003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Upgrade quit date columns from DATE to DATETIME for time-of-day tracking';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users
            MODIFY quit_date DATETIME DEFAULT NULL,
            MODIFY alcohol_quit_date DATETIME DEFAULT NULL,
            MODIFY cigarettes_quit_date DATETIME DEFAULT NULL
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users
            MODIFY quit_date DATE DEFAULT NULL,
            MODIFY alcohol_quit_date DATE DEFAULT NULL,
            MODIFY cigarettes_quit_date DATE DEFAULT NULL
        ');
    }
}
