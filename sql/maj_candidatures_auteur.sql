SET NAMES utf8mb4;
USE biblioccaz;

CREATE TABLE IF NOT EXISTS candidature_auteur (
    id_candidature INT NOT NULL AUTO_INCREMENT,
    id_user INT NOT NULL,
    nom_plume VARCHAR(80) NOT NULL,
    biographie TEXT NOT NULL,
    motivation TEXT NOT NULL,
    ouvrages TEXT NOT NULL,
    lien_site VARCHAR(255) NULL,
    lien_reseau VARCHAR(255) NULL,
    lien_editeur VARCHAR(255) NULL,
    photo VARCHAR(100) NULL,
    statut VARCHAR(20) NOT NULL DEFAULT 'en_attente',
    date_candidature DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_decision DATETIME NULL,
    id_decideur INT NULL,
    message_decision VARCHAR(1000) NULL,
    PRIMARY KEY (id_candidature),
    INDEX idx_candidature_auteur_statut (statut, date_candidature),
    INDEX idx_candidature_auteur_membre (id_user, date_candidature),
    CONSTRAINT fk_candidature_auteur_user FOREIGN KEY (id_user) REFERENCES `user`(id) ON DELETE CASCADE,
    CONSTRAINT fk_candidature_auteur_decideur FOREIGN KEY (id_decideur) REFERENCES `user`(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS fiche_auteur (
    id_user INT NOT NULL,
    nom_plume VARCHAR(80) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    biographie TEXT NOT NULL,
    lien_site VARCHAR(255) NULL,
    lien_reseau VARCHAR(255) NULL,
    lien_editeur VARCHAR(255) NULL,
    photo VARCHAR(100) NULL,
    est_masquee TINYINT(1) NOT NULL DEFAULT 0,
    id_masquee_par INT NULL,
    date_masquage DATETIME NULL,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_modification DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_user),
    UNIQUE KEY uk_fiche_auteur_slug (slug),
    INDEX idx_fiche_auteur_nom (nom_plume),
    CONSTRAINT fk_fiche_auteur_user FOREIGN KEY (id_user) REFERENCES `user`(id) ON DELETE CASCADE,
    CONSTRAINT fk_fiche_auteur_masquage FOREIGN KEY (id_masquee_par) REFERENCES `user`(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS fiche_auteur_ouvrage (
    id_ouvrage INT NOT NULL AUTO_INCREMENT,
    id_user INT NOT NULL,
    titre VARCHAR(200) NOT NULL,
    editeur VARCHAR(120) NULL,
    annee SMALLINT NULL,
    isbn VARCHAR(13) NULL,
    ordre TINYINT NOT NULL DEFAULT 0,
    PRIMARY KEY (id_ouvrage),
    INDEX idx_ouvrage_auteur (id_user, ordre),
    CONSTRAINT fk_ouvrage_fiche FOREIGN KEY (id_user) REFERENCES fiche_auteur(id_user) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
