USE biblioccaz;

CREATE TABLE IF NOT EXISTS newsletter_historique (
  id_historique INT AUTO_INCREMENT PRIMARY KEY,
  email         VARCHAR(150) NOT NULL,
  id_user       INT          NULL,
  action        VARCHAR(20)  NOT NULL,
  source        VARCHAR(20)  NOT NULL,
  date_action   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_newsletter_hist_user FOREIGN KEY (id_user) REFERENCES `user`(id) ON DELETE SET NULL,
  INDEX idx_newsletter_hist_date (date_action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO newsletter_historique (email, id_user, action, source, date_action)
SELECT u.email, u.id, IF(u.is_newsletter = 1, 'inscription', 'desinscription'), 'reprise', u.date_newsletter
FROM `user` u
WHERE date_newsletter IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM newsletter_historique h WHERE h.email = u.email);

INSERT INTO newsletter_historique (email, id_user, action, source, date_action)
SELECT a.email, NULL, IF(a.actif = 1, 'inscription', 'desinscription'), 'reprise', COALESCE(a.date_desinscription, a.date_inscription)
FROM newsletter_abonne a
WHERE NOT EXISTS (SELECT 1 FROM newsletter_historique h WHERE h.email = a.email);
