USE biblioccaz;

CREATE TABLE IF NOT EXISTS newsletter_abonne (
  id_abonne           INT AUTO_INCREMENT PRIMARY KEY,
  email               VARCHAR(150) NOT NULL UNIQUE,
  actif               TINYINT(1)   NOT NULL DEFAULT 1,
  date_inscription    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_desinscription DATETIME     NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO newsletter_abonne (email, actif, date_inscription)
SELECT email, is_newsletter, COALESCE(date_newsletter, NOW()) FROM `user` WHERE role = 'user';

DELETE FROM `user` WHERE role = 'user';
