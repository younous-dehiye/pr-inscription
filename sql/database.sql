-- Création de la base de données
CREATE DATABASE IF NOT EXISTS univ_ngaoundere;
USE univ_ngaoundere;

-- Table des étudiants
CREATE TABLE etudiants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    sexe ENUM('M', 'F') NOT NULL,
    nationalite VARCHAR(100) NOT NULL,
    date_naissance DATE NOT NULL,
    lieu_naissance VARCHAR(200) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    telephone VARCHAR(20) NOT NULL,
    bac_serie VARCHAR(10) NOT NULL,
    bac_annee INT NOT NULL,
    bac_mention VARCHAR(50) NOT NULL,
    date_inscription TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des facultés/écoles
CREATE TABLE facultes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    type ENUM('Faculté', 'École') NOT NULL,
    code VARCHAR(20) UNIQUE NOT NULL
);

-- Table des départements
CREATE TABLE departements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    faculte_id INT,
    FOREIGN KEY (faculte_id) REFERENCES facultes(id) ON DELETE CASCADE
);

-- Table des filières
CREATE TABLE filieres (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(150) NOT NULL,
    departement_id INT,
    code VARCHAR(20) UNIQUE,
    FOREIGN KEY (departement_id) REFERENCES departements(id) ON DELETE CASCADE
);

-- Table des inscriptions
CREATE TABLE inscriptions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    etudiant_id INT,
    filiere_id INT,
    statut ENUM('En attente', 'Validée', 'Rejetée') DEFAULT 'En attente',
    date_inscription TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (etudiant_id) REFERENCES etudiants(id) ON DELETE CASCADE,
    FOREIGN KEY (filiere_id) REFERENCES filieres(id) ON DELETE CASCADE
);

-- Table des paiements
CREATE TABLE paiements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    inscription_id INT,
    mode_paiement ENUM('Express Union', 'CCA Bank') NOT NULL,
    reference VARCHAR(100) NOT NULL,
    montant DECIMAL(10,2) DEFAULT 5000.00,
    statut ENUM('En attente', 'Confirmé') DEFAULT 'En attente',
    date_paiement TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inscription_id) REFERENCES inscriptions(id) ON DELETE CASCADE
);

-- Insertion des données des facultés/écoles
INSERT INTO facultes (nom, type, code) VALUES
('Faculté des Sciences', 'Faculté', 'FS'),
('Faculté des Sciences Juridiques et Politiques', 'Faculté', 'FSJP'),
('Faculté des Sciences Economiques et de Gestion', 'Faculté', 'FSEG'),
('Faculté des Arts, Lettres et Sciences Humaines', 'Faculté', 'FALSH'),
('Faculté des Sciences de l\'Education', 'Faculté', 'FSE'),
('Ecole Nationale Supérieure des Agro-Industries', 'École', 'ENSAI'),
('Institut Universitaire de Technologie', 'École', 'IUT');

-- Insertion des départements et filières
INSERT INTO departements (nom, faculte_id) VALUES
('Mathématiques et Informatique', 1),
('Physique', 1),
('Chimie', 1);

INSERT INTO filieres (nom, departement_id, code) VALUES
('Mathématiques', 1, 'MATH'),
('Informatique', 1, 'INFO'),
('Physique Fondamentale', 2, 'PHY'),
('Chimie Organique', 3, 'CHIM');

-- Ajout d'autres filières selon les facultés
INSERT INTO departements (nom, faculte_id) VALUES
('Droit Privé', 2),
('Droit Public', 2);

INSERT INTO filieres (nom, departement_id, code) VALUES
('Droit des Affaires', 4, 'DAFF'),
('Droit International', 5, 'DINT');