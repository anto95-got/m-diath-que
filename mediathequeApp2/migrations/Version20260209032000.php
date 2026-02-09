<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260209032000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Link reservation demande to exact document copy and drop old title-based reservation triggers';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE demande_document ADD id_doc_demande INT DEFAULT NULL');
        $this->addSql('ALTER TABLE demande_document ADD CONSTRAINT FK_9E30C3B4E68AFD39 FOREIGN KEY (id_doc_demande) REFERENCES document (id_doc) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_9E30C3B4E68AFD39 ON demande_document (id_doc_demande)');

        if (str_contains($this->connection->getDatabasePlatform()::class, 'MySQL')) {
            $this->addSql('DROP TRIGGER IF EXISTS trg_demande_document_no_duplicate_active_insert');
            $this->addSql('DROP TRIGGER IF EXISTS trg_demande_document_no_duplicate_active_update');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE demande_document DROP FOREIGN KEY FK_9E30C3B4E68AFD39');
        $this->addSql('DROP INDEX IDX_9E30C3B4E68AFD39 ON demande_document');
        $this->addSql('ALTER TABLE demande_document DROP id_doc_demande');
    }
}
