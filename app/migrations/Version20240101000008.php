<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240101000008 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add cannabis track (quit date + daily cost)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD cannabis_quit_date DATETIME DEFAULT NULL, ADD cannabis_daily_cost DECIMAL(8,2) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP cannabis_quit_date, DROP cannabis_daily_cost');
    }
}
