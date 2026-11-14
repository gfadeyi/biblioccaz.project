SET NAMES utf8mb4;

ALTER TABLE commande ADD COLUMN IF NOT EXISTS etape_suivi TINYINT NOT NULL DEFAULT 0;

UPDATE commande SET etape_suivi = CASE
    WHEN date_commande <= NOW() - INTERVAL 3 DAY THEN 3
    WHEN date_commande <= NOW() - INTERVAL 1 DAY THEN 2
    WHEN date_commande <= NOW() - INTERVAL 4 HOUR THEN 1
    ELSE 0 END
WHERE etape_suivi = 0 AND date_commande <= NOW() - INTERVAL 4 HOUR;
