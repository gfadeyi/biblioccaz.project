SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS collection_serie (
    id_user INT NOT NULL,
    id_serie INT NOT NULL,
    date_ajout DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_user, id_serie),
    CONSTRAINT fk_collection_serie_user FOREIGN KEY (id_user) REFERENCES user(id) ON DELETE CASCADE,
    CONSTRAINT fk_collection_serie_serie FOREIGN KEY (id_serie) REFERENCES serie(id_serie) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS collection_volume (
    id_user INT NOT NULL,
    id_serie INT NOT NULL,
    cle_volume VARCHAR(190) NOT NULL,
    id_livre INT NOT NULL,
    PRIMARY KEY (id_user, id_serie, cle_volume),
    INDEX idx_collection_volume_livre (id_livre),
    CONSTRAINT fk_collection_volume_suivi FOREIGN KEY (id_user, id_serie) REFERENCES collection_serie(id_user, id_serie) ON DELETE CASCADE,
    CONSTRAINT fk_collection_volume_livre FOREIGN KEY (id_livre) REFERENCES livre(id_livre) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
