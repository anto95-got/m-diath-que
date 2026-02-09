<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260209020000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add quantite_demandee on demande_document and bientot_disponible on document';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE demande_document ADD quantite_demandee INT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE document ADD bientot_disponible TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE demande_document DROP quantite_demandee');
        $this->addSql('ALTER TABLE document DROP bientot_disponible');
    }
}
