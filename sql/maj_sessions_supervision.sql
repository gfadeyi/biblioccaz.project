SET NAMES utf8mb4;
USE biblioccaz;

ALTER TABLE session_utilisateur ADD COLUMN IF NOT EXISTS role_connexion VARCHAR(20) NULL AFTER espace;
