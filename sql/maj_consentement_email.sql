SET NAMES utf8mb4;

ALTER TABLE `user` ADD COLUMN IF NOT EXISTS email_token_hash CHAR(64) NULL;
ALTER TABLE `user` ADD COLUMN IF NOT EXISTS email_token_expire DATETIME NULL;
ALTER TABLE `user` ADD UNIQUE INDEX IF NOT EXISTS uq_user_email_token_hash (email_token_hash);

UPDATE `user` SET email_token_hash = SHA2(email_token, 256), email_token_expire = DATE_ADD(NOW(), INTERVAL 24 HOUR), email_token = NULL
WHERE email_verifie = 0 AND email_token_hash IS NULL AND email_token REGEXP '^[a-f0-9]{64}$';
UPDATE `user` SET email_token = NULL WHERE email_token IS NOT NULL;

CREATE TABLE IF NOT EXISTS newsletter_consentement (
    email VARCHAR(150) NOT NULL PRIMARY KEY,
    actif TINYINT(1) NOT NULL DEFAULT 0,
    confirmation_hash CHAR(64) NULL,
    confirmation_expire DATETIME NULL,
    date_demande DATETIME NULL,
    date_confirmation DATETIME NULL,
    date_desinscription DATETIME NULL,
    generation INT UNSIGNED NOT NULL DEFAULT 0,
    source VARCHAR(20) NOT NULL DEFAULT 'site',
    notice VARCHAR(255) NOT NULL DEFAULT '',
    UNIQUE KEY uq_newsletter_confirmation (confirmation_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS newsletter_desinscription (
    empreinte CHAR(64) NOT NULL PRIMARY KEY,
    email VARCHAR(150) NOT NULL,
    generation INT UNSIGNED NOT NULL,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_newsletter_desinscription_email (email),
    CONSTRAINT fk_newsletter_desinscription_email FOREIGN KEY (email) REFERENCES newsletter_consentement(email) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO newsletter_consentement (email, actif, generation, source)
SELECT email, 1, 1, 'reprise' FROM `user` WHERE is_newsletter = 1;
INSERT IGNORE INTO newsletter_consentement (email, actif, generation, source)
SELECT email, 1, 1, 'reprise' FROM newsletter_abonne WHERE actif = 1;
