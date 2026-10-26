SET NAMES utf8mb4;
USE biblioccaz;

ALTER TABLE categorie ADD COLUMN IF NOT EXISTS id_parent   INT          NULL;
ALTER TABLE categorie ADD COLUMN IF NOT EXISTS icone       VARCHAR(30)  NULL;
ALTER TABLE categorie ADD COLUMN IF NOT EXISTS couleur     VARCHAR(20)  NULL;
ALTER TABLE categorie ADD COLUMN IF NOT EXISTS description VARCHAR(255) NULL;
ALTER TABLE categorie ADD INDEX IF NOT EXISTS idx_categorie_parent (id_parent, ordre);

SET @fk := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'categorie' AND CONSTRAINT_NAME = 'fk_categorie_parent');
SET @sql := IF(@fk = 0,
  'ALTER TABLE categorie ADD CONSTRAINT fk_categorie_parent FOREIGN KEY (id_parent) REFERENCES categorie(id_categorie) ON DELETE SET NULL',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

INSERT IGNORE INTO categorie (nom, slug, ordre, icone, couleur, description) VALUES
('BD, Manga & Jeunesse', 'bd-manga-jeunesse', 1, 'bulle', 'bd', 'Mangas, comics, albums, ados'),
('Littérature', 'litterature', 2, 'livre-ouvert', 'litterature', 'Romans, polars, SF, classiques'),
('Art & Culture', 'art-culture', 3, 'plume', 'art', 'Histoire, photo, musique, voyages'),
('Scolaire & Études', 'scolaire-etudes', 4, 'mortier', 'scolaire', 'Manuels, annales, prépas'),
('Savoirs & Pro', 'savoirs-pro', 5, 'lunettes', 'savoirs', 'Droit, éco, informatique, langues'),
('Vie pratique', 'vie-pratique', 6, 'tasse', 'pratique', 'Cuisine, sport, maison');

UPDATE categorie SET nom = 'BD', ordre = 1 WHERE slug = 'bd-mangas';
UPDATE categorie SET nom = 'Romans jeunesse (7-12 ans)', ordre = 5 WHERE slug = 'jeunesse';
UPDATE categorie SET nom = 'Romans', ordre = 1 WHERE slug = 'roman';
UPDATE categorie SET nom = 'SF & fantasy', ordre = 3 WHERE slug = 'science-fiction';
UPDATE categorie SET nom = 'Essais & société', ordre = 6 WHERE slug = 'essai';
UPDATE categorie SET nom = 'Lycée (manuels & annales)', ordre = 3 WHERE slug = 'manuel-scolaire';

UPDATE categorie c JOIN categorie u ON u.slug = 'bd-manga-jeunesse' SET c.id_parent = u.id_categorie WHERE c.slug IN ('bd-mangas', 'jeunesse');
UPDATE categorie c JOIN categorie u ON u.slug = 'litterature' SET c.id_parent = u.id_categorie WHERE c.slug IN ('roman', 'science-fiction');
UPDATE categorie c JOIN categorie u ON u.slug = 'art-culture' SET c.id_parent = u.id_categorie WHERE c.slug = 'essai';
UPDATE categorie c JOIN categorie u ON u.slug = 'scolaire-etudes' SET c.id_parent = u.id_categorie WHERE c.slug = 'manuel-scolaire';

INSERT IGNORE INTO categorie (nom, slug, ordre, id_parent)
SELECT e.nom, e.slug, e.ordre, u.id_categorie
FROM (
    SELECT 'Comics' AS nom, 'comics' AS slug, 2 AS ordre, 'bd-manga-jeunesse' AS univers
    UNION ALL SELECT 'Mangas', 'mangas', 3, 'bd-manga-jeunesse'
    UNION ALL SELECT 'Albums (0-6 ans)', 'albums', 4, 'bd-manga-jeunesse'
    UNION ALL SELECT 'Ados', 'ados', 6, 'bd-manga-jeunesse'
    UNION ALL SELECT 'Policier & thriller', 'policier-thriller', 2, 'litterature'
    UNION ALL SELECT 'Romance', 'romance', 4, 'litterature'
    UNION ALL SELECT 'Poésie & théâtre', 'poesie-theatre', 5, 'litterature'
    UNION ALL SELECT 'Classiques', 'classiques', 6, 'litterature'
    UNION ALL SELECT 'Histoire', 'histoire', 1, 'art-culture'
    UNION ALL SELECT 'Arts & photographie', 'arts-photographie', 2, 'art-culture'
    UNION ALL SELECT 'Musique', 'musique', 3, 'art-culture'
    UNION ALL SELECT 'Voyages', 'voyages', 5, 'art-culture'
    UNION ALL SELECT 'Primaire', 'primaire', 1, 'scolaire-etudes'
    UNION ALL SELECT 'Collège', 'college', 2, 'scolaire-etudes'
    UNION ALL SELECT 'Soutien scolaire', 'soutien-scolaire', 4, 'scolaire-etudes'
    UNION ALL SELECT 'Prépas & concours', 'prepas-concours', 5, 'scolaire-etudes'
    UNION ALL SELECT 'Université', 'universite', 6, 'scolaire-etudes'
    UNION ALL SELECT 'Droit', 'droit', 1, 'savoirs-pro'
    UNION ALL SELECT 'Économie & entreprise', 'economie-entreprise', 2, 'savoirs-pro'
    UNION ALL SELECT 'Informatique', 'informatique', 3, 'savoirs-pro'
    UNION ALL SELECT 'Sciences & techniques', 'sciences-techniques', 4, 'savoirs-pro'
    UNION ALL SELECT 'Santé', 'sante', 5, 'savoirs-pro'
    UNION ALL SELECT 'Langues & dictionnaires', 'langues-dictionnaires', 6, 'savoirs-pro'
    UNION ALL SELECT 'Cuisine', 'cuisine', 1, 'vie-pratique'
    UNION ALL SELECT 'Développement personnel', 'developpement-personnel', 2, 'vie-pratique'
    UNION ALL SELECT 'Sport', 'sport', 3, 'vie-pratique'
    UNION ALL SELECT 'Maison & jardin', 'maison-jardin', 4, 'vie-pratique'
) e
JOIN categorie u ON u.slug = e.univers;

UPDATE livre l JOIN categorie c ON c.slug = 'mangas' SET l.id_categorie = c.id_categorie
  WHERE l.titre LIKE 'Jujutsu Kaisen%' OR l.titre LIKE 'Bleach%';
UPDATE livre l JOIN categorie c ON c.slug = 'comics' SET l.id_categorie = c.id_categorie
  WHERE l.titre LIKE 'Absolute Batman%';
UPDATE livre l JOIN categorie c ON c.slug = 'romance' SET l.id_categorie = c.id_categorie
  WHERE l.titre LIKE '%Bridgerton%';
UPDATE livre l JOIN categorie c ON c.slug = 'classiques' SET l.id_categorie = c.id_categorie
  WHERE l.titre LIKE 'L%tranger%' OR l.titre LIKE 'Th%r%se Raquin%';
