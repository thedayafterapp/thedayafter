<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240101000009 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE mood_log (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            mood INT DEFAULT 5 NOT NULL,
            note LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            INDEX IDX_mood_log_user (user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE mood_log ADD CONSTRAINT FK_mood_log_user FOREIGN KEY (user_id) REFERENCES `user`(id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mood_log DROP FOREIGN KEY FK_mood_log_user');
        $this->addSql('DROP TABLE mood_log');
    }
}
