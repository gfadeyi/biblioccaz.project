SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS retour_commande (
    id_retour INT AUTO_INCREMENT PRIMARY KEY,
    reference VARCHAR(20) NULL,
    id_commande INT NOT NULL,
    id_user INT NOT NULL,
    type VARCHAR(20) NOT NULL,
    motif VARCHAR(30) NOT NULL,
    message VARCHAR(1000) NULL,
    statut VARCHAR(20) NOT NULL DEFAULT 'demande',
    montant DECIMAL(8,2) NOT NULL DEFAULT 0,
    points_retires INT NOT NULL DEFAULT 0,
    note_equipe VARCHAR(500) NULL,
    id_traitant INT NULL,
    date_demande DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_maj DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_retour_reference (reference),
    INDEX idx_retour_statut (statut, date_demande),
    INDEX idx_retour_commande (id_commande),
    INDEX idx_retour_membre (id_user, date_demande),
    CONSTRAINT fk_retour_commande FOREIGN KEY (id_commande) REFERENCES commande(id_commande) ON DELETE CASCADE,
    CONSTRAINT fk_retour_user FOREIGN KEY (id_user) REFERENCES user(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS retour_ligne (
    id_retour INT NOT NULL,
    id_ligne INT NOT NULL,
    quantite INT NOT NULL,
    PRIMARY KEY (id_retour, id_ligne),
    INDEX idx_retour_ligne_ligne (id_ligne),
    CONSTRAINT fk_retour_ligne_retour FOREIGN KEY (id_retour) REFERENCES retour_commande(id_retour) ON DELETE CASCADE,
    CONSTRAINT fk_retour_ligne_ligne FOREIGN KEY (id_ligne) REFERENCES commande_ligne(id_ligne) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS retour_historique (
    id_historique INT AUTO_INCREMENT PRIMARY KEY,
    id_retour INT NOT NULL,
    statut VARCHAR(20) NOT NULL,
    id_acteur INT NULL,
    commentaire VARCHAR(500) NULL,
    date_action DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_retour_historique (id_retour, date_action),
    CONSTRAINT fk_retour_historique_retour FOREIGN KEY (id_retour) REFERENCES retour_commande(id_retour) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
