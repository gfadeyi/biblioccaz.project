SET NAMES utf8mb4;

INSERT IGNORE INTO categorie (nom, slug, ordre, id_parent)
  SELECT 'Spiritualité & religions', 'spiritualite-religions', 6, u.id_categorie
  FROM categorie u WHERE u.slug = 'art-culture';
