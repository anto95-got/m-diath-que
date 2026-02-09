<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260208234000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add return condition and comment fields to emprunt';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE emprunt ADD etat_retour VARCHAR(30) DEFAULT NULL, ADD commentaire_retour LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE emprunt DROP etat_retour, DROP commentaire_retour');
    }
}

