SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS panier_reservation (
    id_panier CHAR(64) NOT NULL,
    id_exemplaire INT NOT NULL,
    quantite SMALLINT NOT NULL,
    date_expiration DATETIME NOT NULL,
    PRIMARY KEY (id_panier, id_exemplaire),
    INDEX idx_panier_reservation_stock (id_exemplaire, date_expiration),
    INDEX idx_panier_reservation_expiration (date_expiration),
    CONSTRAINT fk_panier_reservation_exemplaire FOREIGN KEY (id_exemplaire) REFERENCES exemplaire(id_exemplaire) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS serie (
    id_serie INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(200) NOT NULL,
    auteur VARCHAR(255) NOT NULL DEFAULT '',
    UNIQUE KEY idx_serie_nom_auteur (nom, auteur)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS livre_serie (
    id_livre INT PRIMARY KEY,
    id_serie INT NOT NULL,
    numero_tome DECIMAL(7,2) NOT NULL,
    INDEX idx_livre_serie_ordre (id_serie, numero_tome, id_livre),
    CONSTRAINT fk_livre_serie_livre FOREIGN KEY (id_livre) REFERENCES livre(id_livre) ON DELETE CASCADE,
    CONSTRAINT fk_livre_serie_serie FOREIGN KEY (id_serie) REFERENCES serie(id_serie) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE livre ADD COLUMN IF NOT EXISTS prix_neuf DECIMAL(8,2) NULL;

CREATE TABLE IF NOT EXISTS connexion_admin_limite (
    cle CHAR(64) PRIMARY KEY,
    debut_fenetre DATETIME NOT NULL,
    echecs SMALLINT NOT NULL DEFAULT 0,
    bloque_jusqua DATETIME NULL,
    INDEX idx_connexion_admin_fenetre (debut_fenetre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
