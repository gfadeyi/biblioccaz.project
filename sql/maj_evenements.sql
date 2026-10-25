USE biblioccaz;

CREATE TABLE IF NOT EXISTS evenement (
  id_evenement    INT AUTO_INCREMENT PRIMARY KEY,
  titre           VARCHAR(200) NOT NULL,
  description     TEXT         NOT NULL,
  type            VARCHAR(20)  NOT NULL,
  auteurs         VARCHAR(255) NULL,
  lieu            VARCHAR(255) NOT NULL,
  date_debut      DATETIME     NOT NULL,
  date_fin        DATETIME     NOT NULL,
  places_max      INT          NOT NULL,
  attente_auto    TINYINT(1)   NOT NULL DEFAULT 1,
  statut          VARCHAR(20)  NOT NULL DEFAULT 'publie',
  image           VARCHAR(255) NULL,
  id_createur     INT          NULL,
  date_creation   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_annulation DATETIME     NULL,
  CONSTRAINT fk_evenement_createur FOREIGN KEY (id_createur) REFERENCES `user`(id) ON DELETE SET NULL,
  INDEX idx_evenement_date (statut, date_debut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS evenement_inscription (
  id_inscription   INT AUTO_INCREMENT PRIMARY KEY,
  id_evenement     INT          NOT NULL,
  id_user          INT          NOT NULL,
  statut           VARCHAR(20)  NOT NULL DEFAULT 'inscrit',
  code             CHAR(32)     NOT NULL UNIQUE,
  code_court       CHAR(6)      NOT NULL UNIQUE,
  date_inscription DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  present          TINYINT(1)   NOT NULL DEFAULT 0,
  date_presence    DATETIME     NULL,
  UNIQUE KEY un_inscription (id_evenement, id_user),
  CONSTRAINT fk_inscription_evenement FOREIGN KEY (id_evenement) REFERENCES evenement(id_evenement) ON DELETE CASCADE,
  CONSTRAINT fk_inscription_user      FOREIGN KEY (id_user)      REFERENCES `user`(id)              ON DELETE CASCADE,
  INDEX idx_inscription_file (id_evenement, statut, date_inscription)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO evenement (titre, description, type, auteurs, lieu, date_debut, date_fin, places_max)
SELECT 'Atelier : donner une seconde vie à ses livres',
       'Réparer une reliure, nettoyer une couverture, protéger ses livres de poche : venez avec un livre abîmé, on le remet en état ensemble.',
       'atelier', NULL, 'Médiathèque, Lyon 3e',
       DATE_ADD(DATE(NOW()), INTERVAL 14 DAY) + INTERVAL 14 HOUR,
       DATE_ADD(DATE(NOW()), INTERVAL 14 DAY) + INTERVAL 16 HOUR, 12
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM evenement);

INSERT INTO evenement (titre, description, type, auteurs, lieu, date_debut, date_fin, places_max)
SELECT 'Club manga : les shōnen de l''année',
       'Une soirée pour échanger sur les séries qui ont marqué l''année, avec une sélection de tomes d''occasion à petit prix.',
       'rencontre', NULL, 'Librairie partenaire, Lyon 7e',
       DATE_ADD(DATE(NOW()), INTERVAL 21 DAY) + INTERVAL 18 HOUR,
       DATE_ADD(DATE(NOW()), INTERVAL 21 DAY) + INTERVAL 20 HOUR, 25
FROM DUAL
WHERE (SELECT COUNT(*) FROM evenement) = 1;
