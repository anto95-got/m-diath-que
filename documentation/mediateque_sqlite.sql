-- SQLite Version du schema Médiathèque

-- Table des rôles (Admin, Utilisateur, etc.)
CREATE TABLE role (
  id_role INTEGER PRIMARY KEY AUTOINCREMENT,
  nom_role VARCHAR(50) NOT NULL UNIQUE
);

-- Table des utilisateurs
CREATE TABLE utilisateur (
  matricule INTEGER PRIMARY KEY,
  nom VARCHAR(100) NOT NULL,
  prenom VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  id_role INTEGER NOT NULL,
  FOREIGN KEY (id_role) REFERENCES role (id_role)
);

-- Table des états physiques (Neuf, Abîmé, etc.)
CREATE TABLE etat (
  id_etat INTEGER PRIMARY KEY AUTOINCREMENT,
  libelle_etat VARCHAR(50) NOT NULL UNIQUE
);

-- Table des catégories parentes
CREATE TABLE categorie (
  id_categorie INTEGER PRIMARY KEY AUTOINCREMENT,
  nom_categorie VARCHAR(100) NOT NULL UNIQUE
);

-- Table des sous-catégories
CREATE TABLE sous_categorie (
  id_sous_categorie INTEGER PRIMARY KEY AUTOINCREMENT,
  nom_sous_categorie VARCHAR(100) NOT NULL,
  id_categorie INTEGER NOT NULL,
  UNIQUE (nom_sous_categorie, id_categorie),
  FOREIGN KEY (id_categorie) REFERENCES categorie (id_categorie)
);

-- Table des documents (Livres, DVD, etc.)
CREATE TABLE document (
  id_doc INTEGER PRIMARY KEY AUTOINCREMENT,
  titre VARCHAR(255) NOT NULL,
  code_barres VARCHAR(50) NOT NULL UNIQUE,
  disponible INTEGER NOT NULL DEFAULT 1,
  id_etat INTEGER NOT NULL,
  id_sous_categorie INTEGER NOT NULL,
  FOREIGN KEY (id_etat) REFERENCES etat (id_etat),
  FOREIGN KEY (id_sous_categorie) REFERENCES sous_categorie (id_sous_categorie)
);

-- Table des auteurs
CREATE TABLE auteur (
  id_auteur INTEGER PRIMARY KEY AUTOINCREMENT,
  nom_prenom VARCHAR(150) NOT NULL
);

-- Table de liaison Auteurs / Documents (Plusieurs auteurs pour un livre)
CREATE TABLE ecrire (
  id_doc INTEGER NOT NULL,
  id_auteur INTEGER NOT NULL,
  PRIMARY KEY (id_doc, id_auteur),
  FOREIGN KEY (id_doc) REFERENCES document (id_doc) ON DELETE CASCADE,
  FOREIGN KEY (id_auteur) REFERENCES auteur (id_auteur) ON DELETE CASCADE
);

-- Table des emprunts
CREATE TABLE emprunt (
  id_emprunt INTEGER PRIMARY KEY AUTOINCREMENT,
  date_emprunt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_retour_prevue DATETIME NOT NULL,
  date_retour_reelle DATETIME DEFAULT NULL,
  prolonge INTEGER NOT NULL DEFAULT 0,
  matricule INTEGER NOT NULL,
  id_doc INTEGER NOT NULL,
  FOREIGN KEY (matricule) REFERENCES utilisateur (matricule),
  FOREIGN KEY (id_doc) REFERENCES document (id_doc)
);

-- Table des suggestions d'achats (Demandes)
CREATE TABLE demande_document (
  id_demande INTEGER PRIMARY KEY AUTOINCREMENT,
  titre_demande VARCHAR(255) NOT NULL,
  auteur_demande VARCHAR(150) NOT NULL,
  statut_demande VARCHAR(50) NOT NULL DEFAULT 'En attente',
  matricule INTEGER NOT NULL,
  FOREIGN KEY (matricule) REFERENCES utilisateur (matricule)
);
