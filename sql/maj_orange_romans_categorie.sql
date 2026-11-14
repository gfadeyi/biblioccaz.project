SET NAMES utf8mb4;

START TRANSACTION;

UPDATE livre l
JOIN categorie c ON c.slug = 'ados'
SET l.id_categorie = c.id_categorie,
    l.description = CONCAT(
        'Roman de Yui Tokiumi adapté du manga d''Ichigo Takano. Naho reçoit une lettre envoyée par elle-même depuis le futur. Les conseils qu''elle contient la conduisent à regarder autrement ses amis et les choix qui pourraient changer leur vie.',
        '\n\nTome ', CASE l.isbn WHEN '9782369742272' THEN 1 WHEN '9782369742470' THEN 2 WHEN '9782369742708' THEN 3 END,
        ' de la version roman d''Orange (texte, et non bande dessinée). Édition française publiée par Akata.')
WHERE l.isbn IN ('9782369742272', '9782369742470', '9782369742708');

UPDATE livre
SET auteur = 'Tatsuya Endō'
WHERE isbn = '9791042021030' AND auteur = 'Tatsuya Endo / Satoko Fujimoto / Nathalie Bougon-Bastide';

COMMIT;
