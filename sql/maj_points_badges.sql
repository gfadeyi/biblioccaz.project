SET NAMES utf8mb4;
USE biblioccaz;

CREATE TABLE IF NOT EXISTS point_mouvement (
  id_mouvement   INT AUTO_INCREMENT PRIMARY KEY,
  id_user        INT          NOT NULL,
  points         INT          NOT NULL,
  type           VARCHAR(30)  NOT NULL,
  libelle        VARCHAR(255) NOT NULL,
  reference      VARCHAR(60)  NULL,
  statut         VARCHAR(20)  NOT NULL DEFAULT 'valide',
  date_mouvement DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY un_mouvement_reference (id_user, type, reference),
  CONSTRAINT fk_mouvement_user FOREIGN KEY (id_user) REFERENCES `user`(id) ON DELETE CASCADE,
  INDEX idx_mouvement_user (id_user, statut, date_mouvement)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS badge_obtenu (
  id_user        INT          NOT NULL,
  code           VARCHAR(40)  NOT NULL,
  palier         TINYINT      NOT NULL DEFAULT 1,
  date_obtention DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  vu             TINYINT(1)   NOT NULL DEFAULT 0,
  PRIMARY KEY (id_user, code, palier),
  CONSTRAINT fk_badge_user FOREIGN KEY (id_user) REFERENCES `user`(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS bon_cadeau (
  id_bon           INT AUTO_INCREMENT PRIMARY KEY,
  id_user          INT          NOT NULL,
  montant          DECIMAL(8,2) NOT NULL,
  motif            VARCHAR(40)  NOT NULL,
  annee            INT          NOT NULL,
  date_creation    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_expiration  DATE         NOT NULL,
  date_utilisation DATETIME     NULL,
  id_commande      INT          NULL,
  UNIQUE KEY un_bon_annee (id_user, motif, annee),
  CONSTRAINT fk_bon_user     FOREIGN KEY (id_user)     REFERENCES `user`(id)            ON DELETE CASCADE,
  CONSTRAINT fk_bon_commande FOREIGN KEY (id_commande) REFERENCES commande(id_commande) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `user` ADD COLUMN IF NOT EXISTS niveau_vu TINYINT NOT NULL DEFAULT 0;

ALTER TABLE commande ADD COLUMN IF NOT EXISTS statut           VARCHAR(20)  NOT NULL DEFAULT 'validee';
ALTER TABLE commande ADD COLUMN IF NOT EXISTS date_annulation  DATETIME     NULL;
ALTER TABLE commande ADD COLUMN IF NOT EXISTS points_utilises  INT          NOT NULL DEFAULT 0;
ALTER TABLE commande ADD COLUMN IF NOT EXISTS reduction_points DECIMAL(8,2) NOT NULL DEFAULT 0;
ALTER TABLE commande ADD COLUMN IF NOT EXISTS bonus_niveau     INT          NOT NULL DEFAULT 0;
ALTER TABLE commande ADD COLUMN IF NOT EXISTS id_bon_cadeau    INT          NULL;

ALTER TABLE avis ADD COLUMN IF NOT EXISTS date_validation DATETIME NULL;
ALTER TABLE avis ADD COLUMN IF NOT EXISTS id_validateur   INT      NULL;

ALTER TABLE evenement_inscription ADD COLUMN IF NOT EXISTS prioritaire TINYINT(1) NOT NULL DEFAULT 0;

SET @fk := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'avis' AND CONSTRAINT_NAME = 'fk_avis_validateur');
SET @sql := IF(@fk = 0,
  'ALTER TABLE avis ADD CONSTRAINT fk_avis_validateur FOREIGN KEY (id_validateur) REFERENCES `user`(id) ON DELETE SET NULL',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @fk := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'commande' AND CONSTRAINT_NAME = 'fk_commande_bon');
SET @sql := IF(@fk = 0,
  'ALTER TABLE commande ADD CONSTRAINT fk_commande_bon FOREIGN KEY (id_bon_cadeau) REFERENCES bon_cadeau(id_bon) ON DELETE SET NULL',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

UPDATE avis SET date_validation = date_creation WHERE statut = 'visible' AND date_validation IS NULL;

INSERT IGNORE INTO point_mouvement (id_user, points, type, libelle, reference, date_mouvement)
SELECT c.id_user, c.points_gagnes, 'achat', CONCAT('Commande n° ', c.id_commande), CONCAT('commande:', c.id_commande), c.date_commande
FROM commande c
WHERE c.points_gagnes > 0;

INSERT IGNORE INTO point_mouvement (id_user, points, type, libelle, reference, date_mouvement)
SELECT a.id_user, 5, 'avis', CONCAT('Avis sur « ', l.titre, ' »'), CONCAT('livre:', a.id_livre), a.date_creation
FROM avis a
INNER JOIN livre l ON l.id_livre = a.id_livre
INNER JOIN `user` u ON u.id = a.id_user AND u.role = 'client'
WHERE a.statut = 'visible';

INSERT IGNORE INTO point_mouvement (id_user, points, type, libelle, reference, date_mouvement)
SELECT d.id_donneur, 20, 'don', CONCAT('Don récupéré : « ', d.titre, ' »'), CONCAT('don:', d.id_don), COALESCE(d.date_recuperation, d.date_creation)
FROM don d
WHERE d.statut = 'recupere';

INSERT IGNORE INTO point_mouvement (id_user, points, type, libelle, reference, date_mouvement)
SELECT u.id, u.solde_points - COALESCE(m.total, 0), 'ajustement', 'Reprise du solde existant', 'reprise', NOW()
FROM `user` u
LEFT JOIN (SELECT id_user, SUM(points) AS total FROM point_mouvement WHERE statut = 'valide' GROUP BY id_user) m ON m.id_user = u.id
WHERE u.solde_points - COALESCE(m.total, 0) <> 0;
