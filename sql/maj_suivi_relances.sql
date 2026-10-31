SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS relance_execution (
    id_execution INT AUTO_INCREMENT PRIMARY KEY,
    date_debut DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_fin DATETIME NULL,
    statut VARCHAR(20) NOT NULL DEFAULT 'en_cours',
    rappels INT NOT NULL DEFAULT 0,
    expirations INT NOT NULL DEFAULT 0,
    erreurs INT NOT NULL DEFAULT 0,
    INDEX idx_relance_execution_date (date_debut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
