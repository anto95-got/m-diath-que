<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260209034500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Align demande_document index name for id_doc_demande with ORM expectation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE demande_document RENAME INDEX idx_9e30c3b4e68afd39 TO IDX_9E30C3B4E164928C');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE demande_document RENAME INDEX IDX_9E30C3B4E164928C TO idx_9e30c3b4e68afd39');
    }
}
