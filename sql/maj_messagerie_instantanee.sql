SET NAMES utf8mb4;

ALTER TABLE conversation_participant ADD COLUMN IF NOT EXISTS date_ecriture DATETIME NULL AFTER date_dernier_mail;
