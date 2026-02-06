<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260202111914 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE auteur (id_auteur INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nom_prenom VARCHAR(150) NOT NULL)');
        $this->addSql('CREATE TABLE categorie (id_categorie INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nom_categorie VARCHAR(100) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_497DD634DD8CA775 ON categorie (nom_categorie)');
        $this->addSql('CREATE TABLE demande_document (id_demande INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, titre_demande VARCHAR(255) NOT NULL, auteur_demande VARCHAR(150) NOT NULL, statut_demande VARCHAR(50) NOT NULL, matricule INTEGER NOT NULL, CONSTRAINT FK_9E30C3B412B2DC9C FOREIGN KEY (matricule) REFERENCES utilisateur (matricule) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_9E30C3B412B2DC9C ON demande_document (matricule)');
        $this->addSql('CREATE TABLE document (id_doc INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, code_barres VARCHAR(50) NOT NULL, disponible INTEGER NOT NULL, id_etat INTEGER NOT NULL, id_sous_categorie INTEGER NOT NULL, CONSTRAINT FK_D8698A76DEEAEB60 FOREIGN KEY (id_etat) REFERENCES etat (id_etat) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_D8698A766F12807D FOREIGN KEY (id_sous_categorie) REFERENCES sous_categorie (id_sous_categorie) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D8698A7668EF62E0 ON document (code_barres)');
        $this->addSql('CREATE INDEX IDX_D8698A76DEEAEB60 ON document (id_etat)');
        $this->addSql('CREATE INDEX IDX_D8698A766F12807D ON document (id_sous_categorie)');
        $this->addSql('CREATE TABLE ecrire (id_doc INTEGER NOT NULL, id_auteur INTEGER NOT NULL, PRIMARY KEY (id_doc, id_auteur), CONSTRAINT FK_918824CC18E5153E FOREIGN KEY (id_doc) REFERENCES document (id_doc) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_918824CC236D04AD FOREIGN KEY (id_auteur) REFERENCES auteur (id_auteur) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_918824CC18E5153E ON ecrire (id_doc)');
        $this->addSql('CREATE INDEX IDX_918824CC236D04AD ON ecrire (id_auteur)');
        $this->addSql('CREATE TABLE emprunt (id_emprunt INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, date_emprunt DATETIME NOT NULL, date_retour_prevue DATETIME NOT NULL, date_retour_reelle DATETIME DEFAULT NULL, prolonge INTEGER NOT NULL, matricule INTEGER NOT NULL, id_doc INTEGER NOT NULL, CONSTRAINT FK_364071D712B2DC9C FOREIGN KEY (matricule) REFERENCES utilisateur (matricule) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_364071D718E5153E FOREIGN KEY (id_doc) REFERENCES document (id_doc) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_364071D712B2DC9C ON emprunt (matricule)');
        $this->addSql('CREATE INDEX IDX_364071D718E5153E ON emprunt (id_doc)');
        $this->addSql('CREATE TABLE etat (id_etat INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, libelle_etat VARCHAR(50) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_55CAF762E3542215 ON etat (libelle_etat)');
        $this->addSql('CREATE TABLE role (id_role INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nom_role VARCHAR(50) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_57698A6AA5B94004 ON role (nom_role)');
        $this->addSql('CREATE TABLE sous_categorie (id_sous_categorie INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nom_sous_categorie VARCHAR(100) NOT NULL, id_categorie INTEGER NOT NULL, CONSTRAINT FK_52743D7BC9486A13 FOREIGN KEY (id_categorie) REFERENCES categorie (id_categorie) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_52743D7BC9486A13 ON sous_categorie (id_categorie)');
        $this->addSql('CREATE TABLE utilisateur (matricule INTEGER NOT NULL, nom VARCHAR(100) NOT NULL, prenom VARCHAR(100) NOT NULL, email VARCHAR(150) NOT NULL, password VARCHAR(255) DEFAULT NULL, confirmation_code VARCHAR(10) DEFAULT NULL, is_verified BOOLEAN DEFAULT 0 NOT NULL, id_role INTEGER NOT NULL, PRIMARY KEY (matricule), CONSTRAINT FK_1D1C63B3DC499668 FOREIGN KEY (id_role) REFERENCES role (id_role) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1D1C63B3E7927C74 ON utilisateur (email)');
        $this->addSql('CREATE INDEX IDX_1D1C63B3DC499668 ON utilisateur (id_role)');
        $this->addSql('CREATE TABLE messenger_messages (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, body CLOB NOT NULL, headers CLOB NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL)');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages (queue_name, available_at, delivered_at, id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE auteur');
        $this->addSql('DROP TABLE categorie');
        $this->addSql('DROP TABLE demande_document');
        $this->addSql('DROP TABLE document');
        $this->addSql('DROP TABLE ecrire');
        $this->addSql('DROP TABLE emprunt');
        $this->addSql('DROP TABLE etat');
        $this->addSql('DROP TABLE role');
        $this->addSql('DROP TABLE sous_categorie');
        $this->addSql('DROP TABLE utilisateur');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
