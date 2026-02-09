<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260208093000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add refusal reason field to demande_document';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE demande_document ADD motif_refus LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE demande_document DROP motif_refus');
    }
}

