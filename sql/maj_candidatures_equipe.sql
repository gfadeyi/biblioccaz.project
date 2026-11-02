SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS candidature_equipe (
  id_candidature   INT           NOT NULL AUTO_INCREMENT,
  role_vise        VARCHAR(20)   NOT NULL,
  nom              VARCHAR(100)  NOT NULL,
  prenom           VARCHAR(100)  NOT NULL,
  pseudo_souhaite  VARCHAR(50)   NOT NULL,
  email            VARCHAR(150)  NOT NULL,
  motivations      TEXT          NOT NULL,
  id_user          INT           NULL,
  statut           VARCHAR(20)   NOT NULL DEFAULT 'en_attente',
  empreinte_ip     CHAR(64)      NULL,
  date_candidature DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_decision    DATETIME      NULL,
  id_decideur      INT           NULL,
  pseudo_attribue  VARCHAR(50)   NULL,
  message_decision VARCHAR(1000) NULL,
  PRIMARY KEY (id_candidature),
  KEY idx_candidature_equipe_statut (statut, date_candidature),
  KEY idx_candidature_equipe_email (email),
  KEY idx_candidature_equipe_ip (empreinte_ip, date_candidature),
  CONSTRAINT fk_candidature_equipe_user     FOREIGN KEY (id_user)     REFERENCES `user`(id) ON DELETE SET NULL,
  CONSTRAINT fk_candidature_equipe_decideur FOREIGN KEY (id_decideur) REFERENCES `user`(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO candidature_equipe (role_vise, nom, prenom, pseudo_souhaite, email, motivations, id_user, statut, date_candidature, date_decision, id_decideur, pseudo_attribue, message_decision)
SELECT 'moderateur', COALESCE(u.nom, ''), COALESCE(u.prenom, ''), u.pseudo, u.email, COALESCE(c.motivations, ''), u.id,
       c.statut, c.date_candidature, c.date_decision, c.id_decideur, IF(c.statut = 'acceptee', u.pseudo, NULL), c.message_decision
FROM candidature_moderateur c
INNER JOIN `user` u ON u.id = c.id_user
WHERE NOT EXISTS (SELECT 1 FROM candidature_equipe ce WHERE ce.id_user = c.id_user AND ce.date_candidature = c.date_candidature);
