SET NAMES utf8mb4;

START TRANSACTION;

UPDATE serie s
SET s.auteur = 'Ichigo Takano'
WHERE s.nom = 'Orange' AND s.auteur = 'Yui Tokiumi'
  AND NOT EXISTS (SELECT 1 FROM (SELECT id_serie FROM serie WHERE nom = 'Orange' AND auteur = 'Ichigo Takano') autre);

INSERT IGNORE INTO serie (nom, auteur, nature)
SELECT 'Orange — Romans', 'Yui Tokiumi', 'cycle'
WHERE EXISTS (SELECT 1 FROM livre WHERE isbn IN ('9782369742272', '9782369742470', '9782369742708'));

UPDATE livre_serie ls
JOIN livre l ON l.id_livre = ls.id_livre
JOIN serie ancienne ON ancienne.id_serie = ls.id_serie AND ancienne.nom IN ('Orange', 'Orange — Romans')
JOIN serie romans ON romans.nom = 'Orange — Romans' AND romans.auteur = 'Yui Tokiumi'
SET ls.id_serie = romans.id_serie,
    ls.numero_tome = CASE l.isbn WHEN '9782369742272' THEN 1 WHEN '9782369742470' THEN 2 WHEN '9782369742708' THEN 3 END,
    ls.numero_tome_fin = NULL,
    ls.cle_oeuvre = CONCAT('orange-roman-', CASE l.isbn WHEN '9782369742272' THEN 1 WHEN '9782369742470' THEN 2 WHEN '9782369742708' THEN 3 END)
WHERE l.isbn IN ('9782369742272', '9782369742470', '9782369742708');

UPDATE livre
SET titre = CONCAT('Orange (roman) - Tome ', CASE isbn WHEN '9782369742272' THEN 1 WHEN '9782369742470' THEN 2 WHEN '9782369742708' THEN 3 END)
WHERE isbn IN ('9782369742272', '9782369742470', '9782369742708');

COMMIT;
