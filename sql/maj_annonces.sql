SET NAMES utf8mb4;
USE biblioccaz;

ALTER TABLE exemplaire ADD COLUMN IF NOT EXISTS statut_annonce   VARCHAR(20)  NULL AFTER id_proprietaire;
ALTER TABLE exemplaire ADD COLUMN IF NOT EXISTS description      TEXT         NULL AFTER statut_annonce;
ALTER TABLE exemplaire ADD COLUMN IF NOT EXISTS mode_remise      VARCHAR(20)  NULL AFTER description;
ALTER TABLE exemplaire ADD COLUMN IF NOT EXISTS ville_remise     VARCHAR(100) NULL AFTER mode_remise;
ALTER TABLE exemplaire ADD COLUMN IF NOT EXISTS motif_moderation VARCHAR(500) NULL AFTER ville_remise;
ALTER TABLE exemplaire ADD COLUMN IF NOT EXISTS id_moderateur    INT          NULL AFTER motif_moderation;
ALTER TABLE exemplaire ADD COLUMN IF NOT EXISTS date_creation    DATETIME     NULL AFTER id_moderateur;
ALTER TABLE exemplaire ADD COLUMN IF NOT EXISTS date_moderation  DATETIME     NULL AFTER date_creation;
ALTER TABLE exemplaire ADD INDEX IF NOT EXISTS idx_exemplaire_annonce (statut_annonce, id_proprietaire);

SET @fk := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'exemplaire' AND CONSTRAINT_NAME = 'fk_exemplaire_moderateur');
SET @sql := IF(@fk = 0,
  'ALTER TABLE exemplaire ADD CONSTRAINT fk_exemplaire_moderateur FOREIGN KEY (id_moderateur) REFERENCES `user`(id) ON DELETE SET NULL',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

CREATE TABLE IF NOT EXISTS exemplaire_photo (
  id_photo      INT AUTO_INCREMENT PRIMARY KEY,
  id_exemplaire INT          NOT NULL,
  fichier       VARCHAR(255) NOT NULL,
  ordre         TINYINT      NOT NULL DEFAULT 0,
  INDEX idx_photo_exemplaire (id_exemplaire, ordre),
  CONSTRAINT fk_photo_exemplaire FOREIGN KEY (id_exemplaire)
    REFERENCES exemplaire(id_exemplaire) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
