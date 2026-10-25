USE biblioccaz;

CREATE TABLE IF NOT EXISTS avis (
  id_avis           INT AUTO_INCREMENT PRIMARY KEY,
  id_livre          INT          NOT NULL,
  id_user           INT          NOT NULL,
  note              TINYINT      NOT NULL,
  titre             VARCHAR(120) NULL,
  commentaire       TEXT         NOT NULL,
  date_creation     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_modification DATETIME     NULL,
  statut            VARCHAR(20)  NOT NULL DEFAULT 'visible',
  UNIQUE KEY un_avis_par_livre (id_livre, id_user),
  CONSTRAINT fk_avis_livre FOREIGN KEY (id_livre) REFERENCES livre(id_livre) ON DELETE CASCADE,
  CONSTRAINT fk_avis_user  FOREIGN KEY (id_user)  REFERENCES `user`(id)       ON DELETE CASCADE,
  CONSTRAINT chk_avis_note CHECK (note BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS avis_signalement (
  id_signalement    INT AUTO_INCREMENT PRIMARY KEY,
  id_avis           INT          NOT NULL,
  id_user           INT          NOT NULL,
  motif             VARCHAR(50)  NOT NULL,
  date_signalement  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  traite            TINYINT(1)   NOT NULL DEFAULT 0,
  UNIQUE KEY un_signalement (id_avis, id_user),
  CONSTRAINT fk_signal_avis FOREIGN KEY (id_avis) REFERENCES avis(id_avis) ON DELETE CASCADE,
  CONSTRAINT fk_signal_user FOREIGN KEY (id_user) REFERENCES `user`(id)    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
