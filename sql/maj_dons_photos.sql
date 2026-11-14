SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS don_photo (
    id_photo INT AUTO_INCREMENT PRIMARY KEY,
    id_don INT NOT NULL,
    fichier VARCHAR(255) NOT NULL,
    ordre TINYINT NOT NULL DEFAULT 0,
    date_ajout DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_don_photo_don (id_don, ordre),
    CONSTRAINT fk_don_photo_don FOREIGN KEY (id_don) REFERENCES don(id_don) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
