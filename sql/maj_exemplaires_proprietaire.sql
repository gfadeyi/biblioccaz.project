SET NAMES utf8mb4;
USE biblioccaz;

ALTER TABLE exemplaire ADD COLUMN IF NOT EXISTS id_proprietaire INT NULL AFTER id_user;
ALTER TABLE exemplaire ADD INDEX IF NOT EXISTS idx_exemplaire_offre (type_offre, id_proprietaire, is_disponible);

SET @fk := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'exemplaire' AND CONSTRAINT_NAME = 'fk_exemplaire_proprietaire');
SET @sql := IF(@fk = 0,
  'ALTER TABLE exemplaire ADD CONSTRAINT fk_exemplaire_proprietaire FOREIGN KEY (id_proprietaire) REFERENCES `user`(id) ON DELETE CASCADE',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

UPDATE exemplaire SET type_offre = 'vente' WHERE type_offre NOT IN ('vente', 'emprunt');
