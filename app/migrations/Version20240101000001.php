<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240101000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial schema';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE users (
            id INT AUTO_INCREMENT NOT NULL,
            email VARCHAR(180) NOT NULL,
            name VARCHAR(100) NOT NULL,
            roles JSON NOT NULL,
            password VARCHAR(255) NOT NULL,
            addiction_type VARCHAR(20) NOT NULL DEFAULT \'both\',
            quit_date DATE DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            total_xp INT NOT NULL DEFAULT 0,
            cravings_survived INT NOT NULL DEFAULT 0,
            daily_cost DECIMAL(8,2) DEFAULT NULL,
            currency VARCHAR(10) DEFAULT \'USD\',
            UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE achievement (
            id INT AUTO_INCREMENT NOT NULL,
            slug VARCHAR(100) NOT NULL,
            name VARCHAR(100) NOT NULL,
            description VARCHAR(255) NOT NULL,
            icon VARCHAR(10) NOT NULL,
            category VARCHAR(50) NOT NULL,
            requirement_days INT NOT NULL DEFAULT 0,
            requirement_count INT NOT NULL DEFAULT 0,
            xp_reward INT NOT NULL DEFAULT 100,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE user_achievement (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            achievement_id INT NOT NULL,
            earned_at DATETIME NOT NULL,
            INDEX IDX_3F68B664A76ED395 (user_id),
            INDEX IDX_3F68B664B3EC99FE (achievement_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE check_in (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            mood INT NOT NULL DEFAULT 5,
            craving_intensity INT NOT NULL DEFAULT 0,
            triggers JSON NOT NULL,
            notes LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            INDEX IDX_A9E2D3C2A76ED395 (user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE journal_entry (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            title VARCHAR(200) DEFAULT NULL,
            content LONGTEXT NOT NULL,
            mood VARCHAR(20) DEFAULT NULL,
            created_at DATETIME NOT NULL,
            INDEX IDX_57564FBA76ED395 (user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE craving_session (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            started_at DATETIME NOT NULL,
            ended_at DATETIME DEFAULT NULL,
            outcome VARCHAR(20) DEFAULT NULL,
            addiction_type VARCHAR(20) NOT NULL DEFAULT \'alcohol\',
            INDEX IDX_CRAVINGSA76ED395 (user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE chat_message (
            id INT AUTO_INCREMENT NOT NULL,
            session_id INT NOT NULL,
            role VARCHAR(10) NOT NULL,
            content LONGTEXT NOT NULL,
            created_at DATETIME NOT NULL,
            INDEX IDX_CHAT613FECDF (session_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE user_achievement ADD CONSTRAINT FK_UA_USER FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE user_achievement ADD CONSTRAINT FK_UA_ACH FOREIGN KEY (achievement_id) REFERENCES achievement (id)');
        $this->addSql('ALTER TABLE check_in ADD CONSTRAINT FK_CI_USER FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE journal_entry ADD CONSTRAINT FK_JE_USER FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE craving_session ADD CONSTRAINT FK_CS_USER FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE chat_message ADD CONSTRAINT FK_CM_SESSION FOREIGN KEY (session_id) REFERENCES craving_session (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE chat_message');
        $this->addSql('DROP TABLE craving_session');
        $this->addSql('DROP TABLE journal_entry');
        $this->addSql('DROP TABLE check_in');
        $this->addSql('DROP TABLE user_achievement');
        $this->addSql('DROP TABLE achievement');
        $this->addSql('DROP TABLE users');
    }
}
