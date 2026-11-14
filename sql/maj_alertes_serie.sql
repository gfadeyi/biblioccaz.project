SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS alerte_serie (
  id_alerte_serie INT AUTO_INCREMENT PRIMARY KEY,
  id_user         INT      NOT NULL,
  id_serie        INT      NOT NULL,
  date_creation   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY un_alerte_serie (id_user, id_serie),
  INDEX idx_alerte_serie_serie (id_serie),
  CONSTRAINT fk_alerte_serie_user  FOREIGN KEY (id_user)  REFERENCES `user`(id)     ON DELETE CASCADE,
  CONSTRAINT fk_alerte_serie_serie FOREIGN KEY (id_serie) REFERENCES serie(id_serie) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS alerte_serie_envoi (
  id_user    INT      NOT NULL,
  id_livre   INT      NOT NULL,
  date_envoi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_user, id_livre),
  INDEX idx_alerte_serie_envoi_livre (id_livre),
  CONSTRAINT fk_alerte_serie_envoi_user  FOREIGN KEY (id_user)  REFERENCES `user`(id)       ON DELETE CASCADE,
  CONSTRAINT fk_alerte_serie_envoi_livre FOREIGN KEY (id_livre) REFERENCES livre(id_livre) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
