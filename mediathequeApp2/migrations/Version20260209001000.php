<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260209001000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add password reset code fields on utilisateur';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur ADD password_reset_code VARCHAR(6) DEFAULT NULL');
        $this->addSql('ALTER TABLE utilisateur ADD password_reset_expires_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur DROP password_reset_code');
        $this->addSql('ALTER TABLE utilisateur DROP password_reset_expires_at');
    }
}

