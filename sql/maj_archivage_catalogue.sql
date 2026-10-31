SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS livre_archive (
    id_livre INT PRIMARY KEY,
    ancienne_validation TINYINT NOT NULL,
    actif TINYINT NOT NULL DEFAULT 1,
    id_archive_par INT NULL,
    date_archivage DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_reactivation DATETIME NULL,
    CONSTRAINT fk_livre_archive_livre FOREIGN KEY (id_livre) REFERENCES livre(id_livre) ON DELETE CASCADE,
    CONSTRAINT fk_livre_archive_user FOREIGN KEY (id_archive_par) REFERENCES `user`(id) ON DELETE SET NULL,
    INDEX idx_livre_archive_actif (actif, id_livre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
