SET NAMES utf8mb4;
USE biblioccaz;

ALTER TABLE tag ADD COLUMN IF NOT EXISTS famille VARCHAR(20) NOT NULL DEFAULT 'theme';
ALTER TABLE tag ADD COLUMN IF NOT EXISTS ordre   INT         NOT NULL DEFAULT 0;
ALTER TABLE tag ADD INDEX IF NOT EXISTS idx_tag_famille (famille, ordre);

UPDATE livre l
  JOIN categorie ancienne ON ancienne.id_categorie = l.id_categorie AND ancienne.slug = 'young-adult'
  JOIN categorie nouvelle ON nouvelle.slug = 'ados'
  SET l.id_categorie = nouvelle.id_categorie;
UPDATE don d
  JOIN categorie ancienne ON ancienne.id_categorie = d.id_categorie AND ancienne.slug = 'young-adult'
  JOIN categorie nouvelle ON nouvelle.slug = 'ados'
  SET d.id_categorie = nouvelle.id_categorie;
UPDATE livre l
  JOIN categorie ancienne ON ancienne.id_categorie = l.id_categorie AND ancienne.slug = 'cinema'
  JOIN categorie nouvelle ON nouvelle.slug = 'musique'
  SET l.id_categorie = nouvelle.id_categorie;
UPDATE don d
  JOIN categorie ancienne ON ancienne.id_categorie = d.id_categorie AND ancienne.slug = 'cinema'
  JOIN categorie nouvelle ON nouvelle.slug = 'musique'
  SET d.id_categorie = nouvelle.id_categorie;
DELETE FROM categorie WHERE slug IN ('young-adult', 'cinema');

UPDATE categorie c
JOIN (
    SELECT 'bd-mangas' AS ancien, 'bandes-dessinees' AS nouveau, 'Bandes dessinées' AS nom, 1 AS ordre
    UNION ALL SELECT 'albums', 'albums', 'Albums & premières lectures', 4
    UNION ALL SELECT 'jeunesse', 'jeunesse', 'Romans jeunesse', 5
    UNION ALL SELECT 'ados', 'ados', 'Romans ados & young adult', 6
    UNION ALL SELECT 'roman', 'roman', 'Romans contemporains', 1
    UNION ALL SELECT 'policier-thriller', 'policier-thriller', 'Polars & thrillers', 2
    UNION ALL SELECT 'science-fiction', 'science-fiction', 'Science-fiction & fantasy', 3
    UNION ALL SELECT 'arts-photographie', 'arts-photographie', 'Beaux-arts & photo', 2
    UNION ALL SELECT 'musique', 'musique', 'Musique & cinéma', 3
    UNION ALL SELECT 'voyages', 'voyages', 'Récits de voyage & guides', 4
    UNION ALL SELECT 'essai', 'essai', 'Essais & société', 5
    UNION ALL SELECT 'manuel-scolaire', 'lycee', 'Lycée', 3
    UNION ALL SELECT 'soutien-scolaire', 'soutien-scolaire', 'Cahiers de vacances & soutien', 4
    UNION ALL SELECT 'sante', 'sante', 'Santé & médecine', 5
    UNION ALL SELECT 'sport', 'sport', 'Sport & loisirs', 3
) renommage ON c.slug IN (renommage.ancien, renommage.nouveau)
SET c.slug = renommage.nouveau, c.nom = renommage.nom, c.ordre = renommage.ordre;

UPDATE don d JOIN categorie c ON c.slug = 'comics'
  SET d.id_categorie = c.id_categorie
  WHERE d.titre LIKE 'Absolute Batman%';

DELETE FROM tag WHERE slug IN ('classique', 'romance', 'histoire', 'fantastique', 'action', 'societe');

INSERT INTO tag (nom, slug, famille, ordre) VALUES
('Aventure', 'aventure', 'theme', 1),
('Action & combats', 'action-combats', 'theme', 2),
('Humour', 'humour', 'theme', 3),
('Suspense', 'suspense', 'theme', 4),
('Magie & surnaturel', 'magie-surnaturel', 'theme', 5),
('Amitié', 'amitie', 'theme', 6),
('Famille', 'famille', 'theme', 7),
('Passage à l''âge adulte', 'passage-age-adulte', 'theme', 8),
('Nature & écologie', 'nature-ecologie', 'theme', 9),
('Engagement', 'engagement', 'theme', 10),
('Super-héros', 'super-heros', 'theme', 11),
('Feel-good', 'feel-good', 'theme', 12),
('Dès 3 ans', 'des-3-ans', 'public', 1),
('Dès 6 ans', 'des-6-ans', 'public', 2),
('Dès 9 ans', 'des-9-ans', 'public', 3),
('Dès 12 ans', 'des-12-ans', 'public', 4),
('Dès 15 ans', 'des-15-ans', 'public', 5),
('Adultes', 'adultes', 'public', 6),
('Adapté en film ou série', 'adapte-film-serie', 'particularite', 1),
('Prix littéraire', 'prix-litteraire', 'particularite', 2),
('Série en plusieurs tomes', 'serie-plusieurs-tomes', 'particularite', 3),
('Premier tome', 'premier-tome', 'particularite', 4),
('Édition illustrée', 'edition-illustree', 'particularite', 5),
('Facile à lire', 'facile-a-lire', 'particularite', 6),
('Histoire vraie', 'histoire-vraie', 'particularite', 7),
('Format poche', 'format-poche', 'particularite', 8)
ON DUPLICATE KEY UPDATE nom = VALUES(nom), famille = VALUES(famille), ordre = VALUES(ordre);

