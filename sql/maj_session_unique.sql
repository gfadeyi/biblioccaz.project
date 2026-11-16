SET NAMES utf8mb4;

ALTER TABLE session_utilisateur
    ADD COLUMN IF NOT EXISTS motif_revocation VARCHAR(30) NULL AFTER id_revoque_par,
    DROP INDEX IF EXISTS idx_session_persistante,
    DROP COLUMN IF EXISTS id_connexion_persistante;

DROP TABLE IF EXISTS connexion_persistante;
