<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260202153522 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE categorie RENAME INDEX nom_categorie TO UNIQ_497DD634DD8CA775');
        $this->addSql('ALTER TABLE demande_document ADD type_demande VARCHAR(20) DEFAULT \'reservation\' NOT NULL, CHANGE statut_demande statut_demande VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE demande_document ADD CONSTRAINT FK_9E30C3B412B2DC9C FOREIGN KEY (matricule) REFERENCES utilisateur (matricule)');
        $this->addSql('ALTER TABLE demande_document RENAME INDEX fk_demande_utilisateur TO IDX_9E30C3B412B2DC9C');
        $this->addSql('ALTER TABLE document RENAME INDEX code_barres TO UNIQ_D8698A7668EF62E0');
        $this->addSql('ALTER TABLE document RENAME INDEX fk_document_etat TO IDX_D8698A76DEEAEB60');
        $this->addSql('ALTER TABLE document RENAME INDEX fk_document_sous_categorie TO IDX_D8698A766F12807D');
        $this->addSql('ALTER TABLE ecrire DROP FOREIGN KEY `fk_ecrire_auteur`');
        $this->addSql('ALTER TABLE ecrire DROP FOREIGN KEY `fk_ecrire_document`');
        $this->addSql('ALTER TABLE ecrire ADD CONSTRAINT FK_918824CC18E5153E FOREIGN KEY (id_doc) REFERENCES document (id_doc)');
        $this->addSql('ALTER TABLE ecrire ADD CONSTRAINT FK_918824CC236D04AD FOREIGN KEY (id_auteur) REFERENCES auteur (id_auteur)');
        $this->addSql('ALTER TABLE ecrire RENAME INDEX fk_ecrire_auteur TO IDX_918824CC236D04AD');
        $this->addSql('ALTER TABLE emprunt CHANGE date_emprunt date_emprunt DATETIME NOT NULL');
        $this->addSql('ALTER TABLE emprunt ADD CONSTRAINT FK_364071D712B2DC9C FOREIGN KEY (matricule) REFERENCES utilisateur (matricule)');
        $this->addSql('ALTER TABLE emprunt RENAME INDEX fk_emprunt_utilisateur TO IDX_364071D712B2DC9C');
        $this->addSql('ALTER TABLE emprunt RENAME INDEX fk_emprunt_document TO IDX_364071D718E5153E');
        $this->addSql('ALTER TABLE etat RENAME INDEX libelle_etat TO UNIQ_55CAF762E3542215');
        $this->addSql('ALTER TABLE role RENAME INDEX nom_role TO UNIQ_57698A6AA5B94004');
        $this->addSql('DROP INDEX uq_sous_categorie ON sous_categorie');
        $this->addSql('ALTER TABLE sous_categorie RENAME INDEX fk_sous_categorie_categorie TO IDX_52743D7BC9486A13');
        $this->addSql('ALTER TABLE utilisateur CHANGE id_role id_role INT NOT NULL');
        $this->addSql('ALTER TABLE utilisateur RENAME INDEX email TO UNIQ_1D1C63B3E7927C74');
        $this->addSql('ALTER TABLE utilisateur RENAME INDEX fk_utilisateur_role TO IDX_1D1C63B3DC499668');
        $this->addSql('DROP INDEX IDX_75EA56E0E3BD61CE ON messenger_messages');
        $this->addSql('DROP INDEX IDX_75EA56E0FB367447 ON messenger_messages');
        $this->addSql('DROP INDEX IDX_75EA56E016BA31DB ON messenger_messages');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages (queue_name, available_at, delivered_at, id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE categorie RENAME INDEX uniq_497dd634dd8ca775 TO nom_categorie');
        $this->addSql('ALTER TABLE demande_document DROP FOREIGN KEY FK_9E30C3B412B2DC9C');
        $this->addSql('ALTER TABLE demande_document DROP type_demande, CHANGE statut_demande statut_demande VARCHAR(50) DEFAULT \'En attente\' NOT NULL');
        $this->addSql('ALTER TABLE demande_document RENAME INDEX idx_9e30c3b412b2dc9c TO fk_demande_utilisateur');
        $this->addSql('ALTER TABLE document RENAME INDEX idx_d8698a76deeaeb60 TO fk_document_etat');
        $this->addSql('ALTER TABLE document RENAME INDEX uniq_d8698a7668ef62e0 TO code_barres');
        $this->addSql('ALTER TABLE document RENAME INDEX idx_d8698a766f12807d TO fk_document_sous_categorie');
        $this->addSql('ALTER TABLE ecrire DROP FOREIGN KEY FK_918824CC18E5153E');
        $this->addSql('ALTER TABLE ecrire DROP FOREIGN KEY FK_918824CC236D04AD');
        $this->addSql('ALTER TABLE ecrire ADD CONSTRAINT `fk_ecrire_auteur` FOREIGN KEY (id_auteur) REFERENCES auteur (id_auteur) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ecrire ADD CONSTRAINT `fk_ecrire_document` FOREIGN KEY (id_doc) REFERENCES document (id_doc) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ecrire RENAME INDEX idx_918824cc236d04ad TO fk_ecrire_auteur');
        $this->addSql('ALTER TABLE emprunt DROP FOREIGN KEY FK_364071D712B2DC9C');
        $this->addSql('ALTER TABLE emprunt CHANGE date_emprunt date_emprunt DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE emprunt RENAME INDEX idx_364071d712b2dc9c TO fk_emprunt_utilisateur');
        $this->addSql('ALTER TABLE emprunt RENAME INDEX idx_364071d718e5153e TO fk_emprunt_document');
        $this->addSql('ALTER TABLE etat RENAME INDEX uniq_55caf762e3542215 TO libelle_etat');
        $this->addSql('DROP INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB367447 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        $this->addSql('ALTER TABLE role RENAME INDEX uniq_57698a6aa5b94004 TO nom_role');
        $this->addSql('CREATE UNIQUE INDEX uq_sous_categorie ON sous_categorie (nom_sous_categorie, id_categorie)');
        $this->addSql('ALTER TABLE sous_categorie RENAME INDEX idx_52743d7bc9486a13 TO fk_sous_categorie_categorie');
        $this->addSql('ALTER TABLE utilisateur CHANGE id_role id_role INT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE utilisateur RENAME INDEX uniq_1d1c63b3e7927c74 TO email');
        $this->addSql('ALTER TABLE utilisateur RENAME INDEX idx_1d1c63b3dc499668 TO fk_utilisateur_role');
    }
}
