SET NAMES utf8mb4;

START TRANSACTION;

UPDATE evenement SET description = REPLACE(description, 'Participation de 5 € réglée sur place.', 'Participation gratuite.') WHERE description LIKE '%Participation de 5 € réglée sur place.%';
UPDATE evenement SET description = REPLACE(description, 'Participation de 3 € par personne.', 'Participation gratuite pour tous.') WHERE description LIKE '%Participation de 3 € par personne.%';
UPDATE evenement SET description = REPLACE(description, 'Entrée 4 €, gratuite pour les étudiants.', 'Entrée gratuite pour tous.') WHERE description LIKE '%Entrée 4 €, gratuite pour les étudiants.%';
UPDATE evenement SET description = REPLACE(description, 'Participation de 8 €.', 'Participation gratuite.') WHERE description LIKE '%Participation de 8 €.%';
UPDATE evenement SET description = REPLACE(description, 'Participation de 6 €.', 'Participation gratuite.') WHERE description LIKE '%Participation de 6 €.%';
UPDATE evenement SET description = REPLACE(description, 'Entrée 10 €, tarif réduit 6 €.', 'Entrée gratuite pour tous.') WHERE description LIKE '%Entrée 10 €, tarif réduit 6 €.%';
UPDATE evenement SET description = REPLACE(description, 'Participation de 5 €, carnet fourni.', 'Gratuit, carnet fourni.') WHERE description LIKE '%Participation de 5 €, carnet fourni.%';
UPDATE evenement SET description = REPLACE(description, 'Entrée 5 €.', 'Entrée gratuite.') WHERE description LIKE '%Entrée 5 €.%';
UPDATE evenement SET description = REPLACE(description, 'Participation de 3 €.', 'Participation gratuite.') WHERE description LIKE '%Participation de 3 €.%';

COMMIT;
