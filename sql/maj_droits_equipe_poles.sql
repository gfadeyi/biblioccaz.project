SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS parametre_site (
    cle VARCHAR(50) PRIMARY KEY,
    configuration LONGTEXT NOT NULL,
    version INT NOT NULL DEFAULT 0,
    id_modifie_par INT NULL,
    date_modification DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_parametre_site_user FOREIGN KEY (id_modifie_par) REFERENCES `user`(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS parametre_site_historique (
    id_historique INT AUTO_INCREMENT PRIMARY KEY,
    cle VARCHAR(50) NOT NULL,
    version INT NOT NULL,
    ancienne_configuration LONGTEXT NOT NULL,
    nouvelle_configuration LONGTEXT NOT NULL,
    id_modifie_par INT NULL,
    date_modification DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_parametre_site_historique_user FOREIGN KEY (id_modifie_par) REFERENCES `user`(id) ON DELETE SET NULL,
    UNIQUE KEY idx_parametre_site_version (cle, version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO parametre_site_historique (cle, version, ancienne_configuration, nouvelle_configuration, id_modifie_par)
SELECT cle, version + 1, configuration,
       JSON_SET(configuration,
           '$.dons', JSON_QUERY('{"gestionnaire": true, "moderateur": false}', '$'),
           '$.dons_lecture', JSON_QUERY('{"gestionnaire": true, "moderateur": true}', '$'),
           '$.moderation', JSON_QUERY('{"gestionnaire": false, "moderateur": true}', '$'),
           '$.ventes', JSON_QUERY('{"gestionnaire": true, "moderateur": true}', '$')),
       NULL
FROM parametre_site
WHERE cle = 'droits_equipe' AND JSON_VALID(configuration) AND JSON_CONTAINS_PATH(configuration, 'one', '$.dons') = 0;

UPDATE parametre_site
SET configuration = JSON_SET(configuration,
        '$.dons', JSON_QUERY('{"gestionnaire": true, "moderateur": false}', '$'),
        '$.dons_lecture', JSON_QUERY('{"gestionnaire": true, "moderateur": true}', '$'),
        '$.moderation', JSON_QUERY('{"gestionnaire": false, "moderateur": true}', '$'),
        '$.ventes', JSON_QUERY('{"gestionnaire": true, "moderateur": true}', '$')),
    version = version + 1,
    id_modifie_par = NULL,
    date_modification = NOW()
WHERE cle = 'droits_equipe' AND JSON_VALID(configuration) AND JSON_CONTAINS_PATH(configuration, 'one', '$.dons') = 0;
