USE biblioccaz;

CREATE TABLE IF NOT EXISTS favori (
  id_user     INT      NOT NULL,
  id_livre    INT      NOT NULL,
  date_ajout  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_user, id_livre),
  CONSTRAINT fk_favori_user  FOREIGN KEY (id_user)  REFERENCES `user`(id)       ON DELETE CASCADE,
  CONSTRAINT fk_favori_livre FOREIGN KEY (id_livre) REFERENCES livre(id_livre) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS alerte_stock (
  id_alerte     INT AUTO_INCREMENT PRIMARY KEY,
  id_user       INT      NOT NULL,
  id_livre      INT      NOT NULL,
  date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_envoi    DATETIME NULL,
  UNIQUE KEY un_alerte (id_user, id_livre),
  CONSTRAINT fk_alerte_user  FOREIGN KEY (id_user)  REFERENCES `user`(id)       ON DELETE CASCADE,
  CONSTRAINT fk_alerte_livre FOREIGN KEY (id_livre) REFERENCES livre(id_livre) ON DELETE CASCADE,
  INDEX idx_alerte_livre (id_livre, date_envoi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS adresse_livraison (
  id_adresse    INT AUTO_INCREMENT PRIMARY KEY,
  id_user       INT          NOT NULL,
  libelle       VARCHAR(50)  NOT NULL,
  prenom        VARCHAR(100) NOT NULL,
  nom           VARCHAR(100) NOT NULL,
  adresse       VARCHAR(255) NOT NULL,
  complement    VARCHAR(255) NULL,
  code_postal   VARCHAR(10)  NOT NULL,
  ville         VARCHAR(100) NOT NULL,
  telephone     VARCHAR(20)  NULL,
  par_defaut    TINYINT(1)   NOT NULL DEFAULT 0,
  date_creation DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_adresse_user FOREIGN KEY (id_user) REFERENCES `user`(id) ON DELETE CASCADE,
  INDEX idx_adresse_user (id_user)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
