SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS equipe_decision (
    id_decision BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_acteur INT NULL,
    pseudo_acteur VARCHAR(50) NOT NULL,
    type_objet VARCHAR(24) NOT NULL,
    id_objet INT NOT NULL,
    libelle_objet VARCHAR(255) NOT NULL,
    action VARCHAR(24) NOT NULL,
    motif TEXT NULL,
    ancien_statut VARCHAR(40) NULL,
    nouveau_statut VARCHAR(40) NULL,
    date_decision DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_decision_date (date_decision, id_decision),
    INDEX idx_decision_objet (type_objet, id_objet, date_decision),
    INDEX idx_decision_acteur (id_acteur, date_decision),
    CONSTRAINT fk_decision_acteur FOREIGN KEY (id_acteur) REFERENCES `user`(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
