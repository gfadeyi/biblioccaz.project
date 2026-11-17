SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS boutique (
    id_boutique SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(40) NOT NULL,
    nom VARCHAR(120) NOT NULL,
    ville VARCHAR(80) NOT NULL,
    adresse VARCHAR(255) NULL,
    horaires VARCHAR(255) NULL,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_boutique_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO boutique (slug, nom, ville) VALUES
('paris', 'BIBLIOccaz Paris', 'Paris'),
('lyon', 'BIBLIOccaz Lyon', 'Lyon'),
('bordeaux', 'BIBLIOccaz Bordeaux', 'Bordeaux'),
('lille', 'BIBLIOccaz Lille', 'Lille'),
('nantes', 'BIBLIOccaz Nantes', 'Nantes'),
('toulouse', 'BIBLIOccaz Toulouse', 'Toulouse');

CREATE TABLE IF NOT EXISTS boutique_equipe (
    id_user INT NOT NULL PRIMARY KEY,
    id_boutique SMALLINT UNSIGNED NOT NULL,
    CONSTRAINT fk_boutique_equipe_user FOREIGN KEY (id_user) REFERENCES user(id) ON DELETE CASCADE,
    CONSTRAINT fk_boutique_equipe_boutique FOREIGN KEY (id_boutique) REFERENCES boutique(id_boutique) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS boutique_reprise (
    cle VARCHAR(60) NOT NULL PRIMARY KEY,
    nombre INT NOT NULL DEFAULT 0,
    date_execution DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE exemplaire ADD COLUMN IF NOT EXISTS id_boutique SMALLINT UNSIGNED NULL;
ALTER TABLE emprunt ADD COLUMN IF NOT EXISTS id_boutique SMALLINT UNSIGNED NULL;
ALTER TABLE emprunt_attente ADD COLUMN IF NOT EXISTS id_boutique SMALLINT UNSIGNED NULL;
ALTER TABLE exemplaire ADD INDEX IF NOT EXISTS idx_pret_boutique (id_livre, id_boutique, statut_pret);
ALTER TABLE emprunt ADD INDEX IF NOT EXISTS idx_emprunt_boutique (id_boutique, statut, date_retour_prevue);
ALTER TABLE emprunt_attente DROP INDEX IF EXISTS uq_attente_livre_user;
ALTER TABLE emprunt_attente ADD UNIQUE INDEX IF NOT EXISTS uq_attente_livre_boutique_user (id_livre, id_boutique, id_user);
ALTER TABLE emprunt_attente ADD INDEX IF NOT EXISTS idx_attente_boutique (id_livre, id_boutique, date_demande);

SET @requete_boutique = IF(EXISTS(SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'exemplaire' AND CONSTRAINT_NAME = 'fk_exemplaire_boutique'), 'SELECT 1', 'ALTER TABLE exemplaire ADD CONSTRAINT fk_exemplaire_boutique FOREIGN KEY (id_boutique) REFERENCES boutique(id_boutique) ON DELETE RESTRICT');
PREPARE ajouter_boutique FROM @requete_boutique;
EXECUTE ajouter_boutique;
DEALLOCATE PREPARE ajouter_boutique;
SET @requete_boutique = IF(EXISTS(SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'emprunt' AND CONSTRAINT_NAME = 'fk_emprunt_boutique'), 'SELECT 1', 'ALTER TABLE emprunt ADD CONSTRAINT fk_emprunt_boutique FOREIGN KEY (id_boutique) REFERENCES boutique(id_boutique) ON DELETE RESTRICT');
PREPARE ajouter_boutique FROM @requete_boutique;
EXECUTE ajouter_boutique;
DEALLOCATE PREPARE ajouter_boutique;
SET @requete_boutique = IF(EXISTS(SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'emprunt_attente' AND CONSTRAINT_NAME = 'fk_attente_boutique'), 'SELECT 1', 'ALTER TABLE emprunt_attente ADD CONSTRAINT fk_attente_boutique FOREIGN KEY (id_boutique) REFERENCES boutique(id_boutique) ON DELETE RESTRICT');
PREPARE ajouter_boutique FROM @requete_boutique;
EXECUTE ajouter_boutique;
DEALLOCATE PREPARE ajouter_boutique;
