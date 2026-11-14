SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS demande_client (
    id_demande INT AUTO_INCREMENT PRIMARY KEY,
    reference VARCHAR(20) NULL,
    id_user INT NOT NULL,
    categorie VARCHAR(30) NOT NULL,
    sujet VARCHAR(150) NOT NULL,
    statut VARCHAR(20) NOT NULL DEFAULT 'nouvelle',
    id_commande INT NULL,
    id_assigne INT NULL,
    non_lu_client TINYINT(1) NOT NULL DEFAULT 0,
    non_lu_equipe TINYINT(1) NOT NULL DEFAULT 1,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_maj DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_cloture DATETIME NULL,
    UNIQUE KEY uq_demande_client_reference (reference),
    INDEX idx_demande_client_statut (statut, date_maj),
    INDEX idx_demande_client_membre (id_user, date_maj),
    INDEX idx_demande_client_assigne (id_assigne, statut),
    CONSTRAINT fk_demande_client_user FOREIGN KEY (id_user) REFERENCES user(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS demande_client_message (
    id_message INT AUTO_INCREMENT PRIMARY KEY,
    id_demande INT NOT NULL,
    id_auteur INT NULL,
    cote VARCHAR(10) NOT NULL,
    contenu TEXT NOT NULL,
    date_envoi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_demande_client_message (id_demande, date_envoi),
    INDEX idx_demande_client_message_auteur (id_auteur, date_envoi),
    CONSTRAINT fk_demande_client_message FOREIGN KEY (id_demande) REFERENCES demande_client(id_demande) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
