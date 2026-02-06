-- 1. Création de la base de données
CREATE DATABASE IF NOT EXISTS `mediateque` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `mediateque`;

-- --------------------------------------------------------
-- STRUCTURE DES TABLES
-- --------------------------------------------------------

-- Table des rôles (Admin, Utilisateur, etc.)
CREATE TABLE `role` (
  `id_role` INT(11) NOT NULL AUTO_INCREMENT,
  `nom_role` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`id_role`),
  UNIQUE KEY `nom_role` (`nom_role`)
) ENGINE=InnoDB;

-- Table des utilisateurs
CREATE TABLE `utilisateur` (
  `matricule` INT(11) NOT NULL,
  `nom` VARCHAR(100) NOT NULL,
  `prenom` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `id_role` INT(11) NOT NULL,
  PRIMARY KEY (`matricule`),
  UNIQUE KEY `email` (`email`),
  CONSTRAINT `fk_utilisateur_role` FOREIGN KEY (`id_role`) REFERENCES `role` (`id_role`)
) ENGINE=InnoDB;

-- Table des états physiques (Neuf, Abîmé, etc.)
CREATE TABLE `etat` (
  `id_etat` INT(11) NOT NULL AUTO_INCREMENT,
  `libelle_etat` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`id_etat`),
  UNIQUE KEY `libelle_etat` (`libelle_etat`)
) ENGINE=InnoDB;

-- Table des catégories parentes
CREATE TABLE `categorie` (
  `id_categorie` INT(11) NOT NULL AUTO_INCREMENT,
  `nom_categorie` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id_categorie`),
  UNIQUE KEY `nom_categorie` (`nom_categorie`)
) ENGINE=InnoDB;

-- Table des sous-catégories
CREATE TABLE `sous_categorie` (
  `id_sous_categorie` INT(11) NOT NULL AUTO_INCREMENT,
  `nom_sous_categorie` VARCHAR(100) NOT NULL,
  `id_categorie` INT(11) NOT NULL,
  PRIMARY KEY (`id_sous_categorie`),
  UNIQUE KEY `uq_sous_categorie` (`nom_sous_categorie`, `id_categorie`),
  CONSTRAINT `fk_sous_categorie_categorie` FOREIGN KEY (`id_categorie`) REFERENCES `categorie` (`id_categorie`)
) ENGINE=InnoDB;

-- Table des documents (Livres, DVD, etc.)
CREATE TABLE `document` (
  `id_doc` INT(11) NOT NULL AUTO_INCREMENT,
  `titre` VARCHAR(255) NOT NULL,
  `code_barres` VARCHAR(50) NOT NULL,
  `disponible` TINYINT(1) NOT NULL DEFAULT '1',
  `id_etat` INT(11) NOT NULL,
  `id_sous_categorie` INT(11) NOT NULL,
  PRIMARY KEY (`id_doc`),
  UNIQUE KEY `code_barres` (`code_barres`),
  CONSTRAINT `fk_document_etat` FOREIGN KEY (`id_etat`) REFERENCES `etat` (`id_etat`),
  CONSTRAINT `fk_document_sous_categorie` FOREIGN KEY (`id_sous_categorie`) REFERENCES `sous_categorie` (`id_sous_categorie`)
) ENGINE=InnoDB;

-- Table des auteurs
CREATE TABLE `auteur` (
  `id_auteur` INT(11) NOT NULL AUTO_INCREMENT,
  `nom_prenom` VARCHAR(150) NOT NULL,
  PRIMARY KEY (`id_auteur`)
) ENGINE=InnoDB;

-- Table de liaison Auteurs / Documents (Plusieurs auteurs pour un livre)
CREATE TABLE `ecrire` (
  `id_doc` INT(11) NOT NULL,
  `id_auteur` INT(11) NOT NULL,
  PRIMARY KEY (`id_doc`, `id_auteur`),
  CONSTRAINT `fk_ecrire_document` FOREIGN KEY (`id_doc`) REFERENCES `document` (`id_doc`) ON DELETE CASCADE,
  CONSTRAINT `fk_ecrire_auteur` FOREIGN KEY (`id_auteur`) REFERENCES `auteur` (`id_auteur`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table des emprunts
CREATE TABLE `emprunt` (
  `id_emprunt` INT(11) NOT NULL AUTO_INCREMENT,
  `date_emprunt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_retour_prevue` DATETIME NOT NULL,
  `date_retour_reelle` DATETIME DEFAULT NULL,
  `prolonge` TINYINT(1) NOT NULL DEFAULT '0',
  `matricule` INT(11) NOT NULL,
  `id_doc` INT(11) NOT NULL,
  PRIMARY KEY (`id_emprunt`),
  CONSTRAINT `fk_emprunt_utilisateur` FOREIGN KEY (`matricule`) REFERENCES `utilisateur` (`matricule`),
  CONSTRAINT `fk_emprunt_document` FOREIGN KEY (`id_doc`) REFERENCES `document` (`id_doc`)
) ENGINE=InnoDB;

-- Table des suggestions d'achats (Demandes)
CREATE TABLE `demande_document` (
  `id_demande` INT(11) NOT NULL AUTO_INCREMENT,
  `titre_demande` VARCHAR(255) NOT NULL,
  `auteur_demande` VARCHAR(150) NOT NULL,
  `statut_demande` VARCHAR(50) NOT NULL DEFAULT 'En attente',
  `matricule` INT(11) NOT NULL,
  PRIMARY KEY (`id_demande`),
  CONSTRAINT `fk_demande_utilisateur` FOREIGN KEY (`matricule`) REFERENCES `utilisateur` (`matricule`)
) ENGINE=InnoDB;