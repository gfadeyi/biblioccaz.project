SET NAMES utf8mb4;
USE biblioccaz;

ALTER TABLE evenement ADD COLUMN IF NOT EXISTS motif_refus     VARCHAR(500) NULL;
ALTER TABLE evenement ADD COLUMN IF NOT EXISTS date_validation DATETIME     NULL;
ALTER TABLE evenement ADD COLUMN IF NOT EXISTS id_validateur   INT          NULL;

SET @fk := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'evenement' AND CONSTRAINT_NAME = 'fk_evenement_validateur');
SET @sql := IF(@fk = 0,
  'ALTER TABLE evenement ADD CONSTRAINT fk_evenement_validateur FOREIGN KEY (id_validateur) REFERENCES `user`(id) ON DELETE SET NULL',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

CREATE TABLE IF NOT EXISTS evenement_livre (
  id_evenement INT NOT NULL,
  id_livre     INT NOT NULL,
  PRIMARY KEY (id_evenement, id_livre),
  CONSTRAINT fk_evtlivre_evenement FOREIGN KEY (id_evenement) REFERENCES evenement(id_evenement) ON DELETE CASCADE,
  CONSTRAINT fk_evtlivre_livre     FOREIGN KEY (id_livre)     REFERENCES livre(id_livre)         ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
