SET NAMES utf8mb4;

ALTER TABLE emprunt_attente ADD COLUMN IF NOT EXISTS prioritaire TINYINT(1) NOT NULL DEFAULT 0 AFTER date_demande;
ALTER TABLE emprunt_attente ADD INDEX IF NOT EXISTS idx_attente_priorite (id_livre, prioritaire, date_demande);
