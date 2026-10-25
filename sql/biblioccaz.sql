CREATE DATABASE IF NOT EXISTS biblioccaz
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE biblioccaz;

CREATE USER IF NOT EXISTS 'admin_biblio'@'localhost' IDENTIFIED BY 'changez_moi';
GRANT ALL PRIVILEGES ON biblioccaz.* TO 'admin_biblio'@'localhost';
FLUSH PRIVILEGES;

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
  reset_token_hash CHAR(64)    NULL,
  reset_expire    DATETIME     NULL,
  date_inscription DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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

INSERT INTO `user` (pseudo, email, mot_de_passe, nom, prenom, role, statut, email_verifie, est_verifie)
VALUES ('admin', 'admin@biblioccaz.local',
        '$2y$10$jp6oA3MYFx2056KyrVVsS.96m4PuBA2JvuBdOI1TNSI7aYycFEOqi',
        'Admin', 'Local', 'admin', 'actif', 1, 1);

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

CREATE TABLE IF NOT EXISTS categorie (
  id_categorie  INT AUTO_INCREMENT PRIMARY KEY,
  nom           VARCHAR(100) NOT NULL,
  slug          VARCHAR(100) NOT NULL UNIQUE,
  ordre         INT          NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tag (
  id_tag        INT AUTO_INCREMENT PRIMARY KEY,
  nom           VARCHAR(100) NOT NULL,
  slug          VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS livre_tag (
  id_livre      INT NOT NULL,
  id_tag        INT NOT NULL,
  PRIMARY KEY (id_livre, id_tag),
  CONSTRAINT fk_livretag_livre FOREIGN KEY (id_livre) REFERENCES livre(id_livre) ON DELETE CASCADE,
  CONSTRAINT fk_livretag_tag   FOREIGN KEY (id_tag)   REFERENCES tag(id_tag)     ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE livre ADD COLUMN IF NOT EXISTS id_categorie INT NULL;

SET @fk := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'livre' AND CONSTRAINT_NAME = 'fk_livre_categorie');
SET @sql := IF(@fk = 0,
  'ALTER TABLE livre ADD CONSTRAINT fk_livre_categorie FOREIGN KEY (id_categorie) REFERENCES categorie(id_categorie) ON DELETE SET NULL',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

INSERT IGNORE INTO categorie (nom, slug, ordre) VALUES
('Roman', 'roman', 1),
('Jeunesse', 'jeunesse', 2),
('BD & Mangas', 'bd-mangas', 3),
('Science-fiction & Fantasy', 'science-fiction', 4),
('Essai', 'essai', 5),
('Manuel scolaire', 'manuel-scolaire', 6);

INSERT IGNORE INTO tag (nom, slug) VALUES
('Classique', 'classique'),
('Aventure', 'aventure'),
('Romance', 'romance'),
('Fantastique', 'fantastique'),
('Histoire', 'histoire'),
('Action', 'action'),
('Société', 'societe');

UPDATE livre SET id_categorie = (SELECT id_categorie FROM categorie WHERE slug = 'roman')
  WHERE id_categorie IS NULL AND (titre LIKE 'L%tranger%' OR titre LIKE 'Th%r%se Raquin%' OR titre LIKE '%Bridgerton%');
UPDATE livre SET id_categorie = (SELECT id_categorie FROM categorie WHERE slug = 'jeunesse')
  WHERE id_categorie IS NULL AND titre LIKE 'Harry Potter%';
UPDATE livre SET id_categorie = (SELECT id_categorie FROM categorie WHERE slug = 'bd-mangas')
  WHERE id_categorie IS NULL AND (titre LIKE 'Jujutsu Kaisen%' OR titre LIKE 'Bleach%' OR titre LIKE 'Absolute Batman%' OR titre LIKE 'L%Arabe du futur%');
UPDATE livre SET id_categorie = (SELECT id_categorie FROM categorie WHERE slug = 'science-fiction')
  WHERE id_categorie IS NULL AND titre LIKE 'Le Probl%me %trois corps%';
UPDATE livre SET id_categorie = (SELECT id_categorie FROM categorie WHERE slug = 'essai')
  WHERE id_categorie IS NULL AND titre LIKE 'I Am Not Your Negro%';

INSERT IGNORE INTO livre_tag (id_livre, id_tag)
  SELECT l.id_livre, t.id_tag FROM livre l JOIN tag t ON t.slug = 'classique'
  WHERE l.titre LIKE 'L%tranger%' OR l.titre LIKE 'Th%r%se Raquin%';
INSERT IGNORE INTO livre_tag (id_livre, id_tag)
  SELECT l.id_livre, t.id_tag FROM livre l JOIN tag t ON t.slug = 'romance'
  WHERE l.titre LIKE '%Bridgerton%';
INSERT IGNORE INTO livre_tag (id_livre, id_tag)
  SELECT l.id_livre, t.id_tag FROM livre l JOIN tag t ON t.slug = 'fantastique'
  WHERE l.titre LIKE 'Harry Potter%' OR l.titre LIKE 'Jujutsu Kaisen%' OR l.titre LIKE 'Bleach%';
INSERT IGNORE INTO livre_tag (id_livre, id_tag)
  SELECT l.id_livre, t.id_tag FROM livre l JOIN tag t ON t.slug = 'action'
  WHERE l.titre LIKE 'Jujutsu Kaisen%' OR l.titre LIKE 'Bleach%' OR l.titre LIKE 'Absolute Batman%';
INSERT IGNORE INTO livre_tag (id_livre, id_tag)
  SELECT l.id_livre, t.id_tag FROM livre l JOIN tag t ON t.slug = 'aventure'
  WHERE l.titre LIKE 'Harry Potter%';
INSERT IGNORE INTO livre_tag (id_livre, id_tag)
  SELECT l.id_livre, t.id_tag FROM livre l JOIN tag t ON t.slug = 'societe'
  WHERE l.titre LIKE 'I Am Not Your Negro%' OR l.titre LIKE 'L%Arabe du futur%';
INSERT IGNORE INTO livre_tag (id_livre, id_tag)
  SELECT l.id_livre, t.id_tag FROM livre l JOIN tag t ON t.slug = 'histoire'
  WHERE l.titre LIKE 'L%Arabe du futur%';

CREATE TABLE IF NOT EXISTS avis (
  id_avis           INT AUTO_INCREMENT PRIMARY KEY,
  id_livre          INT          NOT NULL,
  id_user           INT          NOT NULL,
  note              TINYINT      NOT NULL,
  titre             VARCHAR(120) NULL,
  commentaire       TEXT         NOT NULL,
  date_creation     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_modification DATETIME     NULL,
  statut            VARCHAR(20)  NOT NULL DEFAULT 'visible',
  UNIQUE KEY un_avis_par_livre (id_livre, id_user),
  CONSTRAINT fk_avis_livre FOREIGN KEY (id_livre) REFERENCES livre(id_livre) ON DELETE CASCADE,
  CONSTRAINT fk_avis_user  FOREIGN KEY (id_user)  REFERENCES `user`(id)       ON DELETE CASCADE,
  CONSTRAINT chk_avis_note CHECK (note BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS avis_signalement (
  id_signalement    INT AUTO_INCREMENT PRIMARY KEY,
  id_avis           INT          NOT NULL,
  id_user           INT          NOT NULL,
  motif             VARCHAR(50)  NOT NULL,
  date_signalement  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  traite            TINYINT(1)   NOT NULL DEFAULT 0,
  UNIQUE KEY un_signalement (id_avis, id_user),
  CONSTRAINT fk_signal_avis FOREIGN KEY (id_avis) REFERENCES avis(id_avis) ON DELETE CASCADE,
  CONSTRAINT fk_signal_user FOREIGN KEY (id_user) REFERENCES `user`(id)    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS don (
  id_don             INT AUTO_INCREMENT PRIMARY KEY,
  id_donneur         INT          NOT NULL,
  id_livre           INT          NULL,
  id_categorie       INT          NULL,
  titre              VARCHAR(255) NOT NULL,
  auteur             VARCHAR(255) NULL,
  etat               VARCHAR(50)  NOT NULL,
  description        TEXT         NULL,
  photo              VARCHAR(255) NULL,
  lieu_retrait       VARCHAR(255) NOT NULL,
  conditions_retrait VARCHAR(255) NULL,
  date_limite        DATE         NOT NULL,
  statut             VARCHAR(20)  NOT NULL DEFAULT 'disponible',
  code               CHAR(32)     NOT NULL UNIQUE,
  code_court         CHAR(6)      NOT NULL UNIQUE,
  id_beneficiaire    INT          NULL,
  date_creation      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_reservation   DATETIME     NULL,
  date_recuperation  DATETIME     NULL,
  CONSTRAINT fk_don_donneur     FOREIGN KEY (id_donneur)      REFERENCES `user`(id)              ON DELETE CASCADE,
  CONSTRAINT fk_don_beneficiaire FOREIGN KEY (id_beneficiaire) REFERENCES `user`(id)             ON DELETE SET NULL,
  CONSTRAINT fk_don_livre       FOREIGN KEY (id_livre)        REFERENCES livre(id_livre)         ON DELETE SET NULL,
  CONSTRAINT fk_don_categorie   FOREIGN KEY (id_categorie)    REFERENCES categorie(id_categorie) ON DELETE SET NULL,
  INDEX idx_don_statut (statut, date_limite)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
