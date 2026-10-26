SET NAMES utf8mb4;
USE biblioccaz;

ALTER TABLE commande_ligne ADD COLUMN IF NOT EXISTS id_exemplaire INT NULL AFTER id_livre;
ALTER TABLE commande_ligne ADD INDEX IF NOT EXISTS idx_ligne_exemplaire (id_exemplaire);

ALTER TABLE commande ADD COLUMN IF NOT EXISTS id_annulateur    INT          NULL;
ALTER TABLE commande ADD COLUMN IF NOT EXISTS motif_annulation VARCHAR(255) NULL;
ALTER TABLE commande ADD INDEX IF NOT EXISTS idx_commande_statut_date (statut, date_commande);

SET @fk := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'commande_ligne' AND CONSTRAINT_NAME = 'fk_ligne_exemplaire');
SET @sql := IF(@fk = 0,
  'ALTER TABLE commande_ligne ADD CONSTRAINT fk_ligne_exemplaire FOREIGN KEY (id_exemplaire) REFERENCES exemplaire(id_exemplaire) ON DELETE SET NULL',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @fk := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'commande' AND CONSTRAINT_NAME = 'fk_commande_annulateur');
SET @sql := IF(@fk = 0,
  'ALTER TABLE commande ADD CONSTRAINT fk_commande_annulateur FOREIGN KEY (id_annulateur) REFERENCES `user`(id) ON DELETE SET NULL',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

UPDATE commande_ligne cl
INNER JOIN (
  SELECT id_livre, etat, prix, MIN(id_exemplaire) AS id_exemplaire
  FROM exemplaire
  GROUP BY id_livre, etat, prix
) e ON e.id_livre = cl.id_livre AND e.etat = cl.etat AND e.prix = cl.prix_unitaire
SET cl.id_exemplaire = e.id_exemplaire
WHERE cl.id_exemplaire IS NULL;
