SET NAMES utf8mb4;

INSERT INTO `user` (pseudo, email, mot_de_passe, nom, prenom, role, statut, email_verifie, est_verifie)
SELECT 'lecteur.apercu', 'lecteur.apercu@biblioccaz.invalid', '!', 'Aperçu', 'Lecteur', 'client', 'actif', 1, 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `user` WHERE email = 'lecteur.apercu@biblioccaz.invalid' OR pseudo = 'lecteur.apercu');

UPDATE `user` SET nom = 'Aperçu', prenom = 'Lecteur' WHERE email = 'lecteur.apercu@biblioccaz.invalid';
