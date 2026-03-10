<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240101000005 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add forum (posts, replies) and feedback tables; forum_username on users';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD forum_username VARCHAR(30) DEFAULT NULL UNIQUE');

        $this->addSql('CREATE TABLE forum_post (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            category VARCHAR(20) NOT NULL DEFAULT \'general\',
            title VARCHAR(150) NOT NULL,
            body LONGTEXT NOT NULL,
            is_flagged TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            INDEX IDX_FP_USER (user_id),
            INDEX IDX_FP_CAT (category),
            CONSTRAINT FK_FP_USER FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE forum_reply (
            id INT AUTO_INCREMENT NOT NULL,
            post_id INT NOT NULL,
            user_id INT NOT NULL,
            body LONGTEXT NOT NULL,
            is_flagged TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            INDEX IDX_FR_POST (post_id),
            INDEX IDX_FR_USER (user_id),
            CONSTRAINT FK_FR_POST FOREIGN KEY (post_id) REFERENCES forum_post(id) ON DELETE CASCADE,
            CONSTRAINT FK_FR_USER FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE feedback (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT DEFAULT NULL,
            category VARCHAR(30) NOT NULL DEFAULT \'general\',
            message LONGTEXT NOT NULL,
            created_at DATETIME NOT NULL,
            INDEX IDX_FB_USER (user_id),
            CONSTRAINT FK_FB_USER FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE forum_reply');
        $this->addSql('DROP TABLE forum_post');
        $this->addSql('DROP TABLE feedback');
        $this->addSql('ALTER TABLE users DROP forum_username');
    }
}
