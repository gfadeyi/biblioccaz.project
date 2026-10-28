SET NAMES utf8mb4;
USE biblioccaz;

CREATE TABLE IF NOT EXISTS session_utilisateur (
    id_suivi INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    empreinte CHAR(64) NOT NULL UNIQUE,
    empreinte_mdp CHAR(64) NOT NULL,
    espace ENUM('site', 'admin') NOT NULL,
    date_connexion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    derniere_activite DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_revocation DATETIME NULL,
    id_revoque_par INT NULL,
    INDEX idx_session_membre (id_user, date_revocation, derniere_activite),
    INDEX idx_session_activite (date_revocation, derniere_activite),
    FOREIGN KEY (id_user) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (id_revoque_par) REFERENCES user(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