INSERT IGNORE INTO livre_tag (id_livre, id_tag)
SELECT l.id_livre, t.id_tag
FROM (
    SELECT 'Harry Potter%' AS motif, 'aventure' AS tag
    UNION ALL SELECT 'Harry Potter%', 'magie-surnaturel'
    UNION ALL SELECT 'Harry Potter%', 'amitie'
    UNION ALL SELECT 'Harry Potter%', 'des-9-ans'
    UNION ALL SELECT 'Harry Potter%', 'serie-plusieurs-tomes'
    UNION ALL SELECT 'Harry Potter%', 'adapte-film-serie'
    UNION ALL SELECT 'Jujutsu Kaisen%', 'action-combats'
    UNION ALL SELECT 'Jujutsu Kaisen%', 'magie-surnaturel'
    UNION ALL SELECT 'Jujutsu Kaisen%', 'des-12-ans'
    UNION ALL SELECT 'Jujutsu Kaisen%', 'serie-plusieurs-tomes'
    UNION ALL SELECT 'Jujutsu Kaisen%', 'adapte-film-serie'
    UNION ALL SELECT 'Bleach%', 'action-combats'
    UNION ALL SELECT 'Bleach%', 'magie-surnaturel'
    UNION ALL SELECT 'Bleach%', 'des-12-ans'
    UNION ALL SELECT 'Bleach%', 'serie-plusieurs-tomes'
    UNION ALL SELECT 'Bleach%', 'adapte-film-serie'
    UNION ALL SELECT 'Bleach%', 'premier-tome'
    UNION ALL SELECT 'L%tranger', 'adultes'
    UNION ALL SELECT 'L%tranger', 'adapte-film-serie'
    UNION ALL SELECT 'L%tranger', 'format-poche'
    UNION ALL SELECT 'Th%r%se Raquin%', 'suspense'
    UNION ALL SELECT 'Th%r%se Raquin%', 'adultes'
    UNION ALL SELECT 'Th%r%se Raquin%', 'adapte-film-serie'
    UNION ALL SELECT 'Th%r%se Raquin%', 'format-poche'
    UNION ALL SELECT 'Le Probl%me %trois corps%', 'suspense'
    UNION ALL SELECT 'Le Probl%me %trois corps%', 'adultes'
    UNION ALL SELECT 'Le Probl%me %trois corps%', 'serie-plusieurs-tomes'
    UNION ALL SELECT 'Le Probl%me %trois corps%', 'prix-litteraire'
    UNION ALL SELECT 'Le Probl%me %trois corps%', 'adapte-film-serie'
    UNION ALL SELECT 'L%Arabe du futur%', 'famille'
    UNION ALL SELECT 'L%Arabe du futur%', 'humour'
    UNION ALL SELECT 'L%Arabe du futur%', 'adultes'
    UNION ALL SELECT 'L%Arabe du futur%', 'histoire-vraie'
    UNION ALL SELECT 'L%Arabe du futur%', 'serie-plusieurs-tomes'
    UNION ALL SELECT '%Bridgerton%', 'famille'
    UNION ALL SELECT '%Bridgerton%', 'adultes'
    UNION ALL SELECT '%Bridgerton%', 'serie-plusieurs-tomes'
    UNION ALL SELECT '%Bridgerton%', 'adapte-film-serie'
    UNION ALL SELECT 'I Am Not Your Negro%', 'engagement'
    UNION ALL SELECT 'I Am Not Your Negro%', 'adultes'
    UNION ALL SELECT 'I Am Not Your Negro%', 'histoire-vraie'
    UNION ALL SELECT 'I Am Not Your Negro%', 'adapte-film-serie'
    UNION ALL SELECT 'Absolute Batman%', 'action-combats'
    UNION ALL SELECT 'Absolute Batman%', 'super-heros'
    UNION ALL SELECT 'Absolute Batman%', 'des-15-ans'
    UNION ALL SELECT 'Absolute Batman%', 'serie-plusieurs-tomes'
) attribution
JOIN livre l ON l.titre LIKE attribution.motif
JOIN tag t ON t.slug = attribution.tag;
