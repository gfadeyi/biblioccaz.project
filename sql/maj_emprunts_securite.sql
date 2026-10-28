SET NAMES utf8mb4;
USE biblioccaz;

ALTER TABLE emprunt ADD COLUMN IF NOT EXISTS blocage_retard TINYINT(1) NULL AFTER renouvellements_max;
UPDATE emprunt em INNER JOIN exemplaire e ON e.id_exemplaire = em.id_exemplaire SET em.blocage_retard = e.blocage_retard WHERE em.blocage_retard IS NULL;
ALTER TABLE emprunt MODIFY COLUMN blocage_retard TINYINT(1) NOT NULL DEFAULT 1;
