SET NAMES utf8mb4;

ALTER TABLE emprunt ADD COLUMN IF NOT EXISTS renouvellement_jours SMALLINT NOT NULL DEFAULT 14 AFTER renouvellements_max;

CREATE TABLE IF NOT EXISTS regle_emprunt (
    id_regle TINYINT NOT NULL PRIMARY KEY,
    version INT NOT NULL DEFAULT 1,
    configuration LONGTEXT NOT NULL,
    id_modifie_par INT NULL,
    date_modification DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_regle_emprunt_admin FOREIGN KEY (id_modifie_par) REFERENCES user(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS regle_emprunt_historique (
    id_historique INT AUTO_INCREMENT PRIMARY KEY,
    version INT NOT NULL,
    ancienne_configuration LONGTEXT NOT NULL,
    nouvelle_configuration LONGTEXT NOT NULL,
    id_modifie_par INT NULL,
    date_modification DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_regle_emprunt_version (version),
    CONSTRAINT fk_historique_emprunt_admin FOREIGN KEY (id_modifie_par) REFERENCES user(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO regle_emprunt (id_regle, version, configuration) VALUES
(1, 1, '{"emprunts_max":3,"attentes_max":3,"reservation_jours_ouvres":3,"duree_jours":21,"renouvellements_max":2,"renouvellement_jours":14}');
