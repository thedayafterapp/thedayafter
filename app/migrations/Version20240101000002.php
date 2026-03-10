<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240101000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add per-track quit dates, costs, motivation, relapse_log';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users
            ADD alcohol_quit_date DATE DEFAULT NULL,
            ADD cigarettes_quit_date DATE DEFAULT NULL,
            ADD alcohol_daily_cost DECIMAL(8,2) DEFAULT NULL,
            ADD cigarettes_daily_cost DECIMAL(8,2) DEFAULT NULL,
            ADD motivation LONGTEXT DEFAULT NULL
        ');

        // Migrate existing quit_date and daily_cost to per-track fields
        $this->addSql("UPDATE users SET
            alcohol_quit_date = quit_date,
            cigarettes_quit_date = quit_date,
            alcohol_daily_cost = CASE WHEN addiction_type IN ('alcohol','both') THEN daily_cost ELSE NULL END,
            cigarettes_daily_cost = CASE WHEN addiction_type IN ('cigarettes','both') THEN daily_cost ELSE NULL END
            WHERE quit_date IS NOT NULL
        ");

        $this->addSql('CREATE TABLE relapse_log (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            addiction_type VARCHAR(20) NOT NULL,
            relapsed_at DATETIME NOT NULL,
            previous_quit_date DATE DEFAULT NULL,
            notes LONGTEXT DEFAULT NULL,
            INDEX IDX_RL_USER (user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE relapse_log ADD CONSTRAINT FK_RL_USER FOREIGN KEY (user_id) REFERENCES users (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE relapse_log');
        $this->addSql('ALTER TABLE users
            DROP alcohol_quit_date,
            DROP cigarettes_quit_date,
            DROP alcohol_daily_cost,
            DROP cigarettes_daily_cost,
            DROP motivation
        ');
    }
}
