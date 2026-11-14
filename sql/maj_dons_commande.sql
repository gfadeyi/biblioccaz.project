SET NAMES utf8mb4;

ALTER TABLE don ADD COLUMN IF NOT EXISTS id_commande_ligne INT NULL AFTER id_livre;
ALTER TABLE don ADD INDEX IF NOT EXISTS idx_don_commande_ligne (id_commande_ligne);
