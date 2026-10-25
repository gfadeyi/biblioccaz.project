-- =====================================================================
-- BIBLIOccaz - Base de données pour une installation LOCALE (XAMPP)
-- Reconstituée à partir des requêtes SQL du code (l'ancienne base
-- était sur le VPS et n'est pas dans le dépôt GitHub).
-- À importer dans phpMyAdmin (onglet "Importer") ou avec :
--   mysql -u root < biblioccaz_local.sql
-- =====================================================================

CREATE DATABASE IF NOT EXISTS biblioccaz
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE biblioccaz;

-- Utilisateur MySQL du site : mets le même mot de passe dans secrets.php (DB_PASS)
CREATE USER IF NOT EXISTS 'admin_biblio'@'localhost' IDENTIFIED BY 'changez_moi';
GRANT ALL PRIVILEGES ON biblioccaz.* TO 'admin_biblio'@'localhost';
FLUSH PRIVILEGES;

-- ---------------------------------------------------------------------
-- Utilisateurs
-- role   : admin | moderateur | gestionnaire | auteur | client | user
-- statut : actif | en_attente_moderateur | valide_moderateur
--          | refuse_temporaire | refuse_definitif
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user` (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  pseudo          VARCHAR(50)  NOT NULL UNIQUE,
  email           VARCHAR(150) NOT NULL UNIQUE,
  mot_de_passe    VARCHAR(255) NOT NULL,
  nom             VARCHAR(100) NULL,
  prenom          VARCHAR(100) NULL,
  role            VARCHAR(30)  NOT NULL DEFAULT 'client',
  statut          VARCHAR(30)  NOT NULL DEFAULT 'actif',
  email_token     VARCHAR(255) NULL,
  email_verifie   TINYINT(1)   NOT NULL DEFAULT 0,
  est_verifie     TINYINT(1)   NOT NULL DEFAULT 0,
  is_newsletter   TINYINT(1)   NOT NULL DEFAULT 0,
  date_newsletter DATETIME     NULL,
  last_activity   DATETIME     NULL,
  ville           VARCHAR(100) NULL,
  cp              VARCHAR(10)  NULL,
  solde_points    INT          NOT NULL DEFAULT 0,
  date_inscription DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Livres (fiches catalogue)
-- is_valide = 0 : proposé par un auteur, en attente de modération
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS livre (
  id_livre        INT AUTO_INCREMENT PRIMARY KEY,
  titre           VARCHAR(255) NOT NULL,
  auteur          VARCHAR(255) NULL,
  description     TEXT         NULL,
  couverture      VARCHAR(255) NULL,
  isbn            VARCHAR(20)  NULL,
  editeur         VARCHAR(150) NULL,
  annee_parution  INT          NULL,
  nb_pages        INT          NULL,
  poids           VARCHAR(50)  NULL,
  dimensions      VARCHAR(100) NULL,
  reliure         VARCHAR(50)  NULL,
  prix            DECIMAL(8,2) NULL,
  is_valide       TINYINT(1)   NOT NULL DEFAULT 1,
  id_user_auteur  INT          NULL,
  CONSTRAINT fk_livre_auteur FOREIGN KEY (id_user_auteur)
    REFERENCES `user`(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Exemplaires (stock d'occasion d'un livre : état, prix, quantité)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS exemplaire (
  id_exemplaire   INT AUTO_INCREMENT PRIMARY KEY,
  id_livre        INT          NOT NULL,
  etat            VARCHAR(50)  NOT NULL,
  prix            DECIMAL(8,2) NOT NULL,
  type_offre      VARCHAR(30)  NOT NULL DEFAULT 'vente',
  is_disponible   TINYINT(1)   NOT NULL DEFAULT 1,
  quantite        INT          NOT NULL DEFAULT 1,
  id_user         INT          NULL,
  CONSTRAINT fk_exemplaire_livre FOREIGN KEY (id_livre)
    REFERENCES livre(id_livre) ON DELETE CASCADE,
  CONSTRAINT fk_exemplaire_user FOREIGN KEY (id_user)
    REFERENCES `user`(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Logs (le code utilise deux formats de colonnes, tous gardés ici)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS logs (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  action_type     VARCHAR(50)  NULL,
  description     TEXT         NULL,
  action          VARCHAR(100) NULL,
  details         TEXT         NULL,
  date_action     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  adresse_ip      VARCHAR(45)  NULL,
  id_user         INT          NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Commandes (ajout octobre 2026)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS commande (
  id_commande     INT AUTO_INCREMENT PRIMARY KEY,
  id_user         INT          NOT NULL,
  date_commande   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  prenom          VARCHAR(100) NOT NULL,
  nom             VARCHAR(100) NOT NULL,
  adresse         VARCHAR(255) NOT NULL,
  sous_total      DECIMAL(8,2) NOT NULL,
  remise          DECIMAL(8,2) NOT NULL DEFAULT 0,
  frais_livraison DECIMAL(8,2) NOT NULL DEFAULT 0,
  total           DECIMAL(8,2) NOT NULL,
  points_gagnes   INT          NOT NULL DEFAULT 0,
  CONSTRAINT fk_commande_user FOREIGN KEY (id_user)
    REFERENCES `user`(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Titre, état et prix sont recopiés : la commande reste lisible
-- même si le livre ou l'exemplaire est supprimé plus tard.
CREATE TABLE IF NOT EXISTS commande_ligne (
  id_ligne        INT AUTO_INCREMENT PRIMARY KEY,
  id_commande     INT          NOT NULL,
  id_livre        INT          NULL,
  titre           VARCHAR(255) NOT NULL,
  etat            VARCHAR(50)  NOT NULL,
  prix_unitaire   DECIMAL(8,2) NOT NULL,
  quantite        INT          NOT NULL,
  CONSTRAINT fk_ligne_commande FOREIGN KEY (id_commande)
    REFERENCES commande(id_commande) ON DELETE CASCADE,
  CONSTRAINT fk_ligne_livre FOREIGN KEY (id_livre)
    REFERENCES livre(id_livre) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- DONNÉES DE DÉPART
-- =====================================================================

-- Compte admin local : pseudo "admin" / mot de passe "admin123"
-- (à changer ensuite ; email déjà vérifié pour pouvoir se connecter)
INSERT INTO `user` (pseudo, email, mot_de_passe, nom, prenom, role, statut, email_verifie, est_verifie)
VALUES ('admin', 'admin@biblioccaz.local',
        '$2y$10$jp6oA3MYFx2056KyrVVsS.96m4PuBA2JvuBdOI1TNSI7aYycFEOqi',
        'Admin', 'Local', 'admin', 'actif', 1, 1);

-- Quelques livres d'exemple, avec les couvertures déjà présentes dans img/
-- (prix et états inventés : ce ne sont pas les anciennes données du VPS)
INSERT INTO livre (titre, auteur, couverture, description, is_valide) VALUES
('Harry Potter et la Coupe de Feu', 'J.K. Rowling', 'Harry Potter et la Coupe de Feu.jpg', 'Quatrième tome des aventures de Harry Potter.', 1),
('Jujutsu Kaisen - Tome 22', 'Gege Akutami', 'Jujutsu-Kaisen-T22.jpg', 'Manga shonen.', 1),
('Bleach - Tome 01', 'Tite Kubo', 'Bleach-Tome-01.jpg', 'Premier tome de la série Bleach.', 1),
('L''Étranger', 'Albert Camus', 'L-Etranger.jpg', 'Roman classique de la littérature française.', 1),
('Thérèse Raquin', 'Émile Zola', 'Thérèse-Raquin.jpg', 'Roman naturaliste.', 1),
('Le Problème à trois corps', 'Liu Cixin', 'Le-Probleme-a-trois-corps.jpg', 'Roman de science-fiction.', 1),
('L''Arabe du futur - Volume 6', 'Riad Sattouf', 'L-Arabe-du-futur-Volume-6.jpg', 'Bande dessinée autobiographique.', 1),
('La chronique des Bridgerton', 'Julia Quinn', 'La chronique des Bridgerton.jpg', 'Romance historique.', 1),
('I Am Not Your Negro', 'James Baldwin', '1775768218_I-am-not-your-Negro.jpg', 'Essai.', 1),
('Absolute Batman - Tome 2', 'Scott Snyder', 'Absolute-Batman-Tome 2.jpg', 'Comics.', 1);

INSERT INTO exemplaire (id_livre, etat, prix, type_offre, is_disponible, quantite) VALUES
(1, 'bon', 6.50, 'vente', 1, 3),
(2, 'tres bon', 4.90, 'vente', 1, 2),
(3, 'use', 2.50, 'vente', 1, 1),
(4, 'bon', 3.00, 'vente', 1, 4),
(5, 'neuf', 5.50, 'vente', 1, 1),
(6, 'tres bon', 8.00, 'vente', 1, 2),
(7, 'bon', 9.90, 'vente', 1, 1),
(8, 'use', 4.00, 'vente', 1, 2),
(9, 'bon', 7.50, 'vente', 1, 1),
(10, 'tres bon', 12.00, 'vente', 1, 1);
