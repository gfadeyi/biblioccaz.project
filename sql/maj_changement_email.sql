SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS changement_email (
    id_user INT NOT NULL PRIMARY KEY,
    nouvelle_email VARCHAR(150) NULL,
    ancienne_email VARCHAR(150) NULL,
    jeton_hash CHAR(64) NULL,
    empreinte_mdp CHAR(64) NULL,
    date_expiration DATETIME NULL,
    derniere_tentative DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    debut_fenetre DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tentatives TINYINT NOT NULL DEFAULT 1,
    UNIQUE KEY uq_changement_email_jeton (jeton_hash),
    CONSTRAINT fk_changement_email_user FOREIGN KEY (id_user) REFERENCES user(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
