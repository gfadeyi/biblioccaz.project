SET NAMES utf8mb4;
USE biblioccaz;

ALTER TABLE session_utilisateur ADD COLUMN IF NOT EXISTS appareil VARCHAR(60) NULL AFTER role_connexion;
