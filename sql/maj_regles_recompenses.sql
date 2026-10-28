SET NAMES utf8mb4;
USE biblioccaz;

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
