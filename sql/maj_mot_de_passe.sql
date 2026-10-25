-- =====================================================================
-- BIBLIOccaz - Mise à jour : récupération de mot de passe
-- À importer UNE FOIS dans phpMyAdmin (base biblioccaz > Importer)
-- Ajoute 2 colonnes à la table user, sans toucher aux données.
-- =====================================================================

USE biblioccaz;

ALTER TABLE `user`
  ADD COLUMN IF NOT EXISTS reset_token_hash CHAR(64) NULL,
  ADD COLUMN IF NOT EXISTS reset_expire     DATETIME NULL;
