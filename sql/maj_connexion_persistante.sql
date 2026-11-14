SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS connexion_persistante (
    id_connexion INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    selecteur CHAR(24) NOT NULL,
    empreinte_validateur CHAR(64) NOT NULL,
    empreinte_mdp CHAR(64) NOT NULL,
    appareil VARCHAR(60) NULL,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    derniere_utilisation DATETIME NULL,
    date_expiration DATETIME NOT NULL,
    UNIQUE KEY uq_connexion_persistante_selecteur (selecteur),
    INDEX idx_connexion_persistante_membre (id_user, date_expiration),
    CONSTRAINT fk_connexion_persistante_user FOREIGN KEY (id_user) REFERENCES user(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
