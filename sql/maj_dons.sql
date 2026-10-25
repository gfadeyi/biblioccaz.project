USE biblioccaz;

CREATE TABLE IF NOT EXISTS don (
  id_don             INT AUTO_INCREMENT PRIMARY KEY,
  id_donneur         INT          NOT NULL,
  id_livre           INT          NULL,
  id_categorie       INT          NULL,
  titre              VARCHAR(255) NOT NULL,
  auteur             VARCHAR(255) NULL,
  etat               VARCHAR(50)  NOT NULL,
  description        TEXT         NULL,
  photo              VARCHAR(255) NULL,
  lieu_retrait       VARCHAR(255) NOT NULL,
  conditions_retrait VARCHAR(255) NULL,
  date_limite        DATE         NOT NULL,
  statut             VARCHAR(20)  NOT NULL DEFAULT 'disponible',
  code               CHAR(32)     NOT NULL UNIQUE,
  code_court         CHAR(6)      NOT NULL UNIQUE,
  id_beneficiaire    INT          NULL,
  date_creation      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_reservation   DATETIME     NULL,
  date_recuperation  DATETIME     NULL,
  CONSTRAINT fk_don_donneur     FOREIGN KEY (id_donneur)      REFERENCES `user`(id)              ON DELETE CASCADE,
  CONSTRAINT fk_don_beneficiaire FOREIGN KEY (id_beneficiaire) REFERENCES `user`(id)             ON DELETE SET NULL,
  CONSTRAINT fk_don_livre       FOREIGN KEY (id_livre)        REFERENCES livre(id_livre)         ON DELETE SET NULL,
  CONSTRAINT fk_don_categorie   FOREIGN KEY (id_categorie)    REFERENCES categorie(id_categorie) ON DELETE SET NULL,
  INDEX idx_don_statut (statut, date_limite)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
