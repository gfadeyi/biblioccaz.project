SET NAMES utf8mb4;
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
  id_proprietaire INT          NULL,
  statut_annonce   VARCHAR(20)  NULL,
  description      TEXT         NULL,
  mode_remise      VARCHAR(20)  NULL,
  ville_remise     VARCHAR(100) NULL,
  motif_moderation VARCHAR(500) NULL,
  id_moderateur    INT          NULL,
  date_creation    DATETIME     NULL,
  date_moderation  DATETIME     NULL,
  INDEX idx_exemplaire_offre (type_offre, id_proprietaire, is_disponible),
  INDEX idx_exemplaire_annonce (statut_annonce, id_proprietaire),
  CONSTRAINT fk_exemplaire_livre FOREIGN KEY (id_livre)
    REFERENCES livre(id_livre) ON DELETE CASCADE,
  CONSTRAINT fk_exemplaire_user FOREIGN KEY (id_user)
    REFERENCES `user`(id) ON DELETE SET NULL,
  CONSTRAINT fk_exemplaire_proprietaire FOREIGN KEY (id_proprietaire)
    REFERENCES `user`(id) ON DELETE CASCADE,
  CONSTRAINT fk_exemplaire_moderateur FOREIGN KEY (id_moderateur)
    REFERENCES `user`(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS exemplaire_photo (
  id_photo      INT AUTO_INCREMENT PRIMARY KEY,
  id_exemplaire INT          NOT NULL,
  fichier       VARCHAR(255) NOT NULL,
  ordre         TINYINT      NOT NULL DEFAULT 0,
  INDEX idx_photo_exemplaire (id_exemplaire, ordre),
  CONSTRAINT fk_photo_exemplaire FOREIGN KEY (id_exemplaire)
    REFERENCES exemplaire(id_exemplaire) ON DELETE CASCADE
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
  slug          VARCHAR(100) NOT NULL UNIQUE,
  famille       VARCHAR(20)  NOT NULL DEFAULT 'theme',
  ordre         INT          NOT NULL DEFAULT 0,
  INDEX idx_tag_famille (famille, ordre)
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

CREATE TABLE IF NOT EXISTS evenement (
  id_evenement    INT AUTO_INCREMENT PRIMARY KEY,
  titre           VARCHAR(200) NOT NULL,
  description     TEXT         NOT NULL,
  type            VARCHAR(20)  NOT NULL,
  auteurs         VARCHAR(255) NULL,
  lieu            VARCHAR(255) NOT NULL,
  date_debut      DATETIME     NOT NULL,
  date_fin        DATETIME     NOT NULL,
  places_max      INT          NOT NULL,
  attente_auto    TINYINT(1)   NOT NULL DEFAULT 1,
  statut          VARCHAR(20)  NOT NULL DEFAULT 'publie',
  image           VARCHAR(255) NULL,
  id_createur     INT          NULL,
  date_creation   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_annulation DATETIME     NULL,
  CONSTRAINT fk_evenement_createur FOREIGN KEY (id_createur) REFERENCES `user`(id) ON DELETE SET NULL,
  INDEX idx_evenement_date (statut, date_debut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS evenement_inscription (
  id_inscription   INT AUTO_INCREMENT PRIMARY KEY,
  id_evenement     INT          NOT NULL,
  id_user          INT          NOT NULL,
  statut           VARCHAR(20)  NOT NULL DEFAULT 'inscrit',
  code             CHAR(32)     NOT NULL UNIQUE,
  code_court       CHAR(6)      NOT NULL UNIQUE,
  date_inscription DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  present          TINYINT(1)   NOT NULL DEFAULT 0,
  date_presence    DATETIME     NULL,
  UNIQUE KEY un_inscription (id_evenement, id_user),
  CONSTRAINT fk_inscription_evenement FOREIGN KEY (id_evenement) REFERENCES evenement(id_evenement) ON DELETE CASCADE,
  CONSTRAINT fk_inscription_user      FOREIGN KEY (id_user)      REFERENCES `user`(id)              ON DELETE CASCADE,
  INDEX idx_inscription_file (id_evenement, statut, date_inscription)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO evenement (titre, description, type, auteurs, lieu, date_debut, date_fin, places_max)
SELECT 'Atelier : donner une seconde vie à ses livres',
       'Réparer une reliure, nettoyer une couverture, protéger ses livres de poche : venez avec un livre abîmé, on le remet en état ensemble.',
       'atelier', NULL, 'Médiathèque, Lyon 3e',
       DATE_ADD(DATE(NOW()), INTERVAL 14 DAY) + INTERVAL 14 HOUR,
       DATE_ADD(DATE(NOW()), INTERVAL 14 DAY) + INTERVAL 16 HOUR, 12
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM evenement);

INSERT INTO evenement (titre, description, type, auteurs, lieu, date_debut, date_fin, places_max)
SELECT 'Club manga : les shōnen de l''année',
       'Une soirée pour échanger sur les séries qui ont marqué l''année, avec une sélection de tomes d''occasion à petit prix.',
       'rencontre', NULL, 'Librairie partenaire, Lyon 7e',
       DATE_ADD(DATE(NOW()), INTERVAL 21 DAY) + INTERVAL 18 HOUR,
       DATE_ADD(DATE(NOW()), INTERVAL 21 DAY) + INTERVAL 20 HOUR, 25
FROM DUAL
WHERE (SELECT COUNT(*) FROM evenement) = 1;

CREATE TABLE IF NOT EXISTS newsletter_abonne (
  id_abonne           INT AUTO_INCREMENT PRIMARY KEY,
  email               VARCHAR(150) NOT NULL UNIQUE,
  actif               TINYINT(1)   NOT NULL DEFAULT 1,
  date_inscription    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_desinscription DATETIME     NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS favori (
  id_user     INT      NOT NULL,
  id_livre    INT      NOT NULL,
  date_ajout  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_user, id_livre),
  CONSTRAINT fk_favori_user  FOREIGN KEY (id_user)  REFERENCES `user`(id)       ON DELETE CASCADE,
  CONSTRAINT fk_favori_livre FOREIGN KEY (id_livre) REFERENCES livre(id_livre) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS alerte_stock (
  id_alerte     INT AUTO_INCREMENT PRIMARY KEY,
  id_user       INT      NOT NULL,
  id_livre      INT      NOT NULL,
  date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_envoi    DATETIME NULL,
  UNIQUE KEY un_alerte (id_user, id_livre),
  CONSTRAINT fk_alerte_user  FOREIGN KEY (id_user)  REFERENCES `user`(id)       ON DELETE CASCADE,
  CONSTRAINT fk_alerte_livre FOREIGN KEY (id_livre) REFERENCES livre(id_livre) ON DELETE CASCADE,
  INDEX idx_alerte_livre (id_livre, date_envoi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS adresse_livraison (
  id_adresse    INT AUTO_INCREMENT PRIMARY KEY,
  id_user       INT          NOT NULL,
  libelle       VARCHAR(50)  NOT NULL,
  prenom        VARCHAR(100) NOT NULL,
  nom           VARCHAR(100) NOT NULL,
  adresse       VARCHAR(255) NOT NULL,
  complement    VARCHAR(255) NULL,
  code_postal   VARCHAR(10)  NOT NULL,
  ville         VARCHAR(100) NOT NULL,
  telephone     VARCHAR(20)  NULL,
  par_defaut    TINYINT(1)   NOT NULL DEFAULT 0,
  date_creation DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_adresse_user FOREIGN KEY (id_user) REFERENCES `user`(id) ON DELETE CASCADE,
  INDEX idx_adresse_user (id_user)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS newsletter_historique (
  id_historique INT AUTO_INCREMENT PRIMARY KEY,
  email         VARCHAR(150) NOT NULL,
  id_user       INT          NULL,
  action        VARCHAR(20)  NOT NULL,
  source        VARCHAR(20)  NOT NULL,
  date_action   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_newsletter_hist_user FOREIGN KEY (id_user) REFERENCES `user`(id) ON DELETE SET NULL,
  INDEX idx_newsletter_hist_date (date_action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE categorie ADD COLUMN IF NOT EXISTS id_parent   INT          NULL;
ALTER TABLE categorie ADD COLUMN IF NOT EXISTS icone       VARCHAR(30)  NULL;
ALTER TABLE categorie ADD COLUMN IF NOT EXISTS couleur     VARCHAR(20)  NULL;
ALTER TABLE categorie ADD COLUMN IF NOT EXISTS description VARCHAR(255) NULL;
ALTER TABLE categorie ADD INDEX IF NOT EXISTS idx_categorie_parent (id_parent, ordre);

SET @fk := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'categorie' AND CONSTRAINT_NAME = 'fk_categorie_parent');
SET @sql := IF(@fk = 0,
  'ALTER TABLE categorie ADD CONSTRAINT fk_categorie_parent FOREIGN KEY (id_parent) REFERENCES categorie(id_categorie) ON DELETE SET NULL',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

INSERT IGNORE INTO categorie (nom, slug, ordre, icone, couleur, description) VALUES
('BD, Manga & Jeunesse', 'bd-manga-jeunesse', 1, 'bulle', 'bd', 'Mangas, comics, albums, ados'),
('Littérature', 'litterature', 2, 'livre-ouvert', 'litterature', 'Romans, polars, SF, classiques'),
('Art & Culture', 'art-culture', 3, 'plume', 'art', 'Histoire, photo, musique, voyages'),
('Scolaire & Études', 'scolaire-etudes', 4, 'mortier', 'scolaire', 'Manuels, annales, prépas'),
('Savoirs & Pro', 'savoirs-pro', 5, 'lunettes', 'savoirs', 'Droit, éco, informatique, langues'),
('Vie pratique', 'vie-pratique', 6, 'tasse', 'pratique', 'Cuisine, sport, maison');

UPDATE categorie SET nom = 'BD', ordre = 1 WHERE slug = 'bd-mangas';
UPDATE categorie SET nom = 'Romans jeunesse (7-12 ans)', ordre = 5 WHERE slug = 'jeunesse';
UPDATE categorie SET nom = 'Romans', ordre = 1 WHERE slug = 'roman';
UPDATE categorie SET nom = 'SF & fantasy', ordre = 3 WHERE slug = 'science-fiction';
UPDATE categorie SET nom = 'Essais & société', ordre = 6 WHERE slug = 'essai';
UPDATE categorie SET nom = 'Lycée (manuels & annales)', ordre = 3 WHERE slug = 'manuel-scolaire';

UPDATE categorie c JOIN categorie u ON u.slug = 'bd-manga-jeunesse' SET c.id_parent = u.id_categorie WHERE c.slug IN ('bd-mangas', 'jeunesse');
UPDATE categorie c JOIN categorie u ON u.slug = 'litterature' SET c.id_parent = u.id_categorie WHERE c.slug IN ('roman', 'science-fiction');
UPDATE categorie c JOIN categorie u ON u.slug = 'art-culture' SET c.id_parent = u.id_categorie WHERE c.slug = 'essai';
UPDATE categorie c JOIN categorie u ON u.slug = 'scolaire-etudes' SET c.id_parent = u.id_categorie WHERE c.slug = 'manuel-scolaire';

INSERT IGNORE INTO categorie (nom, slug, ordre, id_parent)
SELECT e.nom, e.slug, e.ordre, u.id_categorie
FROM (
    SELECT 'Comics' AS nom, 'comics' AS slug, 2 AS ordre, 'bd-manga-jeunesse' AS univers
    UNION ALL SELECT 'Mangas', 'mangas', 3, 'bd-manga-jeunesse'
    UNION ALL SELECT 'Albums (0-6 ans)', 'albums', 4, 'bd-manga-jeunesse'
    UNION ALL SELECT 'Ados', 'ados', 6, 'bd-manga-jeunesse'
    UNION ALL SELECT 'Policier & thriller', 'policier-thriller', 2, 'litterature'
    UNION ALL SELECT 'Romance', 'romance', 4, 'litterature'
    UNION ALL SELECT 'Poésie & théâtre', 'poesie-theatre', 5, 'litterature'
    UNION ALL SELECT 'Classiques', 'classiques', 6, 'litterature'
    UNION ALL SELECT 'Histoire', 'histoire', 1, 'art-culture'
    UNION ALL SELECT 'Arts & photographie', 'arts-photographie', 2, 'art-culture'
    UNION ALL SELECT 'Musique', 'musique', 3, 'art-culture'
    UNION ALL SELECT 'Voyages', 'voyages', 5, 'art-culture'
    UNION ALL SELECT 'Primaire', 'primaire', 1, 'scolaire-etudes'
    UNION ALL SELECT 'Collège', 'college', 2, 'scolaire-etudes'
    UNION ALL SELECT 'Soutien scolaire', 'soutien-scolaire', 4, 'scolaire-etudes'
    UNION ALL SELECT 'Prépas & concours', 'prepas-concours', 5, 'scolaire-etudes'
    UNION ALL SELECT 'Université', 'universite', 6, 'scolaire-etudes'
    UNION ALL SELECT 'Droit', 'droit', 1, 'savoirs-pro'
    UNION ALL SELECT 'Économie & entreprise', 'economie-entreprise', 2, 'savoirs-pro'
    UNION ALL SELECT 'Informatique', 'informatique', 3, 'savoirs-pro'
    UNION ALL SELECT 'Sciences & techniques', 'sciences-techniques', 4, 'savoirs-pro'
    UNION ALL SELECT 'Santé', 'sante', 5, 'savoirs-pro'
    UNION ALL SELECT 'Langues & dictionnaires', 'langues-dictionnaires', 6, 'savoirs-pro'
    UNION ALL SELECT 'Cuisine', 'cuisine', 1, 'vie-pratique'
    UNION ALL SELECT 'Développement personnel', 'developpement-personnel', 2, 'vie-pratique'
    UNION ALL SELECT 'Sport', 'sport', 3, 'vie-pratique'
    UNION ALL SELECT 'Maison & jardin', 'maison-jardin', 4, 'vie-pratique'
) e
JOIN categorie u ON u.slug = e.univers;

UPDATE livre l JOIN categorie c ON c.slug = 'mangas' SET l.id_categorie = c.id_categorie
  WHERE l.titre LIKE 'Jujutsu Kaisen%' OR l.titre LIKE 'Bleach%';
UPDATE livre l JOIN categorie c ON c.slug = 'comics' SET l.id_categorie = c.id_categorie
  WHERE l.titre LIKE 'Absolute Batman%';
UPDATE livre l JOIN categorie c ON c.slug = 'romance' SET l.id_categorie = c.id_categorie
  WHERE l.titre LIKE '%Bridgerton%';
UPDATE livre l JOIN categorie c ON c.slug = 'classiques' SET l.id_categorie = c.id_categorie
  WHERE l.titre LIKE 'L%tranger%' OR l.titre LIKE 'Th%r%se Raquin%';

ALTER TABLE tag ADD COLUMN IF NOT EXISTS famille VARCHAR(20) NOT NULL DEFAULT 'theme';
ALTER TABLE tag ADD COLUMN IF NOT EXISTS ordre   INT         NOT NULL DEFAULT 0;
ALTER TABLE tag ADD INDEX IF NOT EXISTS idx_tag_famille (famille, ordre);

UPDATE livre l
  JOIN categorie ancienne ON ancienne.id_categorie = l.id_categorie AND ancienne.slug = 'young-adult'
  JOIN categorie nouvelle ON nouvelle.slug = 'ados'
  SET l.id_categorie = nouvelle.id_categorie;
UPDATE don d
  JOIN categorie ancienne ON ancienne.id_categorie = d.id_categorie AND ancienne.slug = 'young-adult'
  JOIN categorie nouvelle ON nouvelle.slug = 'ados'
  SET d.id_categorie = nouvelle.id_categorie;
UPDATE livre l
  JOIN categorie ancienne ON ancienne.id_categorie = l.id_categorie AND ancienne.slug = 'cinema'
  JOIN categorie nouvelle ON nouvelle.slug = 'musique'
  SET l.id_categorie = nouvelle.id_categorie;
UPDATE don d
  JOIN categorie ancienne ON ancienne.id_categorie = d.id_categorie AND ancienne.slug = 'cinema'
  JOIN categorie nouvelle ON nouvelle.slug = 'musique'
  SET d.id_categorie = nouvelle.id_categorie;
DELETE FROM categorie WHERE slug IN ('young-adult', 'cinema');

UPDATE categorie c
JOIN (
    SELECT 'bd-mangas' AS ancien, 'bandes-dessinees' AS nouveau, 'Bandes dessinées' AS nom, 1 AS ordre
    UNION ALL SELECT 'albums', 'albums', 'Albums & premières lectures', 4
    UNION ALL SELECT 'jeunesse', 'jeunesse', 'Romans jeunesse', 5
    UNION ALL SELECT 'ados', 'ados', 'Romans ados & young adult', 6
    UNION ALL SELECT 'roman', 'roman', 'Romans contemporains', 1
    UNION ALL SELECT 'policier-thriller', 'policier-thriller', 'Polars & thrillers', 2
    UNION ALL SELECT 'science-fiction', 'science-fiction', 'Science-fiction & fantasy', 3
    UNION ALL SELECT 'arts-photographie', 'arts-photographie', 'Beaux-arts & photo', 2
    UNION ALL SELECT 'musique', 'musique', 'Musique & cinéma', 3
    UNION ALL SELECT 'voyages', 'voyages', 'Récits de voyage & guides', 4
    UNION ALL SELECT 'essai', 'essai', 'Essais & société', 5
    UNION ALL SELECT 'manuel-scolaire', 'lycee', 'Lycée', 3
    UNION ALL SELECT 'soutien-scolaire', 'soutien-scolaire', 'Cahiers de vacances & soutien', 4
    UNION ALL SELECT 'sante', 'sante', 'Santé & médecine', 5
    UNION ALL SELECT 'sport', 'sport', 'Sport & loisirs', 3
) renommage ON c.slug IN (renommage.ancien, renommage.nouveau)
SET c.slug = renommage.nouveau, c.nom = renommage.nom, c.ordre = renommage.ordre;

UPDATE don d JOIN categorie c ON c.slug = 'comics'
  SET d.id_categorie = c.id_categorie
  WHERE d.titre LIKE 'Absolute Batman%';

DELETE FROM tag WHERE slug IN ('classique', 'romance', 'histoire', 'fantastique', 'action', 'societe');

INSERT INTO tag (nom, slug, famille, ordre) VALUES
('Aventure', 'aventure', 'theme', 1),
('Action & combats', 'action-combats', 'theme', 2),
('Humour', 'humour', 'theme', 3),
('Suspense', 'suspense', 'theme', 4),
('Magie & surnaturel', 'magie-surnaturel', 'theme', 5),
('Amitié', 'amitie', 'theme', 6),
('Famille', 'famille', 'theme', 7),
('Passage à l''âge adulte', 'passage-age-adulte', 'theme', 8),
('Nature & écologie', 'nature-ecologie', 'theme', 9),
('Engagement', 'engagement', 'theme', 10),
('Super-héros', 'super-heros', 'theme', 11),
('Feel-good', 'feel-good', 'theme', 12),
('Dès 3 ans', 'des-3-ans', 'public', 1),
('Dès 6 ans', 'des-6-ans', 'public', 2),
('Dès 9 ans', 'des-9-ans', 'public', 3),
('Dès 12 ans', 'des-12-ans', 'public', 4),
('Dès 15 ans', 'des-15-ans', 'public', 5),
('Adultes', 'adultes', 'public', 6),
('Adapté en film ou série', 'adapte-film-serie', 'particularite', 1),
('Prix littéraire', 'prix-litteraire', 'particularite', 2),
('Série en plusieurs tomes', 'serie-plusieurs-tomes', 'particularite', 3),
('Premier tome', 'premier-tome', 'particularite', 4),
('Édition illustrée', 'edition-illustree', 'particularite', 5),
('Facile à lire', 'facile-a-lire', 'particularite', 6),
('Histoire vraie', 'histoire-vraie', 'particularite', 7),
('Format poche', 'format-poche', 'particularite', 8)
ON DUPLICATE KEY UPDATE nom = VALUES(nom), famille = VALUES(famille), ordre = VALUES(ordre);

INSERT IGNORE INTO livre_tag (id_livre, id_tag)
SELECT l.id_livre, t.id_tag
FROM (
    SELECT 'Harry Potter%' AS motif, 'aventure' AS tag
    UNION ALL SELECT 'Harry Potter%', 'magie-surnaturel'
    UNION ALL SELECT 'Harry Potter%', 'amitie'
    UNION ALL SELECT 'Harry Potter%', 'des-9-ans'
    UNION ALL SELECT 'Harry Potter%', 'serie-plusieurs-tomes'
    UNION ALL SELECT 'Harry Potter%', 'adapte-film-serie'
    UNION ALL SELECT 'Jujutsu Kaisen%', 'action-combats'
    UNION ALL SELECT 'Jujutsu Kaisen%', 'magie-surnaturel'
    UNION ALL SELECT 'Jujutsu Kaisen%', 'des-12-ans'
    UNION ALL SELECT 'Jujutsu Kaisen%', 'serie-plusieurs-tomes'
    UNION ALL SELECT 'Jujutsu Kaisen%', 'adapte-film-serie'
    UNION ALL SELECT 'Bleach%', 'action-combats'
    UNION ALL SELECT 'Bleach%', 'magie-surnaturel'
    UNION ALL SELECT 'Bleach%', 'des-12-ans'
    UNION ALL SELECT 'Bleach%', 'serie-plusieurs-tomes'
    UNION ALL SELECT 'Bleach%', 'adapte-film-serie'
    UNION ALL SELECT 'Bleach%', 'premier-tome'
    UNION ALL SELECT 'L%tranger', 'adultes'
    UNION ALL SELECT 'L%tranger', 'adapte-film-serie'
    UNION ALL SELECT 'L%tranger', 'format-poche'
    UNION ALL SELECT 'Th%r%se Raquin%', 'suspense'
    UNION ALL SELECT 'Th%r%se Raquin%', 'adultes'
    UNION ALL SELECT 'Th%r%se Raquin%', 'adapte-film-serie'
    UNION ALL SELECT 'Th%r%se Raquin%', 'format-poche'
    UNION ALL SELECT 'Le Probl%me %trois corps%', 'suspense'
    UNION ALL SELECT 'Le Probl%me %trois corps%', 'adultes'
    UNION ALL SELECT 'Le Probl%me %trois corps%', 'serie-plusieurs-tomes'
    UNION ALL SELECT 'Le Probl%me %trois corps%', 'prix-litteraire'
    UNION ALL SELECT 'Le Probl%me %trois corps%', 'adapte-film-serie'
    UNION ALL SELECT 'L%Arabe du futur%', 'famille'
    UNION ALL SELECT 'L%Arabe du futur%', 'humour'
    UNION ALL SELECT 'L%Arabe du futur%', 'adultes'
    UNION ALL SELECT 'L%Arabe du futur%', 'histoire-vraie'
    UNION ALL SELECT 'L%Arabe du futur%', 'serie-plusieurs-tomes'
    UNION ALL SELECT '%Bridgerton%', 'famille'
    UNION ALL SELECT '%Bridgerton%', 'adultes'
    UNION ALL SELECT '%Bridgerton%', 'serie-plusieurs-tomes'
    UNION ALL SELECT '%Bridgerton%', 'adapte-film-serie'
    UNION ALL SELECT 'I Am Not Your Negro%', 'engagement'
    UNION ALL SELECT 'I Am Not Your Negro%', 'adultes'
    UNION ALL SELECT 'I Am Not Your Negro%', 'histoire-vraie'
    UNION ALL SELECT 'I Am Not Your Negro%', 'adapte-film-serie'
    UNION ALL SELECT 'Absolute Batman%', 'action-combats'
    UNION ALL SELECT 'Absolute Batman%', 'super-heros'
    UNION ALL SELECT 'Absolute Batman%', 'des-15-ans'
    UNION ALL SELECT 'Absolute Batman%', 'serie-plusieurs-tomes'
) attribution
JOIN livre l ON l.titre LIKE attribution.motif
JOIN tag t ON t.slug = attribution.tag;

CREATE TABLE IF NOT EXISTS point_mouvement (
  id_mouvement   INT AUTO_INCREMENT PRIMARY KEY,
  id_user        INT          NOT NULL,
  points         INT          NOT NULL,
  type           VARCHAR(30)  NOT NULL,
  libelle        VARCHAR(255) NOT NULL,
  reference      VARCHAR(60)  NULL,
  statut         VARCHAR(20)  NOT NULL DEFAULT 'valide',
  date_mouvement DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY un_mouvement_reference (id_user, type, reference),
  CONSTRAINT fk_mouvement_user FOREIGN KEY (id_user) REFERENCES `user`(id) ON DELETE CASCADE,
  INDEX idx_mouvement_user (id_user, statut, date_mouvement)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS badge_obtenu (
  id_user        INT          NOT NULL,
  code           VARCHAR(40)  NOT NULL,
  palier         TINYINT      NOT NULL DEFAULT 1,
  date_obtention DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  vu             TINYINT(1)   NOT NULL DEFAULT 0,
  PRIMARY KEY (id_user, code, palier),
  CONSTRAINT fk_badge_user FOREIGN KEY (id_user) REFERENCES `user`(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS bon_cadeau (
  id_bon           INT AUTO_INCREMENT PRIMARY KEY,
  id_user          INT          NOT NULL,
  montant          DECIMAL(8,2) NOT NULL,
  motif            VARCHAR(40)  NOT NULL,
  annee            INT          NOT NULL,
  date_creation    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_expiration  DATE         NOT NULL,
  date_utilisation DATETIME     NULL,
  id_commande      INT          NULL,
  UNIQUE KEY un_bon_annee (id_user, motif, annee),
  CONSTRAINT fk_bon_user     FOREIGN KEY (id_user)     REFERENCES `user`(id)            ON DELETE CASCADE,
  CONSTRAINT fk_bon_commande FOREIGN KEY (id_commande) REFERENCES commande(id_commande) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `user` ADD COLUMN IF NOT EXISTS niveau_vu TINYINT NOT NULL DEFAULT 0;

ALTER TABLE commande ADD COLUMN IF NOT EXISTS statut           VARCHAR(20)  NOT NULL DEFAULT 'validee';
ALTER TABLE commande ADD COLUMN IF NOT EXISTS date_annulation  DATETIME     NULL;
ALTER TABLE commande ADD COLUMN IF NOT EXISTS points_utilises  INT          NOT NULL DEFAULT 0;
ALTER TABLE commande ADD COLUMN IF NOT EXISTS reduction_points DECIMAL(8,2) NOT NULL DEFAULT 0;
ALTER TABLE commande ADD COLUMN IF NOT EXISTS bonus_niveau     INT          NOT NULL DEFAULT 0;
ALTER TABLE commande ADD COLUMN IF NOT EXISTS id_bon_cadeau    INT          NULL;

ALTER TABLE avis ADD COLUMN IF NOT EXISTS date_validation DATETIME NULL;
ALTER TABLE avis ADD COLUMN IF NOT EXISTS id_validateur   INT      NULL;

ALTER TABLE evenement_inscription ADD COLUMN IF NOT EXISTS prioritaire TINYINT(1) NOT NULL DEFAULT 0;

SET @fk := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'avis' AND CONSTRAINT_NAME = 'fk_avis_validateur');
SET @sql := IF(@fk = 0,
  'ALTER TABLE avis ADD CONSTRAINT fk_avis_validateur FOREIGN KEY (id_validateur) REFERENCES `user`(id) ON DELETE SET NULL',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @fk := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'commande' AND CONSTRAINT_NAME = 'fk_commande_bon');
SET @sql := IF(@fk = 0,
  'ALTER TABLE commande ADD CONSTRAINT fk_commande_bon FOREIGN KEY (id_bon_cadeau) REFERENCES bon_cadeau(id_bon) ON DELETE SET NULL',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

UPDATE avis SET date_validation = date_creation WHERE statut = 'visible' AND date_validation IS NULL;

INSERT IGNORE INTO point_mouvement (id_user, points, type, libelle, reference, date_mouvement)
SELECT c.id_user, c.points_gagnes, 'achat', CONCAT('Commande n° ', c.id_commande), CONCAT('commande:', c.id_commande), c.date_commande
FROM commande c
WHERE c.points_gagnes > 0;

INSERT IGNORE INTO point_mouvement (id_user, points, type, libelle, reference, date_mouvement)
SELECT a.id_user, 5, 'avis', CONCAT('Avis sur « ', l.titre, ' »'), CONCAT('livre:', a.id_livre), a.date_creation
FROM avis a
INNER JOIN livre l ON l.id_livre = a.id_livre
INNER JOIN `user` u ON u.id = a.id_user AND u.role = 'client'
WHERE a.statut = 'visible';

INSERT IGNORE INTO point_mouvement (id_user, points, type, libelle, reference, date_mouvement)
SELECT d.id_donneur, 20, 'don', CONCAT('Don récupéré : « ', d.titre, ' »'), CONCAT('don:', d.id_don), COALESCE(d.date_recuperation, d.date_creation)
FROM don d
WHERE d.statut = 'recupere';

INSERT IGNORE INTO point_mouvement (id_user, points, type, libelle, reference, date_mouvement)
SELECT u.id, u.solde_points - COALESCE(m.total, 0), 'ajustement', 'Reprise du solde existant', 'reprise', NOW()
FROM `user` u
LEFT JOIN (SELECT id_user, SUM(points) AS total FROM point_mouvement WHERE statut = 'valide' GROUP BY id_user) m ON m.id_user = u.id
WHERE u.solde_points - COALESCE(m.total, 0) <> 0;

ALTER TABLE livre ADD COLUMN IF NOT EXISTS collection      VARCHAR(150) NULL;
ALTER TABLE livre ADD COLUMN IF NOT EXISTS langue          VARCHAR(50)  NULL;
ALTER TABLE livre ADD COLUMN IF NOT EXISTS niveau_scolaire VARCHAR(50)  NULL;
ALTER TABLE livre ADD COLUMN IF NOT EXISTS matiere         VARCHAR(80)  NULL;
ALTER TABLE livre ADD INDEX IF NOT EXISTS idx_livre_isbn (isbn);

ALTER TABLE `user` ADD COLUMN IF NOT EXISTS photo VARCHAR(255) NULL;

ALTER TABLE evenement ADD COLUMN IF NOT EXISTS motif_refus     VARCHAR(500) NULL;
ALTER TABLE evenement ADD COLUMN IF NOT EXISTS date_validation DATETIME     NULL;
ALTER TABLE evenement ADD COLUMN IF NOT EXISTS id_validateur   INT          NULL;

SET @fk := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'evenement' AND CONSTRAINT_NAME = 'fk_evenement_validateur');
SET @sql := IF(@fk = 0,
  'ALTER TABLE evenement ADD CONSTRAINT fk_evenement_validateur FOREIGN KEY (id_validateur) REFERENCES `user`(id) ON DELETE SET NULL',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

CREATE TABLE IF NOT EXISTS evenement_livre (
  id_evenement INT NOT NULL,
  id_livre     INT NOT NULL,
  PRIMARY KEY (id_evenement, id_livre),
  CONSTRAINT fk_evtlivre_evenement FOREIGN KEY (id_evenement) REFERENCES evenement(id_evenement) ON DELETE CASCADE,
  CONSTRAINT fk_evtlivre_livre     FOREIGN KEY (id_livre)     REFERENCES livre(id_livre)         ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE commande_ligne ADD COLUMN IF NOT EXISTS id_exemplaire INT NULL AFTER id_livre;
ALTER TABLE commande_ligne ADD INDEX IF NOT EXISTS idx_ligne_exemplaire (id_exemplaire);

ALTER TABLE commande ADD COLUMN IF NOT EXISTS id_annulateur    INT          NULL;
ALTER TABLE commande ADD COLUMN IF NOT EXISTS motif_annulation VARCHAR(255) NULL;
ALTER TABLE commande ADD INDEX IF NOT EXISTS idx_commande_statut_date (statut, date_commande);

SET @fk := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'commande_ligne' AND CONSTRAINT_NAME = 'fk_ligne_exemplaire');
SET @sql := IF(@fk = 0,
  'ALTER TABLE commande_ligne ADD CONSTRAINT fk_ligne_exemplaire FOREIGN KEY (id_exemplaire) REFERENCES exemplaire(id_exemplaire) ON DELETE SET NULL',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @fk := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'commande' AND CONSTRAINT_NAME = 'fk_commande_annulateur');
SET @sql := IF(@fk = 0,
  'ALTER TABLE commande ADD CONSTRAINT fk_commande_annulateur FOREIGN KEY (id_annulateur) REFERENCES `user`(id) ON DELETE SET NULL',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

UPDATE commande_ligne cl
INNER JOIN (
  SELECT id_livre, etat, prix, MIN(id_exemplaire) AS id_exemplaire
  FROM exemplaire
  GROUP BY id_livre, etat, prix
) e ON e.id_livre = cl.id_livre AND e.etat = cl.etat AND e.prix = cl.prix_unitaire
SET cl.id_exemplaire = e.id_exemplaire
WHERE cl.id_exemplaire IS NULL;

CREATE TABLE IF NOT EXISTS candidature_moderateur (
  id_candidature   INT          NOT NULL AUTO_INCREMENT,
  id_user          INT          NOT NULL,
  motivations      TEXT         NULL,
  statut           VARCHAR(20)  NOT NULL DEFAULT 'en_attente',
  date_candidature DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_decision    DATETIME     NULL,
  id_decideur      INT          NULL,
  message_decision VARCHAR(1000) NULL,
  PRIMARY KEY (id_candidature),
  KEY idx_candidature_statut (statut, date_candidature),
  CONSTRAINT fk_candidature_user     FOREIGN KEY (id_user)     REFERENCES `user`(id) ON DELETE CASCADE,
  CONSTRAINT fk_candidature_decideur FOREIGN KEY (id_decideur) REFERENCES `user`(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS conversation (
  id_conversation      INT AUTO_INCREMENT PRIMARY KEY,
  type_objet           VARCHAR(20)  NOT NULL,
  id_objet             INT          NOT NULL,
  statut               VARCHAR(20)  NOT NULL DEFAULT 'ouverte',
  motif_fermeture      VARCHAR(255) NULL,
  date_creation        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_dernier_message DATETIME     NULL,
  date_fermeture       DATETIME     NULL,
  INDEX idx_conversation_objet (type_objet, id_objet),
  INDEX idx_conversation_statut (statut, date_dernier_message)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS conversation_participant (
  id_conversation       INT         NOT NULL,
  id_user               INT         NOT NULL,
  role_participant      VARCHAR(20) NOT NULL,
  date_derniere_lecture DATETIME    NULL,
  date_dernier_mail     DATETIME    NULL,
  PRIMARY KEY (id_conversation, id_user),
  INDEX idx_participant_user (id_user),
  CONSTRAINT fk_participant_conversation FOREIGN KEY (id_conversation) REFERENCES conversation(id_conversation) ON DELETE CASCADE,
  CONSTRAINT fk_participant_user         FOREIGN KEY (id_user)         REFERENCES `user`(id)                    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS conversation_message (
  id_message      INT AUTO_INCREMENT PRIMARY KEY,
  id_conversation INT           NOT NULL,
  id_auteur       INT           NULL,
  contenu         VARCHAR(1000) NOT NULL,
  est_systeme     TINYINT(1)    NOT NULL DEFAULT 0,
  est_masque      TINYINT(1)    NOT NULL DEFAULT 0,
  id_masque_par   INT           NULL,
  date_envoi      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_masquage   DATETIME      NULL,
  INDEX idx_message_conversation (id_conversation, date_envoi),
  INDEX idx_message_auteur (id_auteur, date_envoi),
  CONSTRAINT fk_message_conversation FOREIGN KEY (id_conversation) REFERENCES conversation(id_conversation) ON DELETE CASCADE,
  CONSTRAINT fk_message_auteur       FOREIGN KEY (id_auteur)       REFERENCES `user`(id)                    ON DELETE SET NULL,
  CONSTRAINT fk_message_masque_par   FOREIGN KEY (id_masque_par)   REFERENCES `user`(id)                    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS message_signalement (
  id_signalement  INT AUTO_INCREMENT PRIMARY KEY,
  id_message      INT          NOT NULL,
  id_user         INT          NOT NULL,
  motif           VARCHAR(30)  NOT NULL,
  commentaire     VARCHAR(500) NULL,
  traite          TINYINT(1)   NOT NULL DEFAULT 0,
  id_traite_par   INT          NULL,
  date_signalement DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_traitement DATETIME     NULL,
  UNIQUE KEY uq_signalement_message (id_message, id_user),
  INDEX idx_signalement_traite (traite, date_signalement),
  CONSTRAINT fk_signalement_message    FOREIGN KEY (id_message)    REFERENCES conversation_message(id_message) ON DELETE CASCADE,
  CONSTRAINT fk_signalement_user       FOREIGN KEY (id_user)       REFERENCES `user`(id)                       ON DELETE CASCADE,
  CONSTRAINT fk_signalement_traite_par FOREIGN KEY (id_traite_par) REFERENCES `user`(id)                       ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS demande_achat (
  id_demande       INT AUTO_INCREMENT PRIMARY KEY,
  id_annonce       INT          NOT NULL,
  id_acheteur      INT          NOT NULL,
  id_vendeur       INT          NOT NULL,
  prix             DECIMAL(8,2) NOT NULL,
  mode_remise      VARCHAR(20)  NOT NULL,
  statut           VARCHAR(20)  NOT NULL DEFAULT 'en_attente',
  motif            VARCHAR(500) NULL,
  en_litige        TINYINT(1)   NOT NULL DEFAULT 0,
  motif_litige     VARCHAR(500) NULL,
  id_signale_par   INT          NULL,
  id_moderateur    INT          NULL,
  numero_recu      VARCHAR(20)  NULL,
  date_demande     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_attente     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_acceptation DATETIME     NULL,
  date_validation  DATETIME     NULL,
  date_confirmation DATETIME    NULL,
  confirmation     VARCHAR(10)  NULL,
  date_fin         DATETIME     NULL,
  date_litige      DATETIME     NULL,
  UNIQUE KEY uq_demande_recu (numero_recu),
  INDEX idx_demande_annonce (id_annonce, statut),
  INDEX idx_demande_acheteur (id_acheteur, statut),
  INDEX idx_demande_vendeur (id_vendeur, statut),
  INDEX idx_demande_attente (statut, date_attente),
  INDEX idx_demande_litige (en_litige, statut),
  INDEX idx_demande_confirmation (statut, date_confirmation, date_validation),
  CONSTRAINT fk_demande_annonce    FOREIGN KEY (id_annonce)     REFERENCES exemplaire(id_exemplaire) ON DELETE CASCADE,
  CONSTRAINT fk_demande_acheteur   FOREIGN KEY (id_acheteur)    REFERENCES `user`(id)                ON DELETE CASCADE,
  CONSTRAINT fk_demande_vendeur    FOREIGN KEY (id_vendeur)     REFERENCES `user`(id)                ON DELETE CASCADE,
  CONSTRAINT fk_demande_signale    FOREIGN KEY (id_signale_par) REFERENCES `user`(id)                ON DELETE SET NULL,
  CONSTRAINT fk_demande_moderateur FOREIGN KEY (id_moderateur)  REFERENCES `user`(id)                ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE exemplaire ADD COLUMN IF NOT EXISTS statut_pret VARCHAR(20) NULL AFTER date_moderation;
ALTER TABLE exemplaire ADD COLUMN IF NOT EXISTS duree_emprunt_jours SMALLINT NOT NULL DEFAULT 21 AFTER statut_pret;
ALTER TABLE exemplaire ADD COLUMN IF NOT EXISTS renouvellements_max TINYINT NOT NULL DEFAULT 2 AFTER duree_emprunt_jours;
ALTER TABLE exemplaire ADD COLUMN IF NOT EXISTS blocage_retard TINYINT(1) NOT NULL DEFAULT 1 AFTER renouvellements_max;
ALTER TABLE exemplaire ADD INDEX IF NOT EXISTS idx_exemplaire_pret (type_offre, statut_pret, id_livre);

CREATE TABLE IF NOT EXISTS emprunt (
  id_emprunt                  INT AUTO_INCREMENT PRIMARY KEY,
  id_exemplaire               INT          NOT NULL,
  id_user                     INT          NOT NULL,
  statut                      VARCHAR(20)  NOT NULL DEFAULT 'reserve',
  code_retrait                CHAR(6)      NOT NULL,
  duree_jours                 SMALLINT     NOT NULL DEFAULT 21,
  renouvellements_max         TINYINT      NOT NULL DEFAULT 2,
  blocage_retard              TINYINT(1)   NOT NULL DEFAULT 1,
  nb_renouvellements          TINYINT      NOT NULL DEFAULT 0,
  date_reservation            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_expiration_reservation DATETIME     NOT NULL,
  date_retrait                DATETIME     NULL,
  date_retour_prevue          DATE         NULL,
  date_retour                 DATETIME     NULL,
  jours_retard                SMALLINT     NOT NULL DEFAULT 0,
  id_valide_par               INT          NULL,
  id_retour_par               INT          NULL,
  rappel_avant                TINYINT(1)   NOT NULL DEFAULT 0,
  rappel_jour                 TINYINT(1)   NOT NULL DEFAULT 0,
  nb_relances                 SMALLINT     NOT NULL DEFAULT 0,
  date_derniere_relance       DATETIME     NULL,
  motif_annulation            VARCHAR(500) NULL,
  date_fin                    DATETIME     NULL,
  UNIQUE KEY uq_emprunt_code (code_retrait),
  INDEX idx_emprunt_user (id_user, statut),
  INDEX idx_emprunt_exemplaire (id_exemplaire, statut),
  INDEX idx_emprunt_statut (statut, date_retour_prevue),
  CONSTRAINT fk_emprunt_exemplaire FOREIGN KEY (id_exemplaire) REFERENCES exemplaire(id_exemplaire) ON DELETE CASCADE,
  CONSTRAINT fk_emprunt_user       FOREIGN KEY (id_user)       REFERENCES `user`(id)                ON DELETE CASCADE,
  CONSTRAINT fk_emprunt_valide_par FOREIGN KEY (id_valide_par) REFERENCES `user`(id)                ON DELETE SET NULL,
  CONSTRAINT fk_emprunt_retour_par FOREIGN KEY (id_retour_par) REFERENCES `user`(id)                ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE emprunt ADD COLUMN IF NOT EXISTS blocage_retard TINYINT(1) NULL AFTER renouvellements_max;
UPDATE emprunt em INNER JOIN exemplaire e ON e.id_exemplaire = em.id_exemplaire SET em.blocage_retard = e.blocage_retard WHERE em.blocage_retard IS NULL;
ALTER TABLE emprunt MODIFY COLUMN blocage_retard TINYINT(1) NOT NULL DEFAULT 1;

CREATE TABLE IF NOT EXISTS blocage_emprunt (
  id_blocage  INT AUTO_INCREMENT PRIMARY KEY,
  id_user     INT          NOT NULL,
  id_emprunt  INT          NULL,
  jours       SMALLINT     NOT NULL,
  date_debut  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_fin    DATETIME     NOT NULL,
  motif       VARCHAR(500) NOT NULL,
  date_levee  DATETIME     NULL,
  id_leve_par INT          NULL,
  motif_levee VARCHAR(500) NULL,
  INDEX idx_blocage_user (id_user, date_levee, date_fin),
  CONSTRAINT fk_blocage_user    FOREIGN KEY (id_user)     REFERENCES `user`(id)          ON DELETE CASCADE,
  CONSTRAINT fk_blocage_emprunt FOREIGN KEY (id_emprunt)  REFERENCES emprunt(id_emprunt) ON DELETE SET NULL,
  CONSTRAINT fk_blocage_leve    FOREIGN KEY (id_leve_par) REFERENCES `user`(id)          ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS emprunt_attente (
  id_attente   INT AUTO_INCREMENT PRIMARY KEY,
  id_livre     INT      NOT NULL,
  id_user      INT      NOT NULL,
  date_demande DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_attente_livre_user (id_livre, id_user),
  INDEX idx_attente_livre (id_livre, date_demande),
  CONSTRAINT fk_attente_livre FOREIGN KEY (id_livre) REFERENCES livre(id_livre) ON DELETE CASCADE,
  CONSTRAINT fk_attente_user  FOREIGN KEY (id_user)  REFERENCES `user`(id)      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS session_utilisateur (
    id_suivi INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    empreinte CHAR(64) NOT NULL UNIQUE,
    empreinte_mdp CHAR(64) NOT NULL,
    espace ENUM('site', 'admin') NOT NULL,
    role_connexion VARCHAR(20) NULL,
    date_connexion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    derniere_activite DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_revocation DATETIME NULL,
    id_revoque_par INT NULL,
    INDEX idx_session_membre (id_user, date_revocation, derniere_activite),
    INDEX idx_session_activite (date_revocation, derniere_activite),
    FOREIGN KEY (id_user) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (id_revoque_par) REFERENCES user(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS regle_recompense (
    id_regle TINYINT PRIMARY KEY,
    version INT NOT NULL DEFAULT 1,
    configuration LONGTEXT NOT NULL CHECK (JSON_VALID(configuration)),
    id_modifie_par INT NULL,
    date_modification DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_modifie_par) REFERENCES user(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS regle_recompense_historique (
    id_historique INT AUTO_INCREMENT PRIMARY KEY,
    version INT NOT NULL UNIQUE,
    ancienne_configuration LONGTEXT NOT NULL CHECK (JSON_VALID(ancienne_configuration)),
    nouvelle_configuration LONGTEXT NOT NULL CHECK (JSON_VALID(nouvelle_configuration)),
    id_modifie_par INT NULL,
    date_modification DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_modifie_par) REFERENCES user(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO regle_recompense (id_regle, version, configuration) VALUES (1, 1, '{"points":{"achat":1,"avis":5,"premier_avis":10,"premier_achat":20,"don":20,"presence":10,"vente":10,"emprunt":5,"badge_bronze":5,"badge_argent":15,"badge_or":30,"badge_rare":30},"quotas":{"avis":4,"vente":5,"emprunt":5},"niveaux":[0,150,400,900,1800,3500],"badges":{"lecteur_fidele":[1,10,50],"plume_critique":[1,10,30],"coeur_genereux":[1,5,20],"habitue_rencontres":[1,5,15],"seconde_main":[1,5,15],"habitue_bibliotheque":[1,10,30],"un_an":[5],"explorateur":[6],"collectionneur":[25],"pilier":[4]}}');
