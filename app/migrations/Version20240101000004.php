<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240101000004 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add timezone field to users';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE users ADD timezone VARCHAR(50) NOT NULL DEFAULT 'UTC'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP timezone');
    }
}
