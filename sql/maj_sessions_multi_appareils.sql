ALTER TABLE session_utilisateur
    ADD COLUMN IF NOT EXISTS id_connexion_persistante INT NULL AFTER appareil,
    ADD INDEX IF NOT EXISTS idx_session_persistante (id_connexion_persistante);
