SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS don_moderation (
    id_moderation INT AUTO_INCREMENT PRIMARY KEY,
    id_don INT NOT NULL,
    id_moderateur INT NULL,
    action VARCHAR(20) NOT NULL,
    motif VARCHAR(500) NOT NULL,
    ancien_statut VARCHAR(20) NOT NULL,
    nouveau_statut VARCHAR(20) NOT NULL,
    id_beneficiaire INT NULL,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_don_moderation_don FOREIGN KEY (id_don) REFERENCES don(id_don) ON DELETE CASCADE,
    CONSTRAINT fk_don_moderation_equipe FOREIGN KEY (id_moderateur) REFERENCES `user`(id) ON DELETE SET NULL,
    CONSTRAINT fk_don_moderation_beneficiaire FOREIGN KEY (id_beneficiaire) REFERENCES `user`(id) ON DELETE SET NULL,
    INDEX idx_don_moderation_historique (id_don, id_moderation)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
