USE biblioccaz;

CREATE TABLE IF NOT EXISTS categorie (
  id_categorie  INT AUTO_INCREMENT PRIMARY KEY,
  nom           VARCHAR(100) NOT NULL,
  slug          VARCHAR(100) NOT NULL UNIQUE,
  ordre         INT          NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tag (
  id_tag        INT AUTO_INCREMENT PRIMARY KEY,
  nom           VARCHAR(100) NOT NULL,
  slug          VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS livre_tag (
  id_livre      INT NOT NULL,
  id_tag        INT NOT NULL,
  PRIMARY KEY (id_livre, id_tag),
  CONSTRAINT fk_livretag_livre FOREIGN KEY (id_livre) REFERENCES livre(id_livre) ON DELETE CASCADE,
  CONSTRAINT fk_livretag_tag   FOREIGN KEY (id_tag)   REFERENCES tag(id_tag)     ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE livre ADD COLUMN IF NOT EXISTS id_categorie INT NULL;

SET @fk := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'livre' AND CONSTRAINT_NAME = 'fk_livre_categorie');
SET @sql := IF(@fk = 0,
  'ALTER TABLE livre ADD CONSTRAINT fk_livre_categorie FOREIGN KEY (id_categorie) REFERENCES categorie(id_categorie) ON DELETE SET NULL',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

INSERT IGNORE INTO categorie (nom, slug, ordre) VALUES
('Roman', 'roman', 1),
('Jeunesse', 'jeunesse', 2),
('BD & Mangas', 'bd-mangas', 3),
('Science-fiction & Fantasy', 'science-fiction', 4),
('Essai', 'essai', 5),
('Manuel scolaire', 'manuel-scolaire', 6);

INSERT IGNORE INTO tag (nom, slug) VALUES
('Classique', 'classique'),
('Aventure', 'aventure'),
('Romance', 'romance'),
('Fantastique', 'fantastique'),
('Histoire', 'histoire'),
('Action', 'action'),
('Société', 'societe');

UPDATE livre SET id_categorie = (SELECT id_categorie FROM categorie WHERE slug = 'roman')
  WHERE id_categorie IS NULL AND (titre LIKE 'L%tranger%' OR titre LIKE 'Th%r%se Raquin%' OR titre LIKE '%Bridgerton%');
UPDATE livre SET id_categorie = (SELECT id_categorie FROM categorie WHERE slug = 'jeunesse')
  WHERE id_categorie IS NULL AND titre LIKE 'Harry Potter%';
UPDATE livre SET id_categorie = (SELECT id_categorie FROM categorie WHERE slug = 'bd-mangas')
  WHERE id_categorie IS NULL AND (titre LIKE 'Jujutsu Kaisen%' OR titre LIKE 'Bleach%' OR titre LIKE 'Absolute Batman%' OR titre LIKE 'L%Arabe du futur%');
UPDATE livre SET id_categorie = (SELECT id_categorie FROM categorie WHERE slug = 'science-fiction')
  WHERE id_categorie IS NULL AND titre LIKE 'Le Probl%me %trois corps%';
UPDATE livre SET id_categorie = (SELECT id_categorie FROM categorie WHERE slug = 'essai')
  WHERE id_categorie IS NULL AND titre LIKE 'I Am Not Your Negro%';

INSERT IGNORE INTO livre_tag (id_livre, id_tag)
  SELECT l.id_livre, t.id_tag FROM livre l JOIN tag t ON t.slug = 'classique'
  WHERE l.titre LIKE 'L%tranger%' OR l.titre LIKE 'Th%r%se Raquin%';
INSERT IGNORE INTO livre_tag (id_livre, id_tag)
  SELECT l.id_livre, t.id_tag FROM livre l JOIN tag t ON t.slug = 'romance'
  WHERE l.titre LIKE '%Bridgerton%';
INSERT IGNORE INTO livre_tag (id_livre, id_tag)
  SELECT l.id_livre, t.id_tag FROM livre l JOIN tag t ON t.slug = 'fantastique'
  WHERE l.titre LIKE 'Harry Potter%' OR l.titre LIKE 'Jujutsu Kaisen%' OR l.titre LIKE 'Bleach%';
INSERT IGNORE INTO livre_tag (id_livre, id_tag)
  SELECT l.id_livre, t.id_tag FROM livre l JOIN tag t ON t.slug = 'action'
  WHERE l.titre LIKE 'Jujutsu Kaisen%' OR l.titre LIKE 'Bleach%' OR l.titre LIKE 'Absolute Batman%';
INSERT IGNORE INTO livre_tag (id_livre, id_tag)
  SELECT l.id_livre, t.id_tag FROM livre l JOIN tag t ON t.slug = 'aventure'
  WHERE l.titre LIKE 'Harry Potter%';
INSERT IGNORE INTO livre_tag (id_livre, id_tag)
  SELECT l.id_livre, t.id_tag FROM livre l JOIN tag t ON t.slug = 'societe'
  WHERE l.titre LIKE 'I Am Not Your Negro%' OR l.titre LIKE 'L%Arabe du futur%';
INSERT IGNORE INTO livre_tag (id_livre, id_tag)
  SELECT l.id_livre, t.id_tag FROM livre l JOIN tag t ON t.slug = 'histoire'
  WHERE l.titre LIKE 'L%Arabe du futur%';
