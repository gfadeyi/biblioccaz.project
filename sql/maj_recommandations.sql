SET NAMES utf8mb4;
USE biblioccaz;

ALTER TABLE `user` ADD COLUMN IF NOT EXISTS telephone VARCHAR(20) NULL;
ALTER TABLE `user` ADD COLUMN IF NOT EXISTS historique_actif TINYINT(1) NOT NULL DEFAULT 1;

CREATE TABLE IF NOT EXISTS preference_categorie (
    id_user INT NOT NULL,
    id_categorie INT NOT NULL,
    PRIMARY KEY (id_user, id_categorie),
    FOREIGN KEY (id_user) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (id_categorie) REFERENCES categorie(id_categorie) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS preference_tag (
    id_user INT NOT NULL,
    id_tag INT NOT NULL,
    PRIMARY KEY (id_user, id_tag),
    FOREIGN KEY (id_user) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (id_tag) REFERENCES tag(id_tag) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS consultation_livre (
    id_user INT NOT NULL,
    id_livre INT NOT NULL,
    date_consultation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_user, id_livre),
    INDEX idx_consultation_membre (id_user, date_consultation),
    FOREIGN KEY (id_user) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (id_livre) REFERENCES livre(id_livre) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
