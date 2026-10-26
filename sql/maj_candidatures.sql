SET NAMES utf8mb4;
USE biblioccaz;

CREATE TABLE IF NOT EXISTS candidature_moderateur (
  id_candidature   INT          NOT NULL AUTO_INCREMENT,
  id_user          INT          NOT NULL,
  motivations      TEXT         NULL,
  statut           VARCHAR(20)  NOT NULL DEFAULT 'en_attente',
  date_candidature DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_decision    DATETIME     NULL,
  id_decideur      INT          NULL,
  message_decision VARCHAR(1000) NULL,
  PRIMARY KEY (id_candidature),
  KEY idx_candidature_statut (statut, date_candidature),
  CONSTRAINT fk_candidature_user     FOREIGN KEY (id_user)     REFERENCES `user`(id) ON DELETE CASCADE,
  CONSTRAINT fk_candidature_decideur FOREIGN KEY (id_decideur) REFERENCES `user`(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO candidature_moderateur (id_user, motivations, date_candidature)
SELECT u.id, NULL, u.date_inscription
FROM `user` u
WHERE u.statut = 'en_attente_moderateur'
  AND NOT EXISTS (SELECT 1 FROM candidature_moderateur c WHERE c.id_user = u.id);
