<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260208235500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Strengthen demande_document triggers: one reservation request per user and title';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!str_contains($this->connection->getDatabasePlatform()::class, 'MySQL'), 'This migration only supports MySQL.');

        $this->addSql('DROP TRIGGER IF EXISTS trg_demande_document_no_duplicate_active_insert');
        $this->addSql('DROP TRIGGER IF EXISTS trg_demande_document_no_duplicate_active_update');

        $this->addSql(<<<'SQL'
CREATE TRIGGER trg_demande_document_no_duplicate_active_insert
BEFORE INSERT ON demande_document
FOR EACH ROW
BEGIN
    IF NEW.type_demande = 'reservation'
       AND EXISTS (
           SELECT 1
           FROM demande_document d
           WHERE d.type_demande = 'reservation'
             AND d.id_utilisateur = NEW.id_utilisateur
             AND LOWER(TRIM(d.titre_demande)) = LOWER(TRIM(NEW.titre_demande))
       )
    THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Un utilisateur ne peut demander qu une seule fois ce document';
    END IF;

    IF NEW.type_demande = 'reservation'
       AND NEW.statut_demande IN ('En attente', 'Réservé', 'Emprunté')
       AND EXISTS (
           SELECT 1
           FROM demande_document d
           WHERE d.type_demande = 'reservation'
             AND d.statut_demande IN ('En attente', 'Réservé', 'Emprunté')
             AND LOWER(TRIM(d.titre_demande)) = LOWER(TRIM(NEW.titre_demande))
       )
    THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Une demande active existe deja pour ce document';
    END IF;
END
SQL);

        $this->addSql(<<<'SQL'
CREATE TRIGGER trg_demande_document_no_duplicate_active_update
BEFORE UPDATE ON demande_document
FOR EACH ROW
BEGIN
    IF NEW.type_demande = 'reservation'
       AND EXISTS (
           SELECT 1
           FROM demande_document d
           WHERE d.id_demande <> NEW.id_demande
             AND d.type_demande = 'reservation'
             AND d.id_utilisateur = NEW.id_utilisateur
             AND LOWER(TRIM(d.titre_demande)) = LOWER(TRIM(NEW.titre_demande))
       )
    THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Un utilisateur ne peut demander qu une seule fois ce document';
    END IF;

    IF NEW.type_demande = 'reservation'
       AND NEW.statut_demande IN ('En attente', 'Réservé', 'Emprunté')
       AND EXISTS (
           SELECT 1
           FROM demande_document d
           WHERE d.id_demande <> NEW.id_demande
             AND d.type_demande = 'reservation'
             AND d.statut_demande IN ('En attente', 'Réservé', 'Emprunté')
             AND LOWER(TRIM(d.titre_demande)) = LOWER(TRIM(NEW.titre_demande))
       )
    THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Une demande active existe deja pour ce document';
    END IF;
END
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(!str_contains($this->connection->getDatabasePlatform()::class, 'MySQL'), 'This migration only supports MySQL.');

        $this->addSql('DROP TRIGGER IF EXISTS trg_demande_document_no_duplicate_active_insert');
        $this->addSql('DROP TRIGGER IF EXISTS trg_demande_document_no_duplicate_active_update');
    }
}

