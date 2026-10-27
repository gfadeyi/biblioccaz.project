SET NAMES utf8mb4;

SET @decalage = DATEDIFF(CURDATE(), '2026-10-26');

START TRANSACTION;

DELETE FROM newsletter_abonne WHERE email LIKE '%@example.com';

DELETE FROM `user` WHERE id BETWEEN 1001 AND 1099;

DELETE FROM commande WHERE id_commande BETWEEN 1001 AND 9999;

DELETE FROM don WHERE id_don BETWEEN 1001 AND 1099;

DELETE FROM evenement WHERE id_evenement BETWEEN 1001 AND 1099;

DELETE FROM livre WHERE id_livre BETWEEN 1001 AND 1099;

UPDATE livre SET titre = 'Harry Potter et la Coupe de Feu', auteur = 'J. K. Rowling', isbn = '9782070643059', editeur = 'Gallimard Jeunesse', collection = 'Folio junior n° 1173', annee_parution = 2011, nb_pages = 784, reliure = 'Poche', dimensions = '18 x 13 cm', langue = 'Français', prix = 11.50, description = IF(CHAR_LENGTH(COALESCE(description, '')) < 80, 'Quatrième année à Poudlard : le Tournoi des Trois Sorciers réunit trois écoles de magie, et Harry se retrouve inscrit malgré lui à des épreuves bien trop dangereuses pour son âge. Pendant qu''il affronte dragons et labyrinthes, des signes de plus en plus inquiétants annoncent le retour de Voldemort. Le tome où la saga bascule vers l''âge adulte.', description) WHERE id_livre = 1;

UPDATE exemplaire SET prix = 11.50, quantite = 10, is_disponible = 1 WHERE id_livre = 1 AND etat = 'neuf';

INSERT INTO exemplaire (id_livre, etat, prix, type_offre, is_disponible, quantite) SELECT 1, 'neuf', 11.50, 'vente', 1, 10 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 1) AND NOT EXISTS (SELECT 1 FROM exemplaire WHERE id_livre = 1 AND etat = 'neuf');

UPDATE exemplaire SET prix = 6.90, quantite = 2, is_disponible = 1 WHERE id_livre = 1 AND etat = 'tres bon';

INSERT INTO exemplaire (id_livre, etat, prix, type_offre, is_disponible, quantite) SELECT 1, 'tres bon', 6.90, 'vente', 1, 2 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 1) AND NOT EXISTS (SELECT 1 FROM exemplaire WHERE id_livre = 1 AND etat = 'tres bon');

UPDATE exemplaire SET prix = 5.20, quantite = 2, is_disponible = 1 WHERE id_livre = 1 AND etat = 'bon';

INSERT INTO exemplaire (id_livre, etat, prix, type_offre, is_disponible, quantite) SELECT 1, 'bon', 5.20, 'vente', 1, 2 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 1) AND NOT EXISTS (SELECT 1 FROM exemplaire WHERE id_livre = 1 AND etat = 'bon');

UPDATE exemplaire SET prix = 3.50, quantite = 1, is_disponible = 1 WHERE id_livre = 1 AND etat = 'use';

INSERT INTO exemplaire (id_livre, etat, prix, type_offre, is_disponible, quantite) SELECT 1, 'use', 3.50, 'vente', 1, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 1) AND NOT EXISTS (SELECT 1 FROM exemplaire WHERE id_livre = 1 AND etat = 'use');

UPDATE livre SET titre = 'Jujutsu Kaisen - Tome 22', auteur = 'Gege Akutami', isbn = '9791032716205', editeur = 'Ki-oon', collection = 'Jujutsu Kaisen, vol. 22 « Sakurajima reincarnation »', annee_parution = 2024, nb_pages = 176, reliure = 'Broché', dimensions = '18 cm', langue = 'Français', prix = 7.10, description = IF(CHAR_LENGTH(COALESCE(description, '')) < 80, 'Le jeu mortel du Culling Game se poursuit et les exorcistes se dispersent sur plusieurs terrains d''affrontement. Ce volume emmène les personnages vers la colonie de Sakurajima, pour des combats d''une rare intensité où chaque victoire se paie cher. Un tome nerveux pour les lecteurs déjà plongés dans la série.', description) WHERE id_livre = 2;

UPDATE exemplaire SET prix = 7.10, quantite = 11, is_disponible = 1 WHERE id_livre = 2 AND etat = 'neuf';

INSERT INTO exemplaire (id_livre, etat, prix, type_offre, is_disponible, quantite) SELECT 2, 'neuf', 7.10, 'vente', 1, 11 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 2) AND NOT EXISTS (SELECT 1 FROM exemplaire WHERE id_livre = 2 AND etat = 'neuf');

UPDATE exemplaire SET prix = 4.30, quantite = 2, is_disponible = 1 WHERE id_livre = 2 AND etat = 'tres bon';

INSERT INTO exemplaire (id_livre, etat, prix, type_offre, is_disponible, quantite) SELECT 2, 'tres bon', 4.30, 'vente', 1, 2 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 2) AND NOT EXISTS (SELECT 1 FROM exemplaire WHERE id_livre = 2 AND etat = 'tres bon');

UPDATE exemplaire SET prix = 3.20, quantite = 5, is_disponible = 1 WHERE id_livre = 2 AND etat = 'bon';

INSERT INTO exemplaire (id_livre, etat, prix, type_offre, is_disponible, quantite) SELECT 2, 'bon', 3.20, 'vente', 1, 5 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 2) AND NOT EXISTS (SELECT 1 FROM exemplaire WHERE id_livre = 2 AND etat = 'bon');

UPDATE exemplaire SET prix = 2.10, quantite = 2, is_disponible = 1 WHERE id_livre = 2 AND etat = 'use';

INSERT INTO exemplaire (id_livre, etat, prix, type_offre, is_disponible, quantite) SELECT 2, 'use', 2.10, 'vente', 1, 2 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 2) AND NOT EXISTS (SELECT 1 FROM exemplaire WHERE id_livre = 2 AND etat = 'use');

UPDATE livre SET titre = 'Bleach - Tome 01', auteur = 'Tite Kubo', isbn = '9782723442275', editeur = 'Glénat', collection = 'Shonen manga', annee_parution = 2003, nb_pages = 183, reliure = 'Broché', dimensions = '18 cm', langue = 'Français', prix = 7.10, description = IF(CHAR_LENGTH(COALESCE(description, '')) < 80, 'Ichigo Kurosaki voit les fantômes depuis toujours, sans jamais avoir rien demandé. Le jour où une shinigami blessée lui transmet ses pouvoirs pour protéger sa famille, il devient malgré lui chasseur d''esprits maléfiques. Le début d''une saga culte du shōnen, entre humour, sabres et monde des morts.', description) WHERE id_livre = 3;

UPDATE exemplaire SET prix = 7.10, quantite = 12, is_disponible = 1 WHERE id_livre = 3 AND etat = 'neuf';

INSERT INTO exemplaire (id_livre, etat, prix, type_offre, is_disponible, quantite) SELECT 3, 'neuf', 7.10, 'vente', 1, 12 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 3) AND NOT EXISTS (SELECT 1 FROM exemplaire WHERE id_livre = 3 AND etat = 'neuf');

UPDATE exemplaire SET prix = 4.30, quantite = 2, is_disponible = 1 WHERE id_livre = 3 AND etat = 'tres bon';

INSERT INTO exemplaire (id_livre, etat, prix, type_offre, is_disponible, quantite) SELECT 3, 'tres bon', 4.30, 'vente', 1, 2 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 3) AND NOT EXISTS (SELECT 1 FROM exemplaire WHERE id_livre = 3 AND etat = 'tres bon');

UPDATE exemplaire SET prix = 3.20, quantite = 3, is_disponible = 1 WHERE id_livre = 3 AND etat = 'bon';

INSERT INTO exemplaire (id_livre, etat, prix, type_offre, is_disponible, quantite) SELECT 3, 'bon', 3.20, 'vente', 1, 3 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 3) AND NOT EXISTS (SELECT 1 FROM exemplaire WHERE id_livre = 3 AND etat = 'bon');

UPDATE exemplaire SET prix = 2.10, quantite = 3, is_disponible = 1 WHERE id_livre = 3 AND etat = 'use';

INSERT INTO exemplaire (id_livre, etat, prix, type_offre, is_disponible, quantite) SELECT 3, 'use', 2.10, 'vente', 1, 3 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 3) AND NOT EXISTS (SELECT 1 FROM exemplaire WHERE id_livre = 3 AND etat = 'use');

UPDATE livre SET titre = 'L''Étranger', auteur = 'Albert Camus', isbn = '9782070360024', editeur = 'Gallimard', collection = 'Folio n° 2', annee_parution = 1971, nb_pages = 191, reliure = 'Poche', dimensions = '18 cm', langue = 'Français', prix = 7.50, description = IF(CHAR_LENGTH(COALESCE(description, '')) < 80, 'Meursault apprend la mort de sa mère et ne pleure pas. Quelques semaines plus tard, sur une plage écrasée de soleil, il tue un homme sans raison apparente. Son procès devient celui de son indifférence. Un roman court et limpide, devenu l''un des textes les plus lus de la littérature française.', description) WHERE id_livre = 4;

UPDATE exemplaire SET prix = 7.50, quantite = 13, is_disponible = 1 WHERE id_livre = 4 AND etat = 'neuf';

INSERT INTO exemplaire (id_livre, etat, prix, type_offre, is_disponible, quantite) SELECT 4, 'neuf', 7.50, 'vente', 1, 13 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 4) AND NOT EXISTS (SELECT 1 FROM exemplaire WHERE id_livre = 4 AND etat = 'neuf');

UPDATE exemplaire SET prix = 4.50, quantite = 2, is_disponible = 1 WHERE id_livre = 4 AND etat = 'tres bon';

INSERT INTO exemplaire (id_livre, etat, prix, type_offre, is_disponible, quantite) SELECT 4, 'tres bon', 4.50, 'vente', 1, 2 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 4) AND NOT EXISTS (SELECT 1 FROM exemplaire WHERE id_livre = 4 AND etat = 'tres bon');

UPDATE exemplaire SET prix = 3.40, quantite = 6, is_disponible = 1 WHERE id_livre = 4 AND etat = 'bon';

INSERT INTO exemplaire (id_livre, etat, prix, type_offre, is_disponible, quantite) SELECT 4, 'bon', 3.40, 'vente', 1, 6 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 4) AND NOT EXISTS (SELECT 1 FROM exemplaire WHERE id_livre = 4 AND etat = 'bon');

UPDATE exemplaire SET prix = 2.30, quantite = 1, is_disponible = 1 WHERE id_livre = 4 AND etat = 'use';

INSERT INTO exemplaire (id_livre, etat, prix, type_offre, is_disponible, quantite) SELECT 4, 'use', 2.30, 'vente', 1, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 4) AND NOT EXISTS (SELECT 1 FROM exemplaire WHERE id_livre = 4 AND etat = 'use');

UPDATE livre SET titre = 'Thérèse Raquin', auteur = 'Émile Zola', isbn = '9782266082587', editeur = 'Pocket', collection = 'Pocket classiques', annee_parution = 1998, nb_pages = 338, reliure = 'Poche', dimensions = '18 cm', langue = 'Français', prix = 3.95, description = IF(CHAR_LENGTH(COALESCE(description, '')) < 80, 'Mariée à un cousin maladif dans une mercerie sombre du passage du Pont-Neuf, Thérèse étouffe. Sa liaison avec Laurent les pousse à un crime qu''ils croient parfait, mais le remords va les ronger jusqu''à la folie. Le premier grand roman de Zola, d''une noirceur saisissante.', description) WHERE id_livre = 5;

UPDATE exemplaire SET prix = 3.95, quantite = 14, is_disponible = 1 WHERE id_livre = 5 AND etat = 'neuf';

INSERT INTO exemplaire (id_livre, etat, prix, type_offre, is_disponible, quantite) SELECT 5, 'neuf', 3.95, 'vente', 1, 14 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 5) AND NOT EXISTS (SELECT 1 FROM exemplaire WHERE id_livre = 5 AND etat = 'neuf');

UPDATE exemplaire SET prix = 2.40, quantite = 2, is_disponible = 1 WHERE id_livre = 5 AND etat = 'tres bon';

INSERT INTO exemplaire (id_livre, etat, prix, type_offre, is_disponible, quantite) SELECT 5, 'tres bon', 2.40, 'vente', 1, 2 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 5) AND NOT EXISTS (SELECT 1 FROM exemplaire WHERE id_livre = 5 AND etat = 'tres bon');

UPDATE exemplaire SET prix = 1.80, quantite = 4, is_disponible = 1 WHERE id_livre = 5 AND etat = 'bon';

INSERT INTO exemplaire (id_livre, etat, prix, type_offre, is_disponible, quantite) SELECT 5, 'bon', 1.80, 'vente', 1, 4 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 5) AND NOT EXISTS (SELECT 1 FROM exemplaire WHERE id_livre = 5 AND etat = 'bon');

UPDATE exemplaire SET prix = 1.20, quantite = 2, is_disponible = 1 WHERE id_livre = 5 AND etat = 'use';

INSERT INTO exemplaire (id_livre, etat, prix, type_offre, is_disponible, quantite) SELECT 5, 'use', 1.20, 'vente', 1, 2 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 5) AND NOT EXISTS (SELECT 1 FROM exemplaire WHERE id_livre = 5 AND etat = 'use');

UPDATE livre SET titre = 'Le Problème à trois corps', auteur = 'Liu Cixin', isbn = '9782330113551', editeur = 'Actes Sud', collection = 'Babel n° 1579', annee_parution = 2018, nb_pages = 495, reliure = 'Poche', dimensions = '18 cm', langue = 'Français', prix = 10.50, description = IF(CHAR_LENGTH(COALESCE(description, '')) < 80, 'Pendant la Révolution culturelle, une jeune astrophysicienne envoie un message vers les étoiles. Des décennies plus tard, des scientifiques se suicident, un mystérieux jeu vidéo fascine les chercheurs et un compte à rebours apparaît sous les yeux d''un nanotechnologue. Une science-fiction vertigineuse, couronnée par le prix Hugo.', description) WHERE id_livre = 6;

UPDATE exemplaire SET prix = 10.50, quantite = 15, is_disponible = 1 WHERE id_livre = 6 AND etat = 'neuf';

INSERT INTO exemplaire (id_livre, etat, prix, type_offre, is_disponible, quantite) SELECT 6, 'neuf', 10.50, 'vente', 1, 15 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 6) AND NOT EXISTS (SELECT 1 FROM exemplaire WHERE id_livre = 6 AND etat = 'neuf');

UPDATE exemplaire SET prix = 6.30, quantite = 2, is_disponible = 1 WHERE id_livre = 6 AND etat = 'tres bon';

INSERT INTO exemplaire (id_livre, etat, prix, type_offre, is_disponible, quantite) SELECT 6, 'tres bon', 6.30, 'vente', 1, 2 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 6) AND NOT EXISTS (SELECT 1 FROM exemplaire WHERE id_livre = 6 AND etat = 'tres bon');

UPDATE exemplaire SET prix = 4.70, quantite = 2, is_disponible = 1 WHERE id_livre = 6 AND etat = 'bon';

INSERT INTO exemplaire (id_livre, etat, prix, type_offre, is_disponible, quantite) SELECT 6, 'bon', 4.70, 'vente', 1, 2 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 6) AND NOT EXISTS (SELECT 1 FROM exemplaire WHERE id_livre = 6 AND etat = 'bon');

UPDATE exemplaire SET prix = 3.20, quantite = 3, is_disponible = 1 WHERE id_livre = 6 AND etat = 'use';

INSERT INTO exemplaire (id_livre, etat, prix, type_offre, is_disponible, quantite) SELECT 6, 'use', 3.20, 'vente', 1, 3 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 6) AND NOT EXISTS (SELECT 1 FROM exemplaire WHERE id_livre = 6 AND etat = 'use');

UPDATE livre SET titre = 'L''Arabe du futur - Volume 6', auteur = 'Riad Sattouf', isbn = '9782370734242', editeur = 'Allary Éditions', collection = 'Une jeunesse au Moyen-Orient (1994-2011)', annee_parution = 2022, nb_pages = 176, reliure = 'Broché', dimensions = '24 cm', langue = 'Français', prix = 23.90, description = IF(CHAR_LENGTH(COALESCE(description, '')) < 80, 'Dernier volume de la série autobiographique : Riad, désormais jeune adulte, quitte la Bretagne pour Paris et se lance dans la bande dessinée, tandis que son père s''enfonce dans ses contradictions. Drôle, cruel et bouleversant, ce tome referme une fresque familiale entre la France et le Moyen-Orient.', description) WHERE id_livre = 7;

UPDATE exemplaire SET prix = 23.90, quantite = 10, is_disponible = 1 WHERE id_livre = 7 AND etat = 'neuf';

INSERT INTO exemplaire (id_livre, etat, prix, type_offre, is_disponible, quantite) SELECT 7, 'neuf', 23.90, 'vente', 1, 10 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 7) AND NOT EXISTS (SELECT 1 FROM exemplaire WHERE id_livre = 7 AND etat = 'neuf');

UPDATE exemplaire SET prix = 14.30, quantite = 2, is_disponible = 1 WHERE id_livre = 7 AND etat = 'tres bon';

INSERT INTO exemplaire (id_livre, etat, prix, type_offre, is_disponible, quantite) SELECT 7, 'tres bon', 14.30, 'vente', 1, 2 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 7) AND NOT EXISTS (SELECT 1 FROM exemplaire WHERE id_livre = 7 AND etat = 'tres bon');

UPDATE exemplaire SET prix = 10.80, quantite = 5, is_disponible = 1 WHERE id_livre = 7 AND etat = 'bon';

INSERT INTO exemplaire (id_livre, etat, prix, type_offre, is_disponible, quantite) SELECT 7, 'bon', 10.80, 'vente', 1, 5 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 7) AND NOT EXISTS (SELECT 1 FROM exemplaire WHERE id_livre = 7 AND etat = 'bon');

UPDATE exemplaire SET prix = 7.20, quantite = 1, is_disponible = 1 WHERE id_livre = 7 AND etat = 'use';

INSERT INTO exemplaire (id_livre, etat, prix, type_offre, is_disponible, quantite) SELECT 7, 'use', 7.20, 'vente', 1, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 7) AND NOT EXISTS (SELECT 1 FROM exemplaire WHERE id_livre = 7 AND etat = 'use');

UPDATE livre SET titre = 'La Chronique des Bridgerton - Tomes 1 & 2', auteur = 'Julia Quinn', isbn = '9782290359426', editeur = 'J''ai lu', collection = NULL, annee_parution = 2021, nb_pages = 731, reliure = 'Broché', dimensions = '20 cm', langue = 'Français', prix = 16.00, description = IF(CHAR_LENGTH(COALESCE(description, '')) < 80, 'Londres, 1813. Daphné Bridgerton et le duc de Hastings concluent un faux arrangement amoureux pour échapper aux mères marieuses… jusqu''à ce que le jeu devienne sérieux. Le second roman suit Anthony, l''aîné de la fratrie, bien décidé à faire un mariage de raison. Les deux premiers tomes de la saga qui a inspiré la série.', description) WHERE id_livre = 8;

UPDATE exemplaire SET prix = 16.00, quantite = 11, is_disponible = 1 WHERE id_livre = 8 AND etat = 'neuf';

INSERT INTO exemplaire (id_livre, etat, prix, type_offre, is_disponible, quantite) SELECT 8, 'neuf', 16.00, 'vente', 1, 11 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 8) AND NOT EXISTS (SELECT 1 FROM exemplaire WHERE id_livre = 8 AND etat = 'neuf');

UPDATE exemplaire SET prix = 9.60, quantite = 2, is_disponible = 1 WHERE id_livre = 8 AND etat = 'tres bon';

INSERT INTO exemplaire (id_livre, etat, prix, type_offre, is_disponible, quantite) SELECT 8, 'tres bon', 9.60, 'vente', 1, 2 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 8) AND NOT EXISTS (SELECT 1 FROM exemplaire WHERE id_livre = 8 AND etat = 'tres bon');

UPDATE exemplaire SET prix = 7.20, quantite = 3, is_disponible = 1 WHERE id_livre = 8 AND etat = 'bon';

INSERT INTO exemplaire (id_livre, etat, prix, type_offre, is_disponible, quantite) SELECT 8, 'bon', 7.20, 'vente', 1, 3 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 8) AND NOT EXISTS (SELECT 1 FROM exemplaire WHERE id_livre = 8 AND etat = 'bon');

UPDATE exemplaire SET prix = 4.80, quantite = 2, is_disponible = 1 WHERE id_livre = 8 AND etat = 'use';

INSERT INTO exemplaire (id_livre, etat, prix, type_offre, is_disponible, quantite) SELECT 8, 'use', 4.80, 'vente', 1, 2 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 8) AND NOT EXISTS (SELECT 1 FROM exemplaire WHERE id_livre = 8 AND etat = 'use');

UPDATE livre SET titre = 'I Am Not Your Negro', auteur = 'James Baldwin', isbn = '9782264073655', editeur = '10/18', collection = '10/18 n° 5362', annee_parution = 2018, nb_pages = 159, reliure = 'Poche', dimensions = '18 cm', langue = 'Français', prix = 7.60, description = IF(CHAR_LENGTH(COALESCE(description, '')) < 80, 'À partir des notes d''un livre que James Baldwin n''a jamais terminé, consacré à Medgar Evers, Malcolm X et Martin Luther King, ce recueil retrace la lutte pour les droits civiques et l''histoire du racisme aux États-Unis. Un texte puissant, accompagné des extraits qui ont nourri le documentaire de Raoul Peck.', description) WHERE id_livre = 9;

UPDATE exemplaire SET prix = 7.60, quantite = 12, is_disponible = 1 WHERE id_livre = 9 AND etat = 'neuf';

INSERT INTO exemplaire (id_livre, etat, prix, type_offre, is_disponible, quantite) SELECT 9, 'neuf', 7.60, 'vente', 1, 12 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 9) AND NOT EXISTS (SELECT 1 FROM exemplaire WHERE id_livre = 9 AND etat = 'neuf');

UPDATE exemplaire SET prix = 4.60, quantite = 2, is_disponible = 1 WHERE id_livre = 9 AND etat = 'tres bon';

INSERT INTO exemplaire (id_livre, etat, prix, type_offre, is_disponible, quantite) SELECT 9, 'tres bon', 4.60, 'vente', 1, 2 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 9) AND NOT EXISTS (SELECT 1 FROM exemplaire WHERE id_livre = 9 AND etat = 'tres bon');

UPDATE exemplaire SET prix = 3.40, quantite = 6, is_disponible = 1 WHERE id_livre = 9 AND etat = 'bon';

INSERT INTO exemplaire (id_livre, etat, prix, type_offre, is_disponible, quantite) SELECT 9, 'bon', 3.40, 'vente', 1, 6 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 9) AND NOT EXISTS (SELECT 1 FROM exemplaire WHERE id_livre = 9 AND etat = 'bon');

UPDATE exemplaire SET prix = 2.30, quantite = 3, is_disponible = 1 WHERE id_livre = 9 AND etat = 'use';

INSERT INTO exemplaire (id_livre, etat, prix, type_offre, is_disponible, quantite) SELECT 9, 'use', 2.30, 'vente', 1, 3 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 9) AND NOT EXISTS (SELECT 1 FROM exemplaire WHERE id_livre = 9 AND etat = 'use');

UPDATE livre SET titre = 'Absolute Batman - Tome 2', auteur = 'Scott Snyder, Nick Dragotta', isbn = '9791026824084', editeur = 'Urban Comics', collection = 'Absolute Batman, tome 2 « Abomination »', annee_parution = NULL, nb_pages = 245, reliure = 'Relié', dimensions = '27 cm', langue = 'Français', prix = 25.00, description = IF(CHAR_LENGTH(COALESCE(description, '')) < 80, 'Dans cette version réinventée du mythe, Bruce Wayne n''a ni fortune ni manoir : c''est un ingénieur issu d''un quartier populaire de Gotham, qui se bat avec ses propres moyens. Ce second tome l''oppose à une menace monstrueuse et pousse encore plus loin la relecture brutale et spectaculaire du Chevalier noir.', description) WHERE id_livre = 10;

UPDATE exemplaire SET prix = 25.00, quantite = 13, is_disponible = 1 WHERE id_livre = 10 AND etat = 'neuf';

INSERT INTO exemplaire (id_livre, etat, prix, type_offre, is_disponible, quantite) SELECT 10, 'neuf', 25.00, 'vente', 1, 13 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 10) AND NOT EXISTS (SELECT 1 FROM exemplaire WHERE id_livre = 10 AND etat = 'neuf');

UPDATE exemplaire SET prix = 15.00, quantite = 2, is_disponible = 1 WHERE id_livre = 10 AND etat = 'tres bon';

INSERT INTO exemplaire (id_livre, etat, prix, type_offre, is_disponible, quantite) SELECT 10, 'tres bon', 15.00, 'vente', 1, 2 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 10) AND NOT EXISTS (SELECT 1 FROM exemplaire WHERE id_livre = 10 AND etat = 'tres bon');

UPDATE exemplaire SET prix = 11.30, quantite = 4, is_disponible = 1 WHERE id_livre = 10 AND etat = 'bon';

INSERT INTO exemplaire (id_livre, etat, prix, type_offre, is_disponible, quantite) SELECT 10, 'bon', 11.30, 'vente', 1, 4 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 10) AND NOT EXISTS (SELECT 1 FROM exemplaire WHERE id_livre = 10 AND etat = 'bon');

UPDATE exemplaire SET prix = 7.50, quantite = 1, is_disponible = 1 WHERE id_livre = 10 AND etat = 'use';

INSERT INTO exemplaire (id_livre, etat, prix, type_offre, is_disponible, quantite) SELECT 10, 'use', 7.50, 'vente', 1, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 10) AND NOT EXISTS (SELECT 1 FROM exemplaire WHERE id_livre = 10 AND etat = 'use');

UPDATE livre SET titre = 'Jujutsu Kaisen - Tome 30', auteur = 'Gege Akutami', isbn = '9791032723777', editeur = 'Ki-oon', collection = 'Jujutsu Kaisen, vol. 30', annee_parution = 2026, nb_pages = NULL, reliure = 'Broché', dimensions = '18 cm', langue = 'Français', prix = 6.95, description = IF(CHAR_LENGTH(COALESCE(description, '')) < 80, 'Le dernier volume de la série : après l''affrontement final, les survivants font le compte de ce qui a été perdu et de ce qui reste à construire. Un épilogue attendu par tous les lecteurs qui ont suivi Yuji Itadori depuis le premier tome.', description) WHERE id_livre = 11;

UPDATE exemplaire SET prix = 6.95, quantite = 14, is_disponible = 1 WHERE id_livre = 11 AND etat = 'neuf';

INSERT INTO exemplaire (id_livre, etat, prix, type_offre, is_disponible, quantite) SELECT 11, 'neuf', 6.95, 'vente', 1, 14 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 11) AND NOT EXISTS (SELECT 1 FROM exemplaire WHERE id_livre = 11 AND etat = 'neuf');

UPDATE exemplaire SET prix = 4.20, quantite = 2, is_disponible = 1 WHERE id_livre = 11 AND etat = 'tres bon';

INSERT INTO exemplaire (id_livre, etat, prix, type_offre, is_disponible, quantite) SELECT 11, 'tres bon', 4.20, 'vente', 1, 2 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 11) AND NOT EXISTS (SELECT 1 FROM exemplaire WHERE id_livre = 11 AND etat = 'tres bon');

UPDATE exemplaire SET prix = 3.10, quantite = 2, is_disponible = 1 WHERE id_livre = 11 AND etat = 'bon';

INSERT INTO exemplaire (id_livre, etat, prix, type_offre, is_disponible, quantite) SELECT 11, 'bon', 3.10, 'vente', 1, 2 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 11) AND NOT EXISTS (SELECT 1 FROM exemplaire WHERE id_livre = 11 AND etat = 'bon');

UPDATE exemplaire SET prix = 2.10, quantite = 2, is_disponible = 1 WHERE id_livre = 11 AND etat = 'use';

INSERT INTO exemplaire (id_livre, etat, prix, type_offre, is_disponible, quantite) SELECT 11, 'use', 2.10, 'vente', 1, 2 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 11) AND NOT EXISTS (SELECT 1 FROM exemplaire WHERE id_livre = 11 AND etat = 'use');

INSERT INTO `user` (id, pseudo, email, mot_de_passe, nom, prenom, role, statut, email_verifie, est_verifie, is_newsletter, date_newsletter, ville, cp, solde_points, date_inscription, niveau_vu) VALUES
(1001, 'elise.marchand', 'elise.marchand@example.com', '$2y$10$rWjqHFaFscZDDJGJ.WnUWOYAtqLP0EKPNFjaaPzUqja3.larz.2K.', 'Marchand', 'Élise', 'auteur', 'actif', 1, 1, 0, NULL, 'Lyon', '69004', 0, '2025-11-14 10:20:00' + INTERVAL @decalage DAY, 0),
(1002, 'bastien.lemoine', 'bastien.lemoine@example.com', '$2y$10$rWjqHFaFscZDDJGJ.WnUWOYAtqLP0EKPNFjaaPzUqja3.larz.2K.', 'Lemoine', 'Bastien', 'auteur', 'actif', 1, 1, 0, NULL, 'Villeurbanne', '69100', 0, '2026-02-03 16:45:00' + INTERVAL @decalage DAY, 0),
(1003, 'lea.martin', 'lea.martin@example.com', '$2y$10$rWjqHFaFscZDDJGJ.WnUWOYAtqLP0EKPNFjaaPzUqja3.larz.2K.', 'Martin', 'Léa', 'client', 'actif', 1, 1, 1, '2025-09-03 10:47:55' + INTERVAL @decalage DAY, 'Lyon', '69003', 15, '2025-09-03 09:47:55' + INTERVAL @decalage DAY, 0),
(1004, 'hugo.bernard', 'hugo.bernard@example.com', '$2y$10$rWjqHFaFscZDDJGJ.WnUWOYAtqLP0EKPNFjaaPzUqja3.larz.2K.', 'Bernard', 'Hugo', 'client', 'actif', 1, 1, 0, NULL, 'Lyon', '69007', 0, '2026-01-15 16:58:12' + INTERVAL @decalage DAY, 0),
(1005, 'chloe.dubois', 'chloe.dubois@example.com', '$2y$10$rWjqHFaFscZDDJGJ.WnUWOYAtqLP0EKPNFjaaPzUqja3.larz.2K.', 'Dubois', 'Chloé', 'client', 'actif', 1, 1, 1, '2025-09-19 14:12:44' + INTERVAL @decalage DAY, 'Villeurbanne', '69100', 0, '2025-09-19 13:12:44' + INTERVAL @decalage DAY, 0),
(1006, 'lucas.thomas', 'lucas.thomas@example.com', '$2y$10$rWjqHFaFscZDDJGJ.WnUWOYAtqLP0EKPNFjaaPzUqja3.larz.2K.', 'Thomas', 'Lucas', 'client', 'actif', 1, 1, 1, '2026-08-30 22:01:31' + INTERVAL @decalage DAY, 'Paris', '75011', 0, '2026-08-30 21:01:31' + INTERVAL @decalage DAY, 0),
(1007, 'manon.robert', 'manon.robert@example.com', '$2y$10$rWjqHFaFscZDDJGJ.WnUWOYAtqLP0EKPNFjaaPzUqja3.larz.2K.', 'Robert', 'Manon', 'client', 'actif', 1, 1, 0, NULL, 'Paris', '75018', 0, '2026-09-22 13:14:13' + INTERVAL @decalage DAY, 0),
(1008, 'nathan.richard', 'nathan.richard@example.com', '$2y$10$rWjqHFaFscZDDJGJ.WnUWOYAtqLP0EKPNFjaaPzUqja3.larz.2K.', 'Richard', 'Nathan', 'client', 'actif', 1, 1, 1, '2026-03-17 13:41:46' + INTERVAL @decalage DAY, 'Lille', '59000', 133, '2026-03-17 12:41:46' + INTERVAL @decalage DAY, 0),
(1009, 'ines.petit', 'ines.petit@example.com', '$2y$10$rWjqHFaFscZDDJGJ.WnUWOYAtqLP0EKPNFjaaPzUqja3.larz.2K.', 'Petit', 'Inès', 'client', 'actif', 1, 1, 1, '2025-09-18 18:36:56' + INTERVAL @decalage DAY, 'Marseille', '13005', 83, '2025-09-18 17:36:56' + INTERVAL @decalage DAY, 0),
(1010, 'theo.durand', 'theo.durand@example.com', '$2y$10$rWjqHFaFscZDDJGJ.WnUWOYAtqLP0EKPNFjaaPzUqja3.larz.2K.', 'Durand', 'Théo', 'client', 'actif', 1, 1, 0, NULL, 'Bordeaux', '33000', 115, '2026-04-17 14:58:03' + INTERVAL @decalage DAY, 0),
(1011, 'camille.leroy', 'camille.leroy@example.com', '$2y$10$rWjqHFaFscZDDJGJ.WnUWOYAtqLP0EKPNFjaaPzUqja3.larz.2K.', 'Leroy', 'Camille', 'client', 'actif', 1, 1, 1, '2026-01-19 16:06:53' + INTERVAL @decalage DAY, 'Nantes', '44000', 134, '2026-01-19 15:06:53' + INTERVAL @decalage DAY, 0),
(1012, 'louis.moreau', 'louis.moreau@example.com', '$2y$10$rWjqHFaFscZDDJGJ.WnUWOYAtqLP0EKPNFjaaPzUqja3.larz.2K.', 'Moreau', 'Louis', 'client', 'actif', 1, 1, 0, NULL, 'Toulouse', '31000', 92, '2025-09-03 08:47:15' + INTERVAL @decalage DAY, 0),
(1013, 'sarah.simon', 'sarah.simon@example.com', '$2y$10$rWjqHFaFscZDDJGJ.WnUWOYAtqLP0EKPNFjaaPzUqja3.larz.2K.', 'Simon', 'Sarah', 'client', 'actif', 1, 1, 0, NULL, 'Grenoble', '38000', 78, '2026-06-16 17:07:29' + INTERVAL @decalage DAY, 0),
(1014, 'jules.laurent', 'jules.laurent@example.com', '$2y$10$rWjqHFaFscZDDJGJ.WnUWOYAtqLP0EKPNFjaaPzUqja3.larz.2K.', 'Laurent', 'Jules', 'client', 'actif', 1, 1, 0, NULL, 'Compiègne', '60200', 89, '2026-07-08 09:57:50' + INTERVAL @decalage DAY, 0),
(1015, 'emma.lefebvre', 'emma.lefebvre@example.com', '$2y$10$rWjqHFaFscZDDJGJ.WnUWOYAtqLP0EKPNFjaaPzUqja3.larz.2K.', 'Lefebvre', 'Emma', 'client', 'actif', 1, 1, 1, '2025-11-29 16:55:16' + INTERVAL @decalage DAY, 'Rennes', '35000', 144, '2025-11-29 15:55:16' + INTERVAL @decalage DAY, 0),
(1016, 'gabriel.michel', 'gabriel.michel@example.com', '$2y$10$rWjqHFaFscZDDJGJ.WnUWOYAtqLP0EKPNFjaaPzUqja3.larz.2K.', 'Michel', 'Gabriel', 'client', 'actif', 1, 1, 1, '2026-09-26 22:29:03' + INTERVAL @decalage DAY, 'Strasbourg', '67000', 71, '2026-09-26 21:29:03' + INTERVAL @decalage DAY, 0),
(1017, 'jade.garcia', 'jade.garcia@example.com', '$2y$10$rWjqHFaFscZDDJGJ.WnUWOYAtqLP0EKPNFjaaPzUqja3.larz.2K.', 'Garcia', 'Jade', 'client', 'actif', 1, 1, 0, NULL, 'Lyon', '69003', 152, '2026-04-25 16:17:13' + INTERVAL @decalage DAY, 1),
(1018, 'raphael.david', 'raphael.david@example.com', '$2y$10$rWjqHFaFscZDDJGJ.WnUWOYAtqLP0EKPNFjaaPzUqja3.larz.2K.', 'David', 'Raphaël', 'client', 'actif', 1, 1, 1, '2026-06-02 21:43:55' + INTERVAL @decalage DAY, 'Lyon', '69007', 134, '2026-06-02 20:43:55' + INTERVAL @decalage DAY, 0),
(1019, 'louise.bertrand', 'louise.bertrand@example.com', '$2y$10$rWjqHFaFscZDDJGJ.WnUWOYAtqLP0EKPNFjaaPzUqja3.larz.2K.', 'Bertrand', 'Louise', 'client', 'actif', 1, 1, 1, '2025-10-12 20:13:58' + INTERVAL @decalage DAY, 'Villeurbanne', '69100', 110, '2025-10-12 19:13:58' + INTERVAL @decalage DAY, 0),
(1020, 'arthur.roux', 'arthur.roux@example.com', '$2y$10$rWjqHFaFscZDDJGJ.WnUWOYAtqLP0EKPNFjaaPzUqja3.larz.2K.', 'Roux', 'Arthur', 'client', 'actif', 1, 1, 0, NULL, 'Paris', '75011', 139, '2026-06-28 12:51:29' + INTERVAL @decalage DAY, 0),
(1021, 'zoe.vincent', 'zoe.vincent@example.com', '$2y$10$rWjqHFaFscZDDJGJ.WnUWOYAtqLP0EKPNFjaaPzUqja3.larz.2K.', 'Vincent', 'Zoé', 'client', 'actif', 1, 1, 0, NULL, 'Paris', '75018', 55, '2026-02-11 08:13:53' + INTERVAL @decalage DAY, 1),
(1022, 'adam.fournier', 'adam.fournier@example.com', '$2y$10$rWjqHFaFscZDDJGJ.WnUWOYAtqLP0EKPNFjaaPzUqja3.larz.2K.', 'Fournier', 'Adam', 'client', 'actif', 1, 1, 0, NULL, 'Lille', '59000', 114, '2025-09-23 08:34:01' + INTERVAL @decalage DAY, 0),
(1023, 'alice.morel', 'alice.morel@example.com', '$2y$10$rWjqHFaFscZDDJGJ.WnUWOYAtqLP0EKPNFjaaPzUqja3.larz.2K.', 'Morel', 'Alice', 'client', 'actif', 1, 1, 1, '2025-09-06 18:09:23' + INTERVAL @decalage DAY, 'Marseille', '13005', 116, '2025-09-06 17:09:23' + INTERVAL @decalage DAY, 0),
(1024, 'noah.girard', 'noah.girard@example.com', '$2y$10$rWjqHFaFscZDDJGJ.WnUWOYAtqLP0EKPNFjaaPzUqja3.larz.2K.', 'Girard', 'Noah', 'client', 'actif', 1, 1, 0, NULL, 'Bordeaux', '33000', 109, '2026-08-14 13:12:42' + INTERVAL @decalage DAY, 0),
(1025, 'lina.andre', 'lina.andre@example.com', '$2y$10$rWjqHFaFscZDDJGJ.WnUWOYAtqLP0EKPNFjaaPzUqja3.larz.2K.', 'André', 'Lina', 'client', 'actif', 1, 1, 0, NULL, 'Nantes', '44000', 244, '2025-08-16 13:36:04' + INTERVAL @decalage DAY, 1),
(1026, 'ethan.mercier', 'ethan.mercier@example.com', '$2y$10$rWjqHFaFscZDDJGJ.WnUWOYAtqLP0EKPNFjaaPzUqja3.larz.2K.', 'Mercier', 'Ethan', 'client', 'actif', 1, 1, 0, NULL, 'Toulouse', '31000', 230, '2025-04-02 14:12:25' + INTERVAL @decalage DAY, 1),
(1027, 'rose.dupont', 'rose.dupont@example.com', '$2y$10$rWjqHFaFscZDDJGJ.WnUWOYAtqLP0EKPNFjaaPzUqja3.larz.2K.', 'Dupont', 'Rose', 'client', 'actif', 1, 1, 0, NULL, 'Grenoble', '38000', 281, '2026-03-03 15:33:12' + INTERVAL @decalage DAY, 1),
(1028, 'paul.lambert', 'paul.lambert@example.com', '$2y$10$rWjqHFaFscZDDJGJ.WnUWOYAtqLP0EKPNFjaaPzUqja3.larz.2K.', 'Lambert', 'Paul', 'client', 'actif', 1, 1, 1, '2026-03-04 14:45:35' + INTERVAL @decalage DAY, 'Compiègne', '60200', 140, '2026-03-04 13:45:35' + INTERVAL @decalage DAY, 1),
(1029, 'anna.bonnet', 'anna.bonnet@example.com', '$2y$10$rWjqHFaFscZDDJGJ.WnUWOYAtqLP0EKPNFjaaPzUqja3.larz.2K.', 'Bonnet', 'Anna', 'client', 'actif', 1, 1, 0, NULL, 'Rennes', '35000', 228, '2025-10-19 18:47:13' + INTERVAL @decalage DAY, 1),
(1030, 'yanis.francois', 'yanis.francois@example.com', '$2y$10$rWjqHFaFscZDDJGJ.WnUWOYAtqLP0EKPNFjaaPzUqja3.larz.2K.', 'François', 'Yanis', 'client', 'actif', 1, 1, 0, NULL, 'Strasbourg', '67000', 199, '2026-05-16 19:26:25' + INTERVAL @decalage DAY, 1),
(1031, 'juliette.martinez', 'juliette.martinez@example.com', '$2y$10$rWjqHFaFscZDDJGJ.WnUWOYAtqLP0EKPNFjaaPzUqja3.larz.2K.', 'Martinez', 'Juliette', 'client', 'actif', 1, 1, 0, NULL, 'Lyon', '69003', 154, '2025-06-06 18:52:29' + INTERVAL @decalage DAY, 1),
(1032, 'sacha.legrand', 'sacha.legrand@example.com', '$2y$10$rWjqHFaFscZDDJGJ.WnUWOYAtqLP0EKPNFjaaPzUqja3.larz.2K.', 'Legrand', 'Sacha', 'client', 'actif', 1, 1, 0, NULL, 'Lyon', '69007', 119, '2026-05-24 15:46:47' + INTERVAL @decalage DAY, 1),
(1033, 'mila.garnier', 'mila.garnier@example.com', '$2y$10$rWjqHFaFscZDDJGJ.WnUWOYAtqLP0EKPNFjaaPzUqja3.larz.2K.', 'Garnier', 'Mila', 'client', 'actif', 1, 1, 1, '2025-09-03 22:32:36' + INTERVAL @decalage DAY, 'Villeurbanne', '69100', 138, '2025-09-03 21:32:36' + INTERVAL @decalage DAY, 2),
(1034, 'tom.faure', 'tom.faure@example.com', '$2y$10$rWjqHFaFscZDDJGJ.WnUWOYAtqLP0EKPNFjaaPzUqja3.larz.2K.', 'Faure', 'Tom', 'client', 'actif', 1, 1, 1, '2026-03-16 09:36:07' + INTERVAL @decalage DAY, 'Paris', '75011', 209, '2026-03-16 08:36:07' + INTERVAL @decalage DAY, 2),
(1035, 'nina.rousseau', 'nina.rousseau@example.com', '$2y$10$rWjqHFaFscZDDJGJ.WnUWOYAtqLP0EKPNFjaaPzUqja3.larz.2K.', 'Rousseau', 'Nina', 'client', 'actif', 1, 1, 0, NULL, 'Paris', '75018', 185, '2026-01-14 13:59:11' + INTERVAL @decalage DAY, 1),
(1036, 'malo.blanc', 'malo.blanc@example.com', '$2y$10$rWjqHFaFscZDDJGJ.WnUWOYAtqLP0EKPNFjaaPzUqja3.larz.2K.', 'Blanc', 'Malo', 'client', 'actif', 1, 1, 1, '2025-06-23 13:48:30' + INTERVAL @decalage DAY, 'Lille', '59000', 57, '2025-06-23 12:48:30' + INTERVAL @decalage DAY, 1),
(1037, 'lou.guerin', 'lou.guerin@example.com', '$2y$10$rWjqHFaFscZDDJGJ.WnUWOYAtqLP0EKPNFjaaPzUqja3.larz.2K.', 'Guerin', 'Lou', 'client', 'actif', 1, 1, 0, NULL, 'Marseille', '13005', 142, '2025-07-04 15:27:12' + INTERVAL @decalage DAY, 1),
(1038, 'mathis.muller', 'mathis.muller@example.com', '$2y$10$rWjqHFaFscZDDJGJ.WnUWOYAtqLP0EKPNFjaaPzUqja3.larz.2K.', 'Muller', 'Mathis', 'client', 'actif', 1, 1, 1, '2025-11-18 14:26:44' + INTERVAL @decalage DAY, 'Bordeaux', '33000', 191, '2025-11-18 13:26:44' + INTERVAL @decalage DAY, 1),
(1039, 'clara.henry', 'clara.henry@example.com', '$2y$10$rWjqHFaFscZDDJGJ.WnUWOYAtqLP0EKPNFjaaPzUqja3.larz.2K.', 'Henry', 'Clara', 'client', 'actif', 1, 1, 0, NULL, 'Nantes', '44000', 63, '2025-08-23 19:48:01' + INTERVAL @decalage DAY, 2),
(1040, 'rayan.roussel', 'rayan.roussel@example.com', '$2y$10$rWjqHFaFscZDDJGJ.WnUWOYAtqLP0EKPNFjaaPzUqja3.larz.2K.', 'Roussel', 'Rayan', 'client', 'actif', 1, 1, 0, NULL, 'Toulouse', '31000', 82, '2025-03-15 21:47:36' + INTERVAL @decalage DAY, 2),
(1041, 'eva.nicolas', 'eva.nicolas@example.com', '$2y$10$rWjqHFaFscZDDJGJ.WnUWOYAtqLP0EKPNFjaaPzUqja3.larz.2K.', 'Nicolas', 'Eva', 'client', 'actif', 1, 1, 1, '2025-07-31 17:39:38' + INTERVAL @decalage DAY, 'Grenoble', '38000', 298, '2025-07-31 16:39:38' + INTERVAL @decalage DAY, 2),
(1042, 'victor.perrin', 'victor.perrin@example.com', '$2y$10$rWjqHFaFscZDDJGJ.WnUWOYAtqLP0EKPNFjaaPzUqja3.larz.2K.', 'Perrin', 'Victor', 'client', 'actif', 1, 1, 0, NULL, 'Compiègne', '60200', 200, '2025-09-06 20:40:55' + INTERVAL @decalage DAY, 2),
(1043, 'margaux.morin', 'margaux.morin@example.com', '$2y$10$rWjqHFaFscZDDJGJ.WnUWOYAtqLP0EKPNFjaaPzUqja3.larz.2K.', 'Morin', 'Margaux', 'client', 'actif', 1, 1, 1, '2025-01-12 22:31:47' + INTERVAL @decalage DAY, 'Rennes', '35000', 106, '2025-01-12 21:31:47' + INTERVAL @decalage DAY, 2),
(1044, 'samuel.mathieu', 'samuel.mathieu@example.com', '$2y$10$rWjqHFaFscZDDJGJ.WnUWOYAtqLP0EKPNFjaaPzUqja3.larz.2K.', 'Mathieu', 'Samuel', 'client', 'actif', 1, 1, 1, '2025-03-06 09:45:02' + INTERVAL @decalage DAY, 'Strasbourg', '67000', 188, '2025-03-06 08:45:02' + INTERVAL @decalage DAY, 2),
(1045, 'elsa.clement', 'elsa.clement@example.com', '$2y$10$rWjqHFaFscZDDJGJ.WnUWOYAtqLP0EKPNFjaaPzUqja3.larz.2K.', 'Clement', 'Elsa', 'client', 'actif', 1, 1, 0, NULL, 'Lyon', '69003', 235, '2025-06-18 11:11:56' + INTERVAL @decalage DAY, 2),
(1046, 'axel.gauthier', 'axel.gauthier@example.com', '$2y$10$rWjqHFaFscZDDJGJ.WnUWOYAtqLP0EKPNFjaaPzUqja3.larz.2K.', 'Gauthier', 'Axel', 'client', 'actif', 1, 1, 0, NULL, 'Lyon', '69007', 317, '2025-01-21 13:00:14' + INTERVAL @decalage DAY, 2),
(1047, 'agathe.dumont', 'agathe.dumont@example.com', '$2y$10$rWjqHFaFscZDDJGJ.WnUWOYAtqLP0EKPNFjaaPzUqja3.larz.2K.', 'Dumont', 'Agathe', 'client', 'actif', 1, 1, 0, NULL, 'Villeurbanne', '69100', 327, '2025-03-28 08:54:52' + INTERVAL @decalage DAY, 3),
(1048, 'karim.diallo', 'karim.diallo@example.com', '$2y$10$rWjqHFaFscZDDJGJ.WnUWOYAtqLP0EKPNFjaaPzUqja3.larz.2K.', 'Diallo', 'Karim', 'client', 'actif', 1, 1, 1, '2025-02-11 20:03:48' + INTERVAL @decalage DAY, 'Paris', '75011', 76, '2025-02-11 19:03:48' + INTERVAL @decalage DAY, 3),
(1049, 'fatou.ndiaye', 'fatou.ndiaye@example.com', '$2y$10$rWjqHFaFscZDDJGJ.WnUWOYAtqLP0EKPNFjaaPzUqja3.larz.2K.', 'Ndiaye', 'Fatou', 'client', 'actif', 1, 1, 0, NULL, 'Paris', '75018', 776, '2024-12-31 20:09:18' + INTERVAL @decalage DAY, 3),
(1050, 'mehdi.benali', 'mehdi.benali@example.com', '$2y$10$rWjqHFaFscZDDJGJ.WnUWOYAtqLP0EKPNFjaaPzUqja3.larz.2K.', 'Benali', 'Mehdi', 'client', 'actif', 1, 1, 0, NULL, 'Lille', '59000', 134, '2024-09-01 19:21:15' + INTERVAL @decalage DAY, 4);

INSERT INTO livre (id_livre, titre, auteur, description, couverture, isbn, editeur, annee_parution, nb_pages, dimensions, reliure, prix, is_valide, id_user_auteur, id_categorie, collection, langue) VALUES
(1031, 'Astérix le Gaulois', 'René Goscinny, Albert Uderzo', '50 avant Jésus-Christ : toute la Gaule est occupée… sauf un petit village d''irréductibles qui résiste grâce à une potion magique. Quand les Romains enlèvent le druide Panoramix pour percer son secret, Astérix part à sa rescousse. La toute première aventure du plus célèbre des Gaulois.', 'livres/demo/9782012101333.jpg', '9782012101333', 'Hachette', 2005, 48, '30 cm', 'Relié', 10.99, 1, NULL, (SELECT id_categorie FROM categorie WHERE slug = 'bandes-dessinees'), 'Astérix n° 1', 'Français'),
(1028, 'Blacksad - Tome 1 : Quelque part entre les ombres', 'Juan Díaz Canales, Juanjo Guarnido', 'Dans une ville américaine des années 1950 peuplée d''animaux, le détective privé John Blacksad, un chat noir au passé trouble, enquête sur le meurtre d''une ancienne amante. Un polar noir aux dessins somptueux qui a renouvelé la bande dessinée animalière.', 'livres/demo/9782205049657.jpg', '9782205049657', 'Dargaud', 2000, 48, '30 cm', 'Relié', 14.99, 1, NULL, (SELECT id_categorie FROM categorie WHERE slug = 'bandes-dessinees'), 'Blacksad', 'Français'),
(1023, 'Watchmen', 'Alan Moore, Dave Gibbons', '1985, dans une Amérique où les justiciers masqués ont été interdits, l''un d''eux est retrouvé assassiné. Rorschach, le dernier à agir encore dans l''ombre, mène l''enquête et découvre un complot qui dépasse tout ce qu''il imaginait. Le roman graphique qui a fait entrer les super-héros dans l''âge adulte.', 'livres/demo/9782809406405.jpg', '9782809406405', 'Panini Comics', 2009, 500, 'Grand format', 'Broché', 35.00, 1, NULL, (SELECT id_categorie FROM categorie WHERE slug = 'comics'), NULL, 'Français'),
(1038, 'One Piece - Édition originale - Tome 01', 'Eiichirō Oda', 'Monkey D. Luffy a mangé un fruit du démon qui a rendu son corps élastique, et il n''a qu''un rêve : devenir le roi des pirates. Armé de son chapeau de paille et d''un optimisme à toute épreuve, il prend la mer pour recruter son équipage. Le premier tome d''une aventure devenue légendaire.', 'livres/demo/9782723488525.jpg', '9782723488525', 'Glénat', 2013, 207, '18 cm', 'Broché', 7.20, 1, NULL, (SELECT id_categorie FROM categorie WHERE slug = 'mangas'), 'Shônen manga', 'Français'),
(1030, 'La Chenille qui fait des trous', 'Eric Carle', 'Une petite chenille affamée sort de son œuf et dévore, jour après jour, une pomme, deux poires, trois prunes… en laissant des trous bien réels dans les pages. Un album culte pour apprendre les jours, compter et découvrir la métamorphose en papillon.', 'livres/demo/9782871424574.jpg', '9782871424574', 'Mijade', 2013, 22, '16 x 19 cm', 'Cartonné', 10.00, 1, NULL, (SELECT id_categorie FROM categorie WHERE slug = 'albums'), NULL, 'Français'),
(1032, 'Max et les Maximonstres', 'Maurice Sendak', 'Puni et envoyé dans sa chambre sans dîner, Max voit une forêt pousser autour de son lit. Il navigue jusqu''au pays des Maximonstres, qui le sacrent roi… avant que l''envie de rentrer chez lui ne se fasse sentir. Un classique de l''album sur la colère et l''imaginaire.', 'livres/demo/9782211222716.jpg', '9782211222716', 'L''École des loisirs', 2015, 44, '24 x 26 cm', 'Relié', 13.20, 1, NULL, (SELECT id_categorie FROM categorie WHERE slug = 'albums'), NULL, 'Français'),
(1047, 'Le Petit Nicolas', 'René Goscinny, Jean-Jacques Sempé', 'Nicolas raconte avec ses mots d''enfant la vie à l''école, les bagarres à la récré, les copains Alceste, Rufus ou Agnan, et les dimanches en famille. Des histoires courtes, tendres et irrésistibles, illustrées par les dessins de Sempé.', 'livres/demo/9782070612765.jpg', '9782070612765', 'Gallimard Jeunesse', 1999, 167, '18 cm', 'Poche', 7.40, 1, NULL, (SELECT id_categorie FROM categorie WHERE slug = 'jeunesse'), 'Folio junior', 'Français'),
(1019, 'Matilda', 'Roald Dahl', 'Matilda lit Dickens à quatre ans, mais ses parents ne s''intéressent qu''à la télévision et la directrice de son école terrorise les élèves. Heureusement, la petite fille se découvre un don étonnant… Une revanche joyeuse des enfants sur les adultes injustes.', 'livres/demo/9782070601585.jpg', '9782070601585', 'Gallimard Jeunesse', 2016, 272, '18 cm', 'Poche', 8.20, 1, NULL, (SELECT id_categorie FROM categorie WHERE slug = 'jeunesse'), 'Folio junior', 'Français'),
(1029, 'Hunger Games - Tome 1', 'Suzanne Collins', 'Chaque année, le Capitole oblige les districts de Panem à envoyer deux adolescents s''affronter à mort dans une arène télévisée. Pour sauver sa petite sœur, Katniss se porte volontaire. Un roman d''aventure haletant, qui interroge le pouvoir et le spectacle de la violence.', 'livres/demo/9782266260770.jpg', '9782266260770', 'Pocket Jeunesse', 2015, 420, '18 cm', 'Poche', 8.95, 1, NULL, (SELECT id_categorie FROM categorie WHERE slug = 'ados'), NULL, 'Français'),
(1001, 'La Passe-miroir - Livre 1 : Les Fiancés de l''hiver', 'Christelle Dabos', 'Ophélie peut lire le passé des objets et traverser les miroirs. Fiancée de force à Thorn, un homme froid venu d''une arche lointaine, elle doit quitter les siens pour une cour pleine d''intrigues et de faux-semblants. Le premier tome d''une saga fantastique française devenue incontournable.', 'livres/demo/9782075215466-generee.png', '9782075215466', 'Gallimard Jeunesse', 2024, 608, '18 cm', 'Poche', 10.90, 1, NULL, (SELECT id_categorie FROM categorie WHERE slug = 'ados'), 'Pôle fiction n° 92', 'Français'),
(1037, 'Chanson douce', 'Leïla Slimani', '« Le bébé est mort. » Dès la première ligne, le drame est connu. Le roman remonte le fil : un couple parisien embauche Louise, une nounou parfaite, qui se rend peu à peu indispensable. Une plongée glaçante dans les rapports de domination, couronnée par le prix Goncourt 2016.', 'livres/demo/9782072764929.jpg', '9782072764929', 'Gallimard', 2018, 244, '18 cm', 'Poche', 8.50, 1, NULL, (SELECT id_categorie FROM categorie WHERE slug = 'roman'), 'Folio n° 6492', 'Français'),
(1021, 'L''Anomalie', 'Hervé Le Tellier', 'En juin 2021, un avion atterrit à New York après une violente turbulence… alors que le même vol, avec les mêmes passagers, s''était déjà posé trois mois plus tôt. Entre thriller, science-fiction et portraits intimes, un roman ludique et vertigineux, prix Goncourt 2020.', 'livres/demo/9782072965821.jpg', '9782072965821', 'Gallimard', 2022, 416, '18 cm', 'Poche', 9.20, 1, NULL, (SELECT id_categorie FROM categorie WHERE slug = 'roman'), 'Folio n° 7096', 'Français'),
(1043, 'La Vérité sur l''affaire Harry Quebert', 'Joël Dicker', 'Le corps d''une adolescente disparue en 1975 est retrouvé dans le jardin d''Harry Quebert, écrivain adulé. Son ancien élève Marcus Goldman, romancier en panne d''inspiration, mène l''enquête pour l''innocenter. Un page-turner à tiroirs, prix Goncourt des lycéens 2012.', 'livres/demo/9791032102114.jpg', '9791032102114', 'Éditions de Fallois', 2018, 896, '18 cm', 'Poche', 11.50, 1, NULL, (SELECT id_categorie FROM categorie WHERE slug = 'policier-thriller'), 'De Fallois poche', 'Français'),
(1026, 'Les Rivières pourpres', 'Jean-Christophe Grangé', 'Dans une ville universitaire des Alpes, un corps mutilé est retrouvé suspendu à une falaise. Au même moment, à des centaines de kilomètres, un jeune policier enquête sur la profanation d''une tombe d''enfant. Deux enquêtes qui vont se rejoindre dans un thriller sombre et haletant.', 'livres/demo/9782253171676.jpg', '9782253171676', 'Le Livre de Poche', 2001, 409, '18 cm', 'Poche', 9.20, 1, NULL, (SELECT id_categorie FROM categorie WHERE slug = 'policier-thriller'), NULL, 'Français'),
(1033, 'Dune - Tome 1', 'Frank Herbert', 'Sur Arrakis, planète désertique et seule source de l''épice la plus précieuse de l''univers, la famille Atréides tombe dans un piège politique. Le jeune Paul doit survivre parmi les Fremen et assumer un destin qui le dépasse. Un monument de la science-fiction, entre écologie, religion et pouvoir.', 'livres/demo/9782266320481.jpg', '9782266320481', 'Pocket', 2021, 928, '18 cm', 'Poche', 11.50, 1, NULL, (SELECT id_categorie FROM categorie WHERE slug = 'science-fiction'), NULL, 'Français'),
(1025, '1984', 'George Orwell', 'En Océania, Big Brother surveille chaque geste et le Parti réécrit le passé. Winston Smith, employé au ministère de la Vérité, commence en secret un journal et tombe amoureux, deux actes de rébellion impardonnables. La dystopie qui a inventé notre vocabulaire de la surveillance, dans une traduction moderne.', 'livres/demo/9782072878497.jpg', '9782072878497', 'Gallimard', 2020, 393, '18 cm', 'Poche', 9.30, 1, NULL, (SELECT id_categorie FROM categorie WHERE slug = 'science-fiction'), 'Folio n° 822 (trad. Josée Kamoun)', 'Français'),
(1034, 'Avant toi', 'Jojo Moyes', 'Louisa Clark, pétillante et sans grande ambition, devient l''auxiliaire de vie de Will Traynor, ancien homme d''affaires devenu tétraplégique et amer. Entre eux naît une relation inattendue qui va bouleverser leurs certitudes. Une histoire d''amour drôle et déchirante.', 'livres/demo/9782811215576.jpg', '9782811215576', 'Milady', 2016, 523, '18 cm', 'Poche', 8.20, 1, NULL, (SELECT id_categorie FROM categorie WHERE slug = 'romance'), 'Milady Littérature', 'Français'),
(1002, 'Les Fleurs du mal', 'Charles Baudelaire', 'Spleen et idéal, beauté et laideur, Paris et l''ailleurs : le recueil qui a fait scandale en 1857 et fondé la poésie moderne. Cette édition présente le texte complet, les pièces condamnées et un appareil de notes pour accompagner la lecture.', 'livres/demo/9782253240570-generee.png', '9782253240570', 'Le Livre de Poche', 2021, 377, '18 cm', 'Poche', 4.90, 1, NULL, (SELECT id_categorie FROM categorie WHERE slug = 'poesie-theatre'), 'Le Livre de Poche classique', 'Français'),
(1042, 'Cyrano de Bergerac', 'Edmond Rostand', 'Poète, bretteur et doté d''un nez immense, Cyrano aime en secret sa cousine Roxane. Lorsqu''elle s''éprend du beau Christian, il accepte de prêter ses mots au jeune homme pour la séduire. La comédie héroïque la plus jouée du théâtre français, pleine de panache et de mélancolie.', 'livres/demo/9782070409310.jpg', '9782070409310', 'Gallimard', 2021, 464, '18 cm', 'Poche', 6.70, 1, NULL, (SELECT id_categorie FROM categorie WHERE slug = 'poesie-theatre'), 'Folio classique', 'Français'),
(1046, 'Madame Bovary', 'Gustave Flaubert', 'Nourrie de romans sentimentaux, Emma épouse Charles Bovary, médecin de campagne sans éclat, et s''ennuie à mourir. Elle cherche dans l''adultère et le luxe une vie à la hauteur de ses rêves, jusqu''à la ruine. Un chef-d''œuvre du roman réaliste, d''une ironie implacable.', 'livres/demo/9782070413119.jpg', '9782070413119', 'Gallimard', 2001, 513, '18 cm', 'Poche', 5.90, 1, NULL, (SELECT id_categorie FROM categorie WHERE slug = 'classiques'), 'Folio classique', 'Français'),
(1036, 'Le Petit Prince', 'Antoine de Saint-Exupéry', 'Un aviateur tombé en panne dans le désert rencontre un petit garçon venu d''une autre planète, qui lui demande de dessiner un mouton. Au fil de ses récits de voyage, le petit prince lui parle de sa rose, du renard et de ce qui est essentiel. Un conte poétique lu à tout âge.', 'livres/demo/9782070612758.jpg', '9782070612758', 'Gallimard Jeunesse', 2007, 120, '18 cm', 'Poche', 6.90, 1, NULL, (SELECT id_categorie FROM categorie WHERE slug = 'classiques'), 'Folio junior', 'Français'),
(1035, 'Sapiens : une brève histoire de l''humanité', 'Yuval Noah Harari', 'Comment une espèce de primates sans importance est-elle devenue maîtresse de la planète ? De la révolution cognitive à l''agriculture, de l''argent aux empires et à la science, l''auteur retrace 70 000 ans d''histoire humaine. Un essai accessible et stimulant, devenu un phénomène mondial.', 'livres/demo/9782226257017.jpg', '9782226257017', 'Albin Michel', 2015, 501, '25 cm', 'Broché', 24.90, 1, NULL, (SELECT id_categorie FROM categorie WHERE slug = 'histoire'), NULL, 'Français'),
(1003, 'Histoire de l''art', 'Ernst Gombrich', 'Des peintures préhistoriques à l''art du XXe siècle, Gombrich raconte l''histoire de l''art comme un récit, sans jargon, en s''appuyant sur des centaines de reproductions. L''ouvrage de référence pour découvrir les grandes œuvres et comprendre comment chaque époque a regardé le monde.', 'livres/demo/9781838666699-generee.png', '9781838666699', 'Phaidon', 2023, 1046, '19 cm', 'Broché', 19.95, 1, NULL, (SELECT id_categorie FROM categorie WHERE slug = 'arts-photographie'), 'Nouvelle édition de poche', 'Français'),
(1041, 'Hitchcock / Truffaut - Édition définitive', 'François Truffaut', 'En 1962, le jeune François Truffaut interroge Alfred Hitchcock pendant une semaine, film par film. Il en sort un dialogue passionnant sur la mise en scène, le suspense et le métier de cinéaste, illustré de centaines de photogrammes. Une bible pour tous les amoureux du cinéma.', 'livres/demo/9782070735747.jpg', '9782070735747', 'Gallimard', 2003, 311, '28 cm', 'Relié', 32.00, 1, NULL, (SELECT id_categorie FROM categorie WHERE slug = 'musique'), NULL, 'Français'),
(1024, 'L''Usage du monde', 'Nicolas Bouvier', 'En 1953, Nicolas Bouvier et son ami peintre Thierry Vernet quittent Genève dans une petite Fiat pour rejoindre l''Inde, sans se presser. Yougoslavie, Turquie, Iran, Afghanistan : un récit de voyage lumineux sur la lenteur, les rencontres et l''art de se laisser transformer par la route.', 'livres/demo/9782707179012.jpg', '9782707179012', 'La Découverte', 2014, 375, '19 cm', 'Poche', 12.00, 1, NULL, (SELECT id_categorie FROM categorie WHERE slug = 'voyages'), 'La Découverte poche n° 402', 'Français'),
(1004, 'Une chambre à soi', 'Virginia Woolf', 'Invitée à parler des femmes et du roman, Virginia Woolf pose une condition simple : pour écrire, une femme a besoin d''argent et d''une pièce à elle. Avec humour et finesse, elle imagine le destin d''une sœur de Shakespeare. Un essai fondateur, toujours d''actualité.', 'livres/demo/9782264073853-generee.png', '9782264073853', '10/18', 2018, 171, '18 cm', 'Poche', 7.10, 1, NULL, (SELECT id_categorie FROM categorie WHERE slug = 'essai'), NULL, 'Français'),
(1044, 'Comme un roman', 'Daniel Pennac', 'Pourquoi un enfant qui adorait qu''on lui lise des histoires finit-il par fuir les livres ? Pennac décortique ce désamour et propose une autre manière de transmettre le goût de lire, résumée dans ses célèbres « droits imprescriptibles du lecteur ». Un essai joyeux et libérateur.', 'livres/demo/9782070388905.jpg', '9782070388905', 'Gallimard', 1992, 197, '18 cm', 'Poche', 7.50, 1, NULL, (SELECT id_categorie FROM categorie WHERE slug = 'essai'), 'Folio n° 2724', 'Français'),
(1005, 'Le Robert Junior illustré', 'Le Robert', 'Le dictionnaire de référence de l''école primaire : définitions claires, exemples adaptés, illustrations en couleur, conjugaisons et planches thématiques. Pensé pour accompagner les élèves du CE1 à la 6e dans leurs devoirs comme dans leurs lectures.', 'livres/demo/9782321019893-generee.png', '9782321019893', 'Le Robert', 2024, 1532, '24 cm', 'Relié', 25.90, 1, NULL, (SELECT id_categorie FROM categorie WHERE slug = 'primaire'), NULL, 'Français'),
(1006, 'Bescherelle - La conjugaison pour tous', 'Bescherelle', 'Le guide indispensable pour conjuguer sans faute : des milliers de verbes, leurs tableaux de conjugaison complets et les règles d''accord expliquées simplement. Un compagnon pour le collège et bien au-delà.', 'livres/demo/9782401104754-generee.png', '9782401104754', 'Hatier', 2024, 255, '20 cm', 'Broché', 9.95, 1, NULL, (SELECT id_categorie FROM categorie WHERE slug = 'college'), 'Bescherelle', 'Français'),
(1007, 'ABC du Bac - Mathématiques Terminale spécialité - Annales 2025', 'Julien Besson, Isabelle Lericque', 'Des sujets du bac classés par thème, avec des corrigés détaillés, des rappels de cours et des méthodes pour gagner des points le jour de l''épreuve. Idéal pour réviser la spécialité mathématiques en terminale.', 'livres/demo/9782095035723-generee.png', '9782095035723', 'Nathan', 2024, 383, '21 cm', 'Broché', 9.95, 1, NULL, (SELECT id_categorie FROM categorie WHERE slug = 'lycee'), 'Annales ABC du Bac', 'Français'),
(1008, 'J''intègre - Maths MPSI-MP2I : exercices incontournables', 'Michaël Bages, Pierre Bernard', 'Les exercices classiques de première année de classe préparatoire scientifique, avec des corrigés commentés qui expliquent la démarche avant le calcul. Un entraînement progressif pour réussir les colles et les concours.', 'livres/demo/9782100828845-generee.png', '9782100828845', 'Dunod', 2023, 508, '24 cm', 'Broché', 29.90, 1, NULL, (SELECT id_categorie FROM categorie WHERE slug = 'prepas-concours'), 'J''intègre', 'Français'),
(1045, 'Campbell Biologie (9e édition)', 'Jane B. Reece, Neil A. Campbell', 'Le manuel de référence des études de biologie : de la chimie du vivant à l''écologie, en passant par la génétique et l''évolution, chaque notion est expliquée avec des schémas clairs et des exercices. Un incontournable de la licence et des études de santé.', 'livres/demo/9782761350655.jpg', '9782761350655', 'Pearson ERPI', 2012, 1458, '28 cm', 'Relié', 99.00, 1, NULL, (SELECT id_categorie FROM categorie WHERE slug = 'universite'), NULL, 'Français'),
(1009, 'Lexique des termes juridiques 2025-2026', 'Serge Guinchard, Thierry Debard', 'Des milliers de définitions pour comprendre le vocabulaire du droit, du droit civil au droit européen. Mis à jour chaque année, c''est l''outil de base des étudiants en droit et de tous ceux qui doivent lire un texte juridique.', 'livres/demo/9782247240258-generee.png', '9782247240258', 'Dalloz', 2025, 1193, '20 cm', 'Broché', 16.90, 1, NULL, (SELECT id_categorie FROM categorie WHERE slug = 'droit'), NULL, 'Français'),
(1020, 'Le Capital au XXIe siècle', 'Thomas Piketty', 'À partir de données historiques couvrant trois siècles et une vingtaine de pays, Thomas Piketty étudie l''évolution des inégalités de revenus et de patrimoine. Il montre comment le rendement du capital peut dépasser la croissance et propose des pistes pour y répondre.', 'livres/demo/9782757876503.jpg', '9782757876503', 'Points', 2019, 970, '19 cm', 'Poche', 13.90, 1, NULL, (SELECT id_categorie FROM categorie WHERE slug = 'economie-entreprise'), 'Points Histoire n° 546', 'Français'),
(1040, 'Principes de l''économie', 'N. Gregory Mankiw, Mark P. Taylor', 'Offre et demande, marchés, chômage, inflation, politique monétaire : les bases de la micro et de la macroéconomie expliquées avec des exemples concrets et de nombreux exercices. Le manuel d''introduction utilisé dans les universités du monde entier.', 'livres/demo/9782807313217.jpg', '9782807313217', 'De Boeck Supérieur', 2019, 1020, '28 cm', 'Broché', 59.00, 1, NULL, (SELECT id_categorie FROM categorie WHERE slug = 'economie-entreprise'), NULL, 'Français'),
(1027, 'Coder proprement', 'Robert C. Martin', 'Noms explicites, fonctions courtes, tests, gestion des erreurs : à partir de nombreux exemples de code remanié, l''auteur montre comment écrire un logiciel lisible et facile à faire évoluer. Un classique pour tous les développeurs, débutants comme confirmés.', 'livres/demo/9782326002272.jpg', '9782326002272', 'Pearson', 2019, 457, '23 cm', 'Broché', 35.00, 1, NULL, (SELECT id_categorie FROM categorie WHERE slug = 'informatique'), NULL, 'Français'),
(1022, 'Introduction à l''algorithmique', 'Thomas H. Cormen, Charles E. Leiserson, Ronald L. Rivest, Clifford Stein', 'Tri, graphes, programmation dynamique, complexité : la référence mondiale de l''algorithmique présente chaque méthode avec rigueur, des pseudo-codes clairs et des centaines d''exercices. L''ouvrage de chevet des étudiants en informatique.', 'livres/demo/9782100039227.jpg', '9782100039227', 'Dunod', 2002, 1146, '25 cm', 'Broché', 69.00, 1, NULL, (SELECT id_categorie FROM categorie WHERE slug = 'informatique'), NULL, 'Français'),
(1010, 'Une brève histoire du temps', 'Stephen Hawking', 'Du Big Bang aux trous noirs, Stephen Hawking explique sans équation les grandes questions de la cosmologie : d''où vient l''univers, le temps peut-il s''inverser, comment tout finira-t-il ? Le livre qui a rendu la physique moderne accessible à des millions de lecteurs.', 'livres/demo/9782080297136-generee.png', '9782080297136', 'Flammarion', 2022, 278, '18 cm', 'Poche', 9.00, 1, NULL, (SELECT id_categorie FROM categorie WHERE slug = 'sciences-techniques'), 'Champs sciences', 'Français'),
(1011, 'Le Charme discret de l''intestin', 'Giulia Enders', 'Organe mal aimé, l''intestin joue pourtant un rôle immense dans notre santé et même notre humeur. Avec humour et clarté, une jeune médecin explique la digestion, le microbiote et les petits tracas du quotidien. Un best-seller de vulgarisation, dans une édition mise à jour.', 'livres/demo/9782330188481-generee.png', '9782330188481', 'Actes Sud', 2024, 381, '18 cm', 'Poche', 9.70, 1, NULL, (SELECT id_categorie FROM categorie WHERE slug = 'sante'), 'Babel essai n° 1925', 'Français'),
(1012, 'Bescherelle - L''anglais pour tous', 'Michèle Malavieille, Wilfrid Rotgé', 'Toute la grammaire anglaise expliquée simplement, des temps aux modaux en passant par les prépositions, avec des exemples tirés de la langue courante et des encadrés sur les pièges à éviter. Une référence du lycée jusqu''aux études supérieures.', 'livres/demo/9782401085237-generee.png', '9782401085237', 'Hatier', 2022, 431, '23 cm', 'Broché', 15.90, 1, NULL, (SELECT id_categorie FROM categorie WHERE slug = 'langues-dictionnaires'), 'Bescherelle', 'Français'),
(1013, 'Je sais cuisiner', 'Ginette Mathiot', 'Plus de 2 000 recettes de la cuisine française, des sauces de base aux desserts de fête, avec les techniques et les temps de cuisson. Le livre de cuisine transmis de génération en génération depuis 1932, ici dans une nouvelle édition revue.', 'livres/demo/9782226441133-generee.png', '9782226441133', 'Albin Michel', 2019, 754, '20 cm', 'Broché', 22.90, 1, NULL, (SELECT id_categorie FROM categorie WHERE slug = 'cuisine'), NULL, 'Français'),
(1039, 'Les Quatre Accords toltèques', 'Don Miguel Ruiz', 'Que votre parole soit impeccable, ne réagissez à rien de façon personnelle, ne faites pas de suppositions, faites toujours de votre mieux : quatre principes simples, inspirés de la sagesse toltèque, pour se libérer des croyances qui nous limitent. Un court livre devenu un classique du développement personnel.', 'livres/demo/9782889116546.jpg', '9782889116546', 'Jouvence', 2016, 144, '18 cm', 'Broché', 8.90, 1, NULL, (SELECT id_categorie FROM categorie WHERE slug = 'developpement-personnel'), NULL, 'Français'),
(1014, 'Born to run', 'Christopher McDougall', 'Pourquoi le corps humain semble-t-il fait pour courir de longues distances ? Pour le comprendre, un journaliste coureur part à la rencontre des Tarahumaras, un peuple mexicain d''ultra-coureurs. Un récit d''aventure et d''enquête qui a lancé la vague de la course minimaliste.', 'livres/demo/9782375021491-generee.png', '9782375021491', 'Paulsen', 2022, 440, '18 cm', 'Poche', 9.90, 1, NULL, (SELECT id_categorie FROM categorie WHERE slug = 'sport'), NULL, 'Français'),
(1015, 'Le Potager du paresseux', 'Didier Helmstetter', 'Ni bêchage, ni arrosage intensif, ni traitement : un jardinier alsacien explique comment il produit des légumes en abondance en laissant faire la nature, grâce au paillage et à l''observation. Une méthode simple et pleine de bon sens pour démarrer son potager.', 'livres/demo/9782266338981-generee.png', '9782266338981', 'Pocket', 2024, 442, '18 cm', 'Poche', 9.70, 1, NULL, (SELECT id_categorie FROM categorie WHERE slug = 'maison-jardin'), 'Pocket n° 19230', 'Français'),
(1016, 'Les Ombres de la Croix-Rousse', 'Élise Marchand', 'Un hiver à Lyon. Dans les traboules de la Croix-Rousse, un bouquiniste est retrouvé mort au milieu de ses cartons de livres anciens. La commandante Salomé Ferrand remonte la piste d''un manuscrit disparu, entre ateliers de soyeux et secrets de famille. Un premier polar qui fait de la ville un personnage à part entière.', 'livres/demo/ombres-croix-rousse-generee.png', NULL, 'Autoédition', 2026, 312, '21 x 14 cm', 'Broché', 18.00, 1, 1001, (SELECT id_categorie FROM categorie WHERE slug = 'policier-thriller'), NULL, 'Français'),
(1017, 'Pixel et Moustache - Tome 1 : Le chat qui voulait voler', 'Bastien Lemoine', 'Moustache, chat de gouttière, rêve de voler comme les pigeons du quartier. Avec Pixel, une souris bricoleuse, il imagine les machines les plus folles pour décoller du toit. Une bande dessinée tendre et drôle, à lire dès 6 ans.', 'livres/demo/pixel-moustache-1-generee.png', NULL, 'Autoédition', 2025, 48, '30 x 22 cm', 'Relié', 12.50, 1, 1002, (SELECT id_categorie FROM categorie WHERE slug = 'bandes-dessinees'), NULL, 'Français'),
(1018, 'Pixel et Moustache - Tome 2 : Panique au musée', 'Bastien Lemoine', 'Une nuit au musée d''histoire naturelle : Pixel et Moustache s''y retrouvent enfermés au moment où le squelette de dinosaure disparaît. Les deux amis mènent l''enquête entre les vitrines. Le deuxième tome des aventures du duo.', 'livres/demo/pixel-moustache-2-generee.png', NULL, 'Autoédition', 2026, 48, '30 x 22 cm', 'Relié', 12.50, 0, 1002, (SELECT id_categorie FROM categorie WHERE slug = 'bandes-dessinees'), NULL, 'Français');

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Magie & surnaturel', 'Aventure', 'Dès 12 ans', 'Série en plusieurs tomes', 'Adapté en film ou série', 'Format poche') WHERE l.id_livre = 1;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Action & combats', 'Magie & surnaturel', 'Dès 15 ans', 'Série en plusieurs tomes', 'Adapté en film ou série') WHERE l.id_livre = 2;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Action & combats', 'Magie & surnaturel', 'Dès 12 ans', 'Premier tome', 'Série en plusieurs tomes', 'Adapté en film ou série') WHERE l.id_livre = 3;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Adultes', 'Format poche', 'Facile à lire') WHERE l.id_livre = 4;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Suspense', 'Adultes', 'Format poche') WHERE l.id_livre = 5;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Suspense', 'Adultes', 'Premier tome', 'Série en plusieurs tomes', 'Prix littéraire', 'Adapté en film ou série', 'Format poche') WHERE l.id_livre = 6;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Famille', 'Humour', 'Passage à l''âge adulte', 'Histoire vraie', 'Série en plusieurs tomes', 'Dès 15 ans') WHERE l.id_livre = 7;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Famille', 'Feel-good', 'Adultes', 'Série en plusieurs tomes', 'Adapté en film ou série') WHERE l.id_livre = 8;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Engagement', 'Adultes', 'Histoire vraie', 'Adapté en film ou série', 'Format poche') WHERE l.id_livre = 9;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Super-héros', 'Action & combats', 'Dès 15 ans', 'Série en plusieurs tomes') WHERE l.id_livre = 10;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Action & combats', 'Magie & surnaturel', 'Dès 15 ans', 'Série en plusieurs tomes', 'Adapté en film ou série') WHERE l.id_livre = 11;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Humour', 'Aventure', 'Dès 6 ans', 'Premier tome', 'Série en plusieurs tomes', 'Adapté en film ou série') WHERE l.id_livre = 1031;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Suspense', 'Adultes', 'Premier tome', 'Série en plusieurs tomes') WHERE l.id_livre = 1028;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Super-héros', 'Suspense', 'Adultes', 'Adapté en film ou série') WHERE l.id_livre = 1023;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Aventure', 'Humour', 'Amitié', 'Dès 9 ans', 'Premier tome', 'Série en plusieurs tomes', 'Adapté en film ou série') WHERE l.id_livre = 1038;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Nature & écologie', 'Dès 3 ans', 'Édition illustrée') WHERE l.id_livre = 1030;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Aventure', 'Famille', 'Dès 3 ans', 'Édition illustrée', 'Adapté en film ou série') WHERE l.id_livre = 1032;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Humour', 'Amitié', 'Famille', 'Dès 6 ans', 'Facile à lire', 'Adapté en film ou série', 'Format poche') WHERE l.id_livre = 1047;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Humour', 'Famille', 'Magie & surnaturel', 'Dès 9 ans', 'Adapté en film ou série', 'Format poche') WHERE l.id_livre = 1019;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Aventure', 'Action & combats', 'Dès 12 ans', 'Premier tome', 'Série en plusieurs tomes', 'Adapté en film ou série', 'Format poche') WHERE l.id_livre = 1029;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Magie & surnaturel', 'Aventure', 'Dès 12 ans', 'Premier tome', 'Série en plusieurs tomes', 'Format poche') WHERE l.id_livre = 1001;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Suspense', 'Famille', 'Adultes', 'Prix littéraire', 'Adapté en film ou série', 'Format poche') WHERE l.id_livre = 1037;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Suspense', 'Adultes', 'Prix littéraire', 'Format poche') WHERE l.id_livre = 1021;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Suspense', 'Adultes', 'Prix littéraire', 'Adapté en film ou série', 'Format poche') WHERE l.id_livre = 1043;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Suspense', 'Adultes', 'Adapté en film ou série', 'Format poche') WHERE l.id_livre = 1026;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Aventure', 'Adultes', 'Premier tome', 'Série en plusieurs tomes', 'Adapté en film ou série', 'Format poche') WHERE l.id_livre = 1033;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Engagement', 'Suspense', 'Adultes', 'Adapté en film ou série', 'Format poche') WHERE l.id_livre = 1025;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Feel-good', 'Adultes', 'Adapté en film ou série', 'Format poche') WHERE l.id_livre = 1034;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Adultes', 'Format poche') WHERE l.id_livre = 1002;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Aventure', 'Adultes', 'Adapté en film ou série', 'Format poche') WHERE l.id_livre = 1042;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Adultes', 'Adapté en film ou série', 'Format poche') WHERE l.id_livre = 1046;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Amitié', 'Dès 9 ans', 'Édition illustrée', 'Facile à lire', 'Adapté en film ou série', 'Format poche') WHERE l.id_livre = 1036;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Adultes') WHERE l.id_livre = 1035;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Adultes', 'Édition illustrée') WHERE l.id_livre = 1003;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Adultes', 'Édition illustrée') WHERE l.id_livre = 1041;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Aventure', 'Amitié', 'Adultes', 'Histoire vraie', 'Format poche') WHERE l.id_livre = 1024;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Engagement', 'Adultes', 'Format poche') WHERE l.id_livre = 1004;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Famille', 'Adultes', 'Facile à lire', 'Format poche') WHERE l.id_livre = 1044;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Dès 6 ans', 'Édition illustrée') WHERE l.id_livre = 1005;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Dès 12 ans') WHERE l.id_livre = 1006;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Dès 15 ans') WHERE l.id_livre = 1007;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Adultes') WHERE l.id_livre = 1008;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Adultes', 'Édition illustrée') WHERE l.id_livre = 1045;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Adultes') WHERE l.id_livre = 1009;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Engagement', 'Adultes', 'Format poche') WHERE l.id_livre = 1020;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Adultes') WHERE l.id_livre = 1040;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Adultes') WHERE l.id_livre = 1027;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Adultes') WHERE l.id_livre = 1022;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Adultes', 'Format poche') WHERE l.id_livre = 1010;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Humour', 'Adultes', 'Édition illustrée', 'Format poche') WHERE l.id_livre = 1011;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Dès 15 ans') WHERE l.id_livre = 1012;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Famille', 'Adultes') WHERE l.id_livre = 1013;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Feel-good', 'Adultes', 'Facile à lire') WHERE l.id_livre = 1039;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Aventure', 'Adultes', 'Histoire vraie', 'Format poche') WHERE l.id_livre = 1014;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Nature & écologie', 'Adultes', 'Édition illustrée', 'Format poche') WHERE l.id_livre = 1015;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Suspense', 'Adultes') WHERE l.id_livre = 1016;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Humour', 'Aventure', 'Amitié', 'Dès 6 ans', 'Premier tome', 'Série en plusieurs tomes') WHERE l.id_livre = 1017;

INSERT IGNORE INTO livre_tag (id_livre, id_tag) SELECT l.id_livre, t.id_tag FROM livre l INNER JOIN tag t ON t.nom IN ('Humour', 'Aventure', 'Amitié', 'Dès 6 ans', 'Série en plusieurs tomes') WHERE l.id_livre = 1018;

INSERT INTO exemplaire (id_exemplaire, id_livre, etat, prix, type_offre, is_disponible, quantite) VALUES
(1001, 1031, 'neuf', 10.99, 'vente', 1, 15),
(1002, 1031, 'tres bon', 6.60, 'vente', 1, 2),
(1003, 1031, 'bon', 4.90, 'vente', 1, 5),
(1004, 1031, 'use', 3.30, 'vente', 1, 3),
(1005, 1028, 'neuf', 14.99, 'vente', 1, 10),
(1006, 1028, 'tres bon', 9.00, 'vente', 1, 2),
(1007, 1028, 'bon', 6.70, 'vente', 1, 3),
(1008, 1028, 'use', 4.50, 'vente', 1, 1),
(1009, 1023, 'neuf', 35.00, 'vente', 1, 11),
(1010, 1023, 'tres bon', 21.00, 'vente', 1, 2),
(1011, 1023, 'bon', 15.80, 'vente', 1, 6),
(1012, 1023, 'use', 10.50, 'vente', 1, 2),
(1013, 1038, 'neuf', 7.20, 'vente', 1, 12),
(1014, 1038, 'tres bon', 4.30, 'vente', 1, 2),
(1015, 1038, 'bon', 3.20, 'vente', 1, 4),
(1016, 1038, 'use', 2.20, 'vente', 1, 3),
(1017, 1030, 'neuf', 10.00, 'vente', 1, 13),
(1018, 1030, 'tres bon', 6.00, 'vente', 1, 2),
(1019, 1030, 'bon', 4.50, 'vente', 1, 2),
(1020, 1030, 'use', 3.00, 'vente', 1, 1),
(1021, 1032, 'neuf', 13.20, 'vente', 1, 14),
(1022, 1032, 'tres bon', 7.90, 'vente', 1, 2),
(1023, 1032, 'bon', 5.90, 'vente', 1, 5),
(1024, 1032, 'use', 4.00, 'vente', 1, 2),
(1025, 1047, 'neuf', 7.40, 'vente', 1, 15),
(1026, 1047, 'tres bon', 4.40, 'vente', 1, 2),
(1027, 1047, 'bon', 3.30, 'vente', 1, 3),
(1028, 1047, 'use', 2.20, 'vente', 1, 3),
(1029, 1019, 'neuf', 8.20, 'vente', 1, 10),
(1030, 1019, 'tres bon', 4.90, 'vente', 1, 2),
(1031, 1019, 'bon', 3.70, 'vente', 1, 6),
(1032, 1019, 'use', 2.50, 'vente', 1, 1),
(1033, 1029, 'neuf', 8.95, 'vente', 1, 0),
(1034, 1029, 'tres bon', 5.40, 'vente', 1, 0),
(1035, 1029, 'bon', 4.00, 'vente', 1, 0),
(1036, 1029, 'use', 2.70, 'vente', 1, 0),
(1037, 1001, 'neuf', 10.90, 'vente', 1, 12),
(1038, 1001, 'tres bon', 6.50, 'vente', 1, 2),
(1039, 1001, 'bon', 4.90, 'vente', 1, 2),
(1040, 1001, 'use', 3.30, 'vente', 1, 3),
(1041, 1037, 'neuf', 8.50, 'vente', 1, 13),
(1042, 1037, 'tres bon', 5.10, 'vente', 1, 2),
(1043, 1037, 'bon', 3.80, 'vente', 1, 5),
(1044, 1037, 'use', 2.60, 'vente', 1, 1),
(1045, 1021, 'neuf', 9.20, 'vente', 1, 14),
(1046, 1021, 'tres bon', 5.50, 'vente', 1, 2),
(1047, 1021, 'bon', 4.10, 'vente', 1, 3),
(1048, 1021, 'use', 2.80, 'vente', 1, 2),
(1049, 1043, 'neuf', 11.50, 'vente', 1, 15),
(1050, 1043, 'tres bon', 6.90, 'vente', 1, 2),
(1051, 1043, 'bon', 5.20, 'vente', 1, 6),
(1052, 1043, 'use', 3.50, 'vente', 1, 3),
(1053, 1026, 'neuf', 9.20, 'vente', 1, 10),
(1054, 1026, 'tres bon', 5.50, 'vente', 1, 2),
(1055, 1026, 'bon', 4.10, 'vente', 1, 4),
(1056, 1026, 'use', 2.80, 'vente', 1, 1),
(1057, 1033, 'neuf', 11.50, 'vente', 1, 0),
(1058, 1033, 'tres bon', 6.90, 'vente', 1, 0),
(1059, 1033, 'bon', 5.20, 'vente', 1, 0),
(1060, 1033, 'use', 3.50, 'vente', 1, 0),
(1061, 1025, 'neuf', 9.30, 'vente', 1, 12),
(1062, 1025, 'tres bon', 5.60, 'vente', 1, 2),
(1063, 1025, 'bon', 4.20, 'vente', 1, 5),
(1064, 1025, 'use', 2.80, 'vente', 1, 3),
(1065, 1034, 'neuf', 8.20, 'vente', 1, 13),
(1066, 1034, 'tres bon', 4.90, 'vente', 1, 2),
(1067, 1034, 'bon', 3.70, 'vente', 1, 3),
(1068, 1034, 'use', 2.50, 'vente', 1, 1),
(1069, 1002, 'neuf', 4.90, 'vente', 1, 14),
(1070, 1002, 'tres bon', 2.90, 'vente', 1, 2),
(1071, 1002, 'bon', 2.20, 'vente', 1, 6),
(1072, 1002, 'use', 1.50, 'vente', 1, 2),
(1073, 1042, 'neuf', 6.70, 'vente', 1, 15),
(1074, 1042, 'tres bon', 4.00, 'vente', 1, 2),
(1075, 1042, 'bon', 3.00, 'vente', 1, 4),
(1076, 1042, 'use', 2.00, 'vente', 1, 3),
(1077, 1046, 'neuf', 5.90, 'vente', 1, 10),
(1078, 1046, 'tres bon', 3.50, 'vente', 1, 2),
(1079, 1046, 'bon', 2.70, 'vente', 1, 2),
(1080, 1046, 'use', 1.80, 'vente', 1, 1),
(1081, 1036, 'neuf', 6.90, 'vente', 1, 11),
(1082, 1036, 'tres bon', 4.10, 'vente', 1, 2),
(1083, 1036, 'bon', 3.10, 'vente', 1, 5),
(1084, 1036, 'use', 2.10, 'vente', 1, 2),
(1085, 1035, 'neuf', 24.90, 'vente', 1, 0),
(1086, 1035, 'tres bon', 14.90, 'vente', 1, 0),
(1087, 1035, 'bon', 11.20, 'vente', 1, 0),
(1088, 1035, 'use', 7.50, 'vente', 1, 0),
(1089, 1003, 'neuf', 19.95, 'vente', 1, 13),
(1090, 1003, 'tres bon', 12.00, 'vente', 1, 2),
(1091, 1003, 'bon', 9.00, 'vente', 1, 6),
(1092, 1003, 'use', 6.00, 'vente', 1, 1),
(1093, 1041, 'neuf', 32.00, 'vente', 1, 14),
(1094, 1041, 'tres bon', 19.20, 'vente', 1, 2),
(1095, 1041, 'bon', 14.40, 'vente', 1, 4),
(1096, 1041, 'use', 9.60, 'vente', 1, 2),
(1097, 1024, 'neuf', 12.00, 'vente', 1, 15),
(1098, 1024, 'tres bon', 7.20, 'vente', 1, 2),
(1099, 1024, 'bon', 5.40, 'vente', 1, 2),
(1100, 1024, 'use', 3.60, 'vente', 1, 3),
(1101, 1004, 'neuf', 7.10, 'vente', 1, 10),
(1102, 1004, 'tres bon', 4.30, 'vente', 1, 2),
(1103, 1004, 'bon', 3.20, 'vente', 1, 5),
(1104, 1004, 'use', 2.10, 'vente', 1, 1),
(1105, 1044, 'neuf', 7.50, 'vente', 1, 11),
(1106, 1044, 'tres bon', 4.50, 'vente', 1, 2),
(1107, 1044, 'bon', 3.40, 'vente', 1, 3),
(1108, 1044, 'use', 2.30, 'vente', 1, 2),
(1109, 1005, 'neuf', 25.90, 'vente', 1, 12),
(1110, 1005, 'tres bon', 15.50, 'vente', 1, 2),
(1111, 1005, 'bon', 11.70, 'vente', 1, 6),
(1112, 1005, 'use', 7.80, 'vente', 1, 3),
(1113, 1006, 'neuf', 9.95, 'vente', 1, 13),
(1114, 1006, 'tres bon', 6.00, 'vente', 1, 2),
(1115, 1006, 'bon', 4.50, 'vente', 1, 4),
(1116, 1006, 'use', 3.00, 'vente', 1, 1),
(1117, 1007, 'neuf', 9.95, 'vente', 1, 14),
(1118, 1007, 'tres bon', 6.00, 'vente', 1, 2),
(1119, 1007, 'bon', 4.50, 'vente', 1, 2),
(1120, 1007, 'use', 3.00, 'vente', 1, 2),
(1121, 1008, 'neuf', 29.90, 'vente', 1, 15),
(1122, 1008, 'tres bon', 17.90, 'vente', 1, 2),
(1123, 1008, 'bon', 13.50, 'vente', 1, 5),
(1124, 1008, 'use', 9.00, 'vente', 1, 3),
(1125, 1045, 'neuf', 99.00, 'vente', 1, 10),
(1126, 1045, 'tres bon', 59.40, 'vente', 1, 2),
(1127, 1045, 'bon', 44.60, 'vente', 1, 3),
(1128, 1045, 'use', 29.70, 'vente', 1, 1),
(1129, 1009, 'neuf', 16.90, 'vente', 1, 11),
(1130, 1009, 'tres bon', 10.10, 'vente', 1, 2),
(1131, 1009, 'bon', 7.60, 'vente', 1, 6),
(1132, 1009, 'use', 5.10, 'vente', 1, 2),
(1133, 1020, 'neuf', 13.90, 'vente', 1, 12),
(1134, 1020, 'tres bon', 8.30, 'vente', 1, 2),
(1135, 1020, 'bon', 6.30, 'vente', 1, 4),
(1136, 1020, 'use', 4.20, 'vente', 1, 3),
(1137, 1040, 'neuf', 59.00, 'vente', 1, 13),
(1138, 1040, 'tres bon', 35.40, 'vente', 1, 2),
(1139, 1040, 'bon', 26.60, 'vente', 1, 2),
(1140, 1040, 'use', 17.70, 'vente', 1, 1),
(1141, 1027, 'neuf', 35.00, 'vente', 1, 14),
(1142, 1027, 'tres bon', 21.00, 'vente', 1, 2),
(1143, 1027, 'bon', 15.80, 'vente', 1, 5),
(1144, 1027, 'use', 10.50, 'vente', 1, 2),
(1145, 1022, 'neuf', 69.00, 'vente', 1, 15),
(1146, 1022, 'tres bon', 41.40, 'vente', 1, 2),
(1147, 1022, 'bon', 31.10, 'vente', 1, 3),
(1148, 1022, 'use', 20.70, 'vente', 1, 3),
(1149, 1010, 'neuf', 9.00, 'vente', 1, 10),
(1150, 1010, 'tres bon', 5.40, 'vente', 1, 2);

INSERT INTO exemplaire (id_exemplaire, id_livre, etat, prix, type_offre, is_disponible, quantite) VALUES
(1151, 1010, 'bon', 4.10, 'vente', 1, 6),
(1152, 1010, 'use', 2.70, 'vente', 1, 1),
(1153, 1011, 'neuf', 9.70, 'vente', 1, 11),
(1154, 1011, 'tres bon', 5.80, 'vente', 1, 2),
(1155, 1011, 'bon', 4.40, 'vente', 1, 4),
(1156, 1011, 'use', 2.90, 'vente', 1, 2),
(1157, 1012, 'neuf', 15.90, 'vente', 1, 12),
(1158, 1012, 'tres bon', 9.50, 'vente', 1, 2),
(1159, 1012, 'bon', 7.20, 'vente', 1, 2),
(1160, 1012, 'use', 4.80, 'vente', 1, 3),
(1161, 1013, 'neuf', 22.90, 'vente', 1, 13),
(1162, 1013, 'tres bon', 13.70, 'vente', 1, 2),
(1163, 1013, 'bon', 10.30, 'vente', 1, 5),
(1164, 1013, 'use', 6.90, 'vente', 1, 1),
(1165, 1039, 'neuf', 8.90, 'vente', 1, 14),
(1166, 1039, 'tres bon', 5.30, 'vente', 1, 2),
(1167, 1039, 'bon', 4.00, 'vente', 1, 3),
(1168, 1039, 'use', 2.70, 'vente', 1, 2),
(1169, 1014, 'neuf', 9.90, 'vente', 1, 15),
(1170, 1014, 'tres bon', 5.90, 'vente', 1, 2),
(1171, 1014, 'bon', 4.50, 'vente', 1, 6),
(1172, 1014, 'use', 3.00, 'vente', 1, 3),
(1173, 1015, 'neuf', 9.70, 'vente', 1, 10),
(1174, 1015, 'tres bon', 5.80, 'vente', 1, 2),
(1175, 1015, 'bon', 4.40, 'vente', 1, 4),
(1176, 1015, 'use', 2.90, 'vente', 1, 1),
(1177, 1016, 'neuf', 18.00, 'vente', 1, 15),
(1178, 1017, 'neuf', 12.50, 'vente', 1, 15);

UPDATE evenement SET attente_auto = 1 WHERE id_evenement = 2;

INSERT INTO evenement (id_evenement, titre, description, type, auteurs, lieu, date_debut, date_fin, places_max, attente_auto, statut, image, id_createur, date_creation, date_validation, id_validateur) VALUES
(1001, 'Rencontre : polars d''ici et d''ailleurs', 'Pourquoi aime-t-on tant avoir peur en lisant ? Libraires et lecteurs partagent leurs coups de cœur, du polar scandinave au roman noir français, et une autrice lyonnaise raconte comment naît une enquête. Échanges, conseils de lecture et sélection de polars d''occasion sur place.', 'rencontre', 'Élise Marchand et des libraires lyonnais', 'Bibliothèque municipale, Villeurbanne', '2026-11-28 15:00:00' + INTERVAL @decalage DAY, '2026-11-28 17:00:00' + INTERVAL @decalage DAY, 40, 1, 'publie', NULL, NULL, '2026-10-01 09:30:00' + INTERVAL @decalage DAY, NULL, NULL),
(1002, 'Troc de livres de la rentrée', 'Apportez les livres que vous avez déjà lus et repartez avec de nouvelles lectures. Romans, BD, manuels scolaires ou livres pour enfants : tout s''échange, dans la bonne humeur et autour d''un café.', 'atelier', NULL, 'Maison des associations, Lyon 3e', '2026-09-19 10:00:00' + INTERVAL @decalage DAY, '2026-09-19 13:00:00' + INTERVAL @decalage DAY, 30, 1, 'publie', NULL, NULL, '2026-08-25 11:00:00' + INTERVAL @decalage DAY, NULL, NULL),
(1003, 'Lecture-dédicace : « Les Ombres de la Croix-Rousse »', 'Élise Marchand lit les premiers chapitres de son polar lyonnais, revient sur l''écriture du livre et ses repérages dans les traboules, puis dédicace les exemplaires. Questions du public bienvenues.', 'dedicace', 'Élise Marchand', 'Librairie partenaire, Lyon 4e', '2026-12-05 16:00:00' + INTERVAL @decalage DAY, '2026-12-05 18:00:00' + INTERVAL @decalage DAY, 20, 1, 'publie', NULL, 1001, '2026-10-08 18:12:00' + INTERVAL @decalage DAY, '2026-10-10 09:45:00' + INTERVAL @decalage DAY, 1),
(1004, 'Atelier BD : dessine ton premier strip', 'Un atelier pour les 8-12 ans : imaginer un personnage, découper une histoire en trois cases et la mettre en couleur. Le matériel est fourni, chaque enfant repart avec sa planche.', 'atelier', 'Bastien Lemoine', 'Médiathèque, Villeurbanne', '2026-12-16 14:00:00' + INTERVAL @decalage DAY, '2026-12-16 16:00:00' + INTERVAL @decalage DAY, 15, 0, 'en_attente', NULL, 1002, '2026-10-22 20:05:00' + INTERVAL @decalage DAY, NULL, NULL);

INSERT IGNORE INTO evenement_livre (id_evenement, id_livre) VALUES
(1001, 1043),
(1001, 1026),
(1001, 1016),
(1003, 1016),
(1004, 1017),
(1004, 1031);

INSERT INTO adresse_livraison (id_user, libelle, prenom, nom, adresse, complement, code_postal, ville, telephone, par_defaut, date_creation) VALUES
(1001, 'Maison', 'Élise', 'Marchand', '60 boulevard de la Croix-Rousse', NULL, '69004', 'Lyon', '06 65 49 61 78', 1, '2025-11-14 10:30:00' + INTERVAL @decalage DAY),
(1002, 'Maison', 'Bastien', 'Lemoine', '60 rue du Moulin', NULL, '69100', 'Villeurbanne', '06 73 97 63 57', 1, '2025-11-14 10:30:00' + INTERVAL @decalage DAY),
(1002, 'Travail', 'Bastien', 'Lemoine', '23 allée des Tilleuls', 'Accueil, 2e étage', '69003', 'Lyon', '06 73 97 63 57', 0, '2025-11-14 10:30:00' + INTERVAL @decalage DAY),
(1003, 'Maison', 'Léa', 'Martin', '32 rue Saint-Michel', NULL, '69003', 'Lyon', '06 82 32 74 34', 1, '2025-09-03 09:47:55' + INTERVAL @decalage DAY),
(1004, 'Maison', 'Hugo', 'Bernard', '22 allée des Tilleuls', NULL, '69007', 'Lyon', '06 34 81 69 21', 1, '2026-01-15 16:58:12' + INTERVAL @decalage DAY),
(1005, 'Maison', 'Chloé', 'Dubois', '18 rue Pasteur', NULL, '69100', 'Villeurbanne', '06 32 35 82 17', 1, '2025-09-19 13:12:44' + INTERVAL @decalage DAY),
(1005, 'Travail', 'Chloé', 'Dubois', '119 cours Gambetta', 'Accueil, 2e étage', '69003', 'Lyon', '06 32 35 82 17', 0, '2025-09-19 13:12:44' + INTERVAL @decalage DAY),
(1006, 'Maison', 'Lucas', 'Thomas', '92 rue du Moulin', NULL, '75011', 'Paris', '06 64 54 77 81', 1, '2026-08-30 21:01:31' + INTERVAL @decalage DAY),
(1007, 'Maison', 'Manon', 'Robert', '11 avenue de la Libération', NULL, '75018', 'Paris', '06 82 71 79 45', 1, '2026-09-22 13:14:13' + INTERVAL @decalage DAY),
(1008, 'Maison', 'Nathan', 'Richard', '44 rue Paul Bert', NULL, '59000', 'Lille', '06 37 86 54 32', 1, '2026-03-17 12:41:46' + INTERVAL @decalage DAY),
(1008, 'Travail', 'Nathan', 'Richard', '72 avenue Jean Jaurès', 'Accueil, 2e étage', '69003', 'Lyon', '06 37 86 54 32', 0, '2026-03-17 12:41:46' + INTERVAL @decalage DAY),
(1009, 'Maison', 'Inès', 'Petit', '35 allée des Tilleuls', NULL, '13005', 'Marseille', '06 43 88 83 17', 1, '2025-09-18 17:36:56' + INTERVAL @decalage DAY),
(1010, 'Maison', 'Théo', 'Durand', '33 avenue Jean Jaurès', NULL, '33000', 'Bordeaux', '06 34 31 57 72', 1, '2026-04-17 14:58:03' + INTERVAL @decalage DAY),
(1011, 'Maison', 'Camille', 'Leroy', '63 chemin des Vignes', NULL, '44000', 'Nantes', '06 26 87 94 54', 1, '2026-01-19 15:06:53' + INTERVAL @decalage DAY),
(1011, 'Travail', 'Camille', 'Leroy', '84 rue des Lilas', 'Accueil, 2e étage', '69003', 'Lyon', '06 26 87 94 54', 0, '2026-01-19 15:06:53' + INTERVAL @decalage DAY),
(1012, 'Maison', 'Louis', 'Moreau', '26 place Bellecour', NULL, '31000', 'Toulouse', '06 49 49 25 30', 1, '2025-09-03 08:47:15' + INTERVAL @decalage DAY),
(1013, 'Maison', 'Sarah', 'Simon', '49 rue Pasteur', NULL, '38000', 'Grenoble', '06 20 89 63 94', 1, '2026-06-16 17:07:29' + INTERVAL @decalage DAY),
(1014, 'Maison', 'Jules', 'Laurent', '93 boulevard de la Croix-Rousse', NULL, '60200', 'Compiègne', '06 25 99 52 92', 1, '2026-07-08 09:57:50' + INTERVAL @decalage DAY),
(1014, 'Travail', 'Jules', 'Laurent', '82 rue Victor Hugo', 'Accueil, 2e étage', '69003', 'Lyon', '06 25 99 52 92', 0, '2026-07-08 09:57:50' + INTERVAL @decalage DAY),
(1015, 'Maison', 'Emma', 'Lefebvre', '54 rue du Moulin', NULL, '35000', 'Rennes', '06 52 85 77 81', 1, '2025-11-29 15:55:16' + INTERVAL @decalage DAY),
(1016, 'Maison', 'Gabriel', 'Michel', '3 rue Victor Hugo', NULL, '67000', 'Strasbourg', '06 20 82 91 10', 1, '2026-09-26 21:29:03' + INTERVAL @decalage DAY),
(1017, 'Maison', 'Jade', 'Garcia', '61 allée des Tilleuls', NULL, '69003', 'Lyon', '06 12 51 70 93', 1, '2026-04-25 16:17:13' + INTERVAL @decalage DAY),
(1017, 'Travail', 'Jade', 'Garcia', '12 chemin des Vignes', 'Accueil, 2e étage', '69003', 'Lyon', '06 12 51 70 93', 0, '2026-04-25 16:17:13' + INTERVAL @decalage DAY),
(1018, 'Maison', 'Raphaël', 'David', '10 rue du Moulin', NULL, '69007', 'Lyon', '06 52 66 82 56', 1, '2026-06-02 20:43:55' + INTERVAL @decalage DAY),
(1019, 'Maison', 'Louise', 'Bertrand', '56 rue du Moulin', NULL, '69100', 'Villeurbanne', '06 86 18 85 50', 1, '2025-10-12 19:13:58' + INTERVAL @decalage DAY),
(1020, 'Maison', 'Arthur', 'Roux', '60 rue Paul Bert', NULL, '75011', 'Paris', '06 30 13 16 43', 1, '2026-06-28 12:51:29' + INTERVAL @decalage DAY),
(1020, 'Travail', 'Arthur', 'Roux', '76 boulevard de la Croix-Rousse', 'Accueil, 2e étage', '69003', 'Lyon', '06 30 13 16 43', 0, '2026-06-28 12:51:29' + INTERVAL @decalage DAY),
(1021, 'Maison', 'Zoé', 'Vincent', '77 boulevard de la Croix-Rousse', NULL, '75018', 'Paris', '06 44 57 54 33', 1, '2026-02-11 08:13:53' + INTERVAL @decalage DAY),
(1022, 'Maison', 'Adam', 'Fournier', '25 rue Victor Hugo', NULL, '59000', 'Lille', '06 74 50 23 48', 1, '2025-09-23 08:34:01' + INTERVAL @decalage DAY),
(1023, 'Maison', 'Alice', 'Morel', '79 allée des Tilleuls', NULL, '13005', 'Marseille', '06 69 35 14 15', 1, '2025-09-06 17:09:23' + INTERVAL @decalage DAY),
(1023, 'Travail', 'Alice', 'Morel', '108 avenue de la Libération', 'Accueil, 2e étage', '69003', 'Lyon', '06 69 35 14 15', 0, '2025-09-06 17:09:23' + INTERVAL @decalage DAY),
(1024, 'Maison', 'Noah', 'Girard', '91 boulevard de la Croix-Rousse', NULL, '33000', 'Bordeaux', '06 15 60 20 15', 1, '2026-08-14 13:12:42' + INTERVAL @decalage DAY),
(1025, 'Maison', 'Lina', 'André', '77 boulevard de la Croix-Rousse', NULL, '44000', 'Nantes', '06 17 14 26 87', 1, '2025-08-16 13:36:04' + INTERVAL @decalage DAY),
(1026, 'Maison', 'Ethan', 'Mercier', '78 allée des Tilleuls', NULL, '31000', 'Toulouse', '06 69 23 59 54', 1, '2025-04-02 14:12:25' + INTERVAL @decalage DAY),
(1026, 'Travail', 'Ethan', 'Mercier', '81 rue Victor Hugo', 'Accueil, 2e étage', '69003', 'Lyon', '06 69 23 59 54', 0, '2025-04-02 14:12:25' + INTERVAL @decalage DAY),
(1027, 'Maison', 'Rose', 'Dupont', '5 rue Pasteur', NULL, '38000', 'Grenoble', '06 27 65 47 93', 1, '2026-03-03 15:33:12' + INTERVAL @decalage DAY),
(1028, 'Maison', 'Paul', 'Lambert', '15 rue du Moulin', NULL, '60200', 'Compiègne', '06 40 34 60 39', 1, '2026-03-04 13:45:35' + INTERVAL @decalage DAY),
(1029, 'Maison', 'Anna', 'Bonnet', '33 chemin des Vignes', NULL, '35000', 'Rennes', '06 87 16 44 99', 1, '2025-10-19 18:47:13' + INTERVAL @decalage DAY),
(1029, 'Travail', 'Anna', 'Bonnet', '34 avenue Jean Jaurès', 'Accueil, 2e étage', '69003', 'Lyon', '06 87 16 44 99', 0, '2025-10-19 18:47:13' + INTERVAL @decalage DAY),
(1030, 'Maison', 'Yanis', 'François', '3 chemin des Vignes', NULL, '67000', 'Strasbourg', '06 18 82 45 55', 1, '2026-05-16 19:26:25' + INTERVAL @decalage DAY),
(1031, 'Maison', 'Juliette', 'Martinez', '4 cours Gambetta', NULL, '69003', 'Lyon', '06 55 66 75 64', 1, '2025-06-06 18:52:29' + INTERVAL @decalage DAY),
(1032, 'Maison', 'Sacha', 'Legrand', '56 rue Pasteur', NULL, '69007', 'Lyon', '06 30 30 53 51', 1, '2026-05-24 15:46:47' + INTERVAL @decalage DAY),
(1032, 'Travail', 'Sacha', 'Legrand', '35 place Bellecour', 'Accueil, 2e étage', '69003', 'Lyon', '06 30 30 53 51', 0, '2026-05-24 15:46:47' + INTERVAL @decalage DAY),
(1033, 'Maison', 'Mila', 'Garnier', '62 chemin des Vignes', NULL, '69100', 'Villeurbanne', '06 43 45 27 17', 1, '2025-09-03 21:32:36' + INTERVAL @decalage DAY),
(1034, 'Maison', 'Tom', 'Faure', '51 rue Victor Hugo', NULL, '75011', 'Paris', '06 62 14 13 22', 1, '2026-03-16 08:36:07' + INTERVAL @decalage DAY),
(1035, 'Maison', 'Nina', 'Rousseau', '54 rue Pasteur', NULL, '75018', 'Paris', '06 17 90 69 81', 1, '2026-01-14 13:59:11' + INTERVAL @decalage DAY),
(1035, 'Travail', 'Nina', 'Rousseau', '105 rue Saint-Michel', 'Accueil, 2e étage', '69003', 'Lyon', '06 17 90 69 81', 0, '2026-01-14 13:59:11' + INTERVAL @decalage DAY),
(1036, 'Maison', 'Malo', 'Blanc', '94 rue des Lilas', NULL, '59000', 'Lille', '06 54 68 94 33', 1, '2025-06-23 12:48:30' + INTERVAL @decalage DAY),
(1037, 'Maison', 'Lou', 'Guerin', '52 boulevard de la Croix-Rousse', NULL, '13005', 'Marseille', '06 32 33 26 35', 1, '2025-07-04 15:27:12' + INTERVAL @decalage DAY),
(1038, 'Maison', 'Mathis', 'Muller', '39 rue de la République', NULL, '33000', 'Bordeaux', '06 16 14 93 12', 1, '2025-11-18 13:26:44' + INTERVAL @decalage DAY),
(1038, 'Travail', 'Mathis', 'Muller', '60 rue Victor Hugo', 'Accueil, 2e étage', '69003', 'Lyon', '06 16 14 93 12', 0, '2025-11-18 13:26:44' + INTERVAL @decalage DAY),
(1039, 'Maison', 'Clara', 'Henry', '1 rue Saint-Michel', NULL, '44000', 'Nantes', '06 36 72 43 57', 1, '2025-08-23 19:48:01' + INTERVAL @decalage DAY),
(1040, 'Maison', 'Rayan', 'Roussel', '91 rue des Lilas', NULL, '31000', 'Toulouse', '06 36 40 16 83', 1, '2025-03-15 21:47:36' + INTERVAL @decalage DAY),
(1041, 'Maison', 'Eva', 'Nicolas', '78 allée des Tilleuls', NULL, '38000', 'Grenoble', '06 63 27 41 51', 1, '2025-07-31 16:39:38' + INTERVAL @decalage DAY),
(1041, 'Travail', 'Eva', 'Nicolas', '12 rue du Moulin', 'Accueil, 2e étage', '69003', 'Lyon', '06 63 27 41 51', 0, '2025-07-31 16:39:38' + INTERVAL @decalage DAY),
(1042, 'Maison', 'Victor', 'Perrin', '81 rue de la République', NULL, '60200', 'Compiègne', '06 78 51 88 39', 1, '2025-09-06 20:40:55' + INTERVAL @decalage DAY),
(1043, 'Maison', 'Margaux', 'Morin', '64 rue Victor Hugo', NULL, '35000', 'Rennes', '06 88 37 38 52', 1, '2025-01-12 21:31:47' + INTERVAL @decalage DAY),
(1044, 'Maison', 'Samuel', 'Mathieu', '92 rue de la République', NULL, '67000', 'Strasbourg', '06 41 39 34 74', 1, '2025-03-06 08:45:02' + INTERVAL @decalage DAY),
(1044, 'Travail', 'Samuel', 'Mathieu', '25 allée des Tilleuls', 'Accueil, 2e étage', '69003', 'Lyon', '06 41 39 34 74', 0, '2025-03-06 08:45:02' + INTERVAL @decalage DAY),
(1045, 'Maison', 'Elsa', 'Clement', '24 chemin des Vignes', NULL, '69003', 'Lyon', '06 68 88 55 89', 1, '2025-06-18 11:11:56' + INTERVAL @decalage DAY),
(1046, 'Maison', 'Axel', 'Gauthier', '90 place Bellecour', NULL, '69007', 'Lyon', '06 39 88 87 53', 1, '2025-01-21 13:00:14' + INTERVAL @decalage DAY),
(1047, 'Maison', 'Agathe', 'Dumont', '80 boulevard de la Croix-Rousse', NULL, '69100', 'Villeurbanne', '06 80 10 57 98', 1, '2025-03-28 08:54:52' + INTERVAL @decalage DAY),
(1047, 'Travail', 'Agathe', 'Dumont', '91 boulevard de la Croix-Rousse', 'Accueil, 2e étage', '69003', 'Lyon', '06 80 10 57 98', 0, '2025-03-28 08:54:52' + INTERVAL @decalage DAY),
(1048, 'Maison', 'Karim', 'Diallo', '98 avenue Jean Jaurès', NULL, '75011', 'Paris', '06 93 56 11 85', 1, '2025-02-11 19:03:48' + INTERVAL @decalage DAY),
(1049, 'Maison', 'Fatou', 'Ndiaye', '79 avenue Jean Jaurès', NULL, '75018', 'Paris', '06 53 81 86 49', 1, '2024-12-31 20:09:18' + INTERVAL @decalage DAY),
(1050, 'Maison', 'Mehdi', 'Benali', '3 rue des Lilas', NULL, '59000', 'Lille', '06 63 51 93 98', 1, '2024-09-01 19:21:15' + INTERVAL @decalage DAY),
(1050, 'Travail', 'Mehdi', 'Benali', '101 rue de la République', 'Accueil, 2e étage', '69003', 'Lyon', '06 63 51 93 98', 0, '2024-09-01 19:21:15' + INTERVAL @decalage DAY);

INSERT INTO commande (id_commande, id_user, date_commande, prenom, nom, adresse, sous_total, remise, frais_livraison, total, points_gagnes, statut, date_annulation, points_utilises, reduction_points, bonus_niveau, id_annulateur, motif_annulation) VALUES
(1001, 1008, '2026-03-22 12:26:38' + INTERVAL @decalage DAY, 'Nathan', 'Richard', '44 rue Paul Bert, 59000 Lille', 13.50, 0.00, 1.99, 15.49, 13, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1002, 1008, '2026-04-13 18:50:04' + INTERVAL @decalage DAY, 'Nathan', 'Richard', '44 rue Paul Bert, 59000 Lille', 44.60, 2.23, 0.00, 42.37, 42, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1003, 1008, '2026-09-05 22:56:29' + INTERVAL @decalage DAY, 'Nathan', 'Richard', '44 rue Paul Bert, 59000 Lille', 40.00, 2.00, 0.00, 38.00, 38, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1004, 1009, '2026-08-17 19:59:53' + INTERVAL @decalage DAY, 'Inès', 'Petit', '35 allée des Tilleuls, 13005 Marseille', 64.80, 6.48, 0.00, 58.32, 58, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1005, 1010, '2026-07-27 20:06:35' + INTERVAL @decalage DAY, 'Théo', 'Durand', '33 avenue Jean Jaurès, 33000 Bordeaux', 62.90, 6.29, 0.00, 56.61, 56, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1006, 1010, '2026-10-21 08:38:24' + INTERVAL @decalage DAY, 'Théo', 'Durand', '33 avenue Jean Jaurès, 33000 Bordeaux', 14.40, 0.00, 1.99, 16.39, 14, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1007, 1011, '2026-01-28 21:14:18' + INTERVAL @decalage DAY, 'Camille', 'Leroy', '63 chemin des Vignes, 44000 Nantes', 9.40, 0.00, 1.99, 11.39, 9, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1008, 1011, '2026-03-23 20:51:27' + INTERVAL @decalage DAY, 'Camille', 'Leroy', '63 chemin des Vignes, 44000 Nantes', 11.70, 0.00, 1.99, 13.69, 11, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1009, 1011, '2026-05-12 17:43:07' + INTERVAL @decalage DAY, 'Camille', 'Leroy', '63 chemin des Vignes, 44000 Nantes', 9.20, 0.00, 1.99, 11.19, 9, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1010, 1011, '2026-06-28 09:47:43' + INTERVAL @decalage DAY, 'Camille', 'Leroy', '63 chemin des Vignes, 44000 Nantes', 16.60, 0.00, 1.99, 18.59, 16, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1011, 1011, '2026-07-10 16:52:14' + INTERVAL @decalage DAY, 'Camille', 'Leroy', '63 chemin des Vignes, 44000 Nantes', 4.30, 0.00, 1.99, 6.29, 4, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1012, 1011, '2026-08-20 12:03:36' + INTERVAL @decalage DAY, 'Camille', 'Leroy', '63 chemin des Vignes, 44000 Nantes', 10.90, 0.00, 1.99, 12.89, 10, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1013, 1012, '2025-09-18 10:40:25' + INTERVAL @decalage DAY, 'Louis', 'Moreau', '26 place Bellecour, 31000 Toulouse', 25.90, 0.00, 0.00, 25.90, 25, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1014, 1012, '2025-10-20 12:58:49' + INTERVAL @decalage DAY, 'Louis', 'Moreau', '26 place Bellecour, 31000 Toulouse', 36.50, 1.83, 0.00, 34.67, 34, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1015, 1012, '2026-04-13 11:43:21' + INTERVAL @decalage DAY, 'Louis', 'Moreau', '26 place Bellecour, 31000 Toulouse', 8.20, 0.00, 1.99, 10.19, 8, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1016, 1013, '2026-06-21 09:08:45' + INTERVAL @decalage DAY, 'Sarah', 'Simon', '49 rue Pasteur, 38000 Grenoble', 17.85, 0.00, 1.99, 19.84, 17, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1017, 1013, '2026-08-14 19:21:28' + INTERVAL @decalage DAY, 'Sarah', 'Simon', '49 rue Pasteur, 38000 Grenoble', 10.30, 0.00, 1.99, 12.29, 10, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1018, 1013, '2026-08-26 20:16:10' + INTERVAL @decalage DAY, 'Sarah', 'Simon', '49 rue Pasteur, 38000 Grenoble', 6.90, 0.00, 1.99, 8.89, 6, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1019, 1014, '2026-08-18 19:52:45' + INTERVAL @decalage DAY, 'Jules', 'Laurent', '93 boulevard de la Croix-Rousse, 60200 Compiègne', 46.80, 2.34, 0.00, 44.46, 44, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1020, 1015, '2026-01-23 10:27:17' + INTERVAL @decalage DAY, 'Emma', 'Lefebvre', '54 rue du Moulin, 35000 Rennes', 93.40, 9.34, 0.00, 84.06, 84, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1021, 1016, '2026-09-30 18:40:55' + INTERVAL @decalage DAY, 'Gabriel', 'Michel', '3 rue Victor Hugo, 67000 Strasbourg', 7.50, 0.00, 1.99, 9.49, 7, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1022, 1016, '2026-10-16 17:17:35' + INTERVAL @decalage DAY, 'Gabriel', 'Michel', '3 rue Victor Hugo, 67000 Strasbourg', 7.20, 0.00, 1.99, 9.19, 7, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1023, 1016, '2026-10-20 10:42:39' + INTERVAL @decalage DAY, 'Gabriel', 'Michel', '3 rue Victor Hugo, 67000 Strasbourg', 34.10, 1.71, 0.00, 32.39, 32, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1024, 1017, '2026-06-09 19:24:10' + INTERVAL @decalage DAY, 'Jade', 'Garcia', '61 allée des Tilleuls, 69003 Lyon', 10.80, 0.00, 1.99, 12.79, 10, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1025, 1017, '2026-06-18 08:50:55' + INTERVAL @decalage DAY, 'Jade', 'Garcia', '61 allée des Tilleuls, 69003 Lyon', 18.10, 0.00, 1.99, 20.09, 18, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1026, 1017, '2026-08-14 15:22:21' + INTERVAL @decalage DAY, 'Jade', 'Garcia', '61 allée des Tilleuls, 69003 Lyon', 7.10, 0.00, 1.99, 9.09, 7, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1027, 1017, '2026-08-14 18:13:55' + INTERVAL @decalage DAY, 'Jade', 'Garcia', '61 allée des Tilleuls, 69003 Lyon', 9.20, 0.00, 1.99, 11.19, 9, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1028, 1017, '2026-08-22 13:22:59' + INTERVAL @decalage DAY, 'Jade', 'Garcia', '61 allée des Tilleuls, 69003 Lyon', 14.40, 0.00, 1.99, 16.39, 14, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1029, 1017, '2026-08-29 08:27:39' + INTERVAL @decalage DAY, 'Jade', 'Garcia', '61 allée des Tilleuls, 69003 Lyon', 19.50, 0.00, 1.99, 21.49, 19, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1030, 1018, '2026-06-19 15:05:24' + INTERVAL @decalage DAY, 'Raphaël', 'David', '10 rue du Moulin, 69007 Lyon', 44.10, 2.21, 0.00, 41.89, 41, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1031, 1018, '2026-06-25 10:18:18' + INTERVAL @decalage DAY, 'Raphaël', 'David', '10 rue du Moulin, 69007 Lyon', 22.50, 0.00, 0.00, 22.50, 22, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1032, 1018, '2026-09-21 18:14:40' + INTERVAL @decalage DAY, 'Raphaël', 'David', '10 rue du Moulin, 69007 Lyon', 49.28, 2.46, 0.00, 46.82, 46, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1033, 1019, '2025-11-01 17:15:39' + INTERVAL @decalage DAY, 'Louise', 'Bertrand', '56 rue du Moulin, 69100 Villeurbanne', 30.40, 1.52, 0.00, 28.88, 28, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1034, 1019, '2025-12-03 15:53:02' + INTERVAL @decalage DAY, 'Louise', 'Bertrand', '56 rue du Moulin, 69100 Villeurbanne', 9.90, 0.00, 1.99, 11.89, 9, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1035, 1019, '2026-06-04 21:59:35' + INTERVAL @decalage DAY, 'Louise', 'Bertrand', '56 rue du Moulin, 69100 Villeurbanne', 11.20, 0.00, 1.99, 13.19, 11, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1036, 1019, '2026-06-08 20:40:58' + INTERVAL @decalage DAY, 'Louise', 'Bertrand', '56 rue du Moulin, 69100 Villeurbanne', 17.85, 0.00, 1.99, 19.84, 17, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1037, 1020, '2026-07-26 09:34:02' + INTERVAL @decalage DAY, 'Arthur', 'Roux', '60 rue Paul Bert, 75011 Paris', 13.10, 0.00, 1.99, 15.09, 13, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1038, 1020, '2026-08-02 20:59:53' + INTERVAL @decalage DAY, 'Arthur', 'Roux', '60 rue Paul Bert, 75011 Paris', 28.90, 0.00, 0.00, 28.90, 28, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1039, 1020, '2026-08-22 15:41:56' + INTERVAL @decalage DAY, 'Arthur', 'Roux', '60 rue Paul Bert, 75011 Paris', 13.15, 0.00, 1.99, 15.14, 13, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1040, 1020, '2026-08-22 17:35:55' + INTERVAL @decalage DAY, 'Arthur', 'Roux', '60 rue Paul Bert, 75011 Paris', 10.50, 0.00, 1.99, 12.49, 10, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1041, 1020, '2026-09-06 17:30:53' + INTERVAL @decalage DAY, 'Arthur', 'Roux', '60 rue Paul Bert, 75011 Paris', 3.50, 0.00, 1.99, 5.49, 3, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1042, 1020, '2026-10-08 09:59:15' + INTERVAL @decalage DAY, 'Arthur', 'Roux', '60 rue Paul Bert, 75011 Paris', 2.10, 0.00, 1.99, 4.09, 2, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1043, 1020, '2026-10-20 16:45:34' + INTERVAL @decalage DAY, 'Arthur', 'Roux', '60 rue Paul Bert, 75011 Paris', 15.40, 0.00, 1.99, 17.39, 15, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1044, 1021, '2026-03-29 19:24:55' + INTERVAL @decalage DAY, 'Zoé', 'Vincent', '77 boulevard de la Croix-Rousse, 75018 Paris', 19.80, 0.00, 1.99, 21.79, 19, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1045, 1021, '2026-04-24 15:59:22' + INTERVAL @decalage DAY, 'Zoé', 'Vincent', '77 boulevard de la Croix-Rousse, 75018 Paris', 53.90, 5.39, 0.00, 48.51, 48, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1046, 1021, '2026-07-16 11:15:02' + INTERVAL @decalage DAY, 'Zoé', 'Vincent', '77 boulevard de la Croix-Rousse, 75018 Paris', 11.20, 0.00, 1.99, 13.19, 11, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1047, 1021, '2026-07-31 13:44:01' + INTERVAL @decalage DAY, 'Zoé', 'Vincent', '77 boulevard de la Croix-Rousse, 75018 Paris', 22.60, 0.00, 0.00, 17.60, 17, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1048, 1022, '2026-01-20 18:47:53' + INTERVAL @decalage DAY, 'Adam', 'Fournier', '25 rue Victor Hugo, 59000 Lille', 3.40, 0.00, 1.99, 5.39, 3, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1049, 1022, '2026-02-27 19:17:01' + INTERVAL @decalage DAY, 'Adam', 'Fournier', '25 rue Victor Hugo, 59000 Lille', 59.40, 5.94, 0.00, 53.46, 53, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1050, 1022, '2026-04-03 08:43:07' + INTERVAL @decalage DAY, 'Adam', 'Fournier', '25 rue Victor Hugo, 59000 Lille', 9.95, 0.00, 1.99, 11.94, 9, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1051, 1022, '2026-08-26 15:26:41' + INTERVAL @decalage DAY, 'Adam', 'Fournier', '25 rue Victor Hugo, 59000 Lille', 9.95, 0.00, 1.99, 11.94, 9, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1052, 1023, '2026-02-17 21:53:58' + INTERVAL @decalage DAY, 'Alice', 'Morel', '79 allée des Tilleuls, 13005 Marseille', 23.20, 0.00, 0.00, 23.20, 23, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1053, 1023, '2026-04-30 15:26:08' + INTERVAL @decalage DAY, 'Alice', 'Morel', '79 allée des Tilleuls, 13005 Marseille', 4.60, 0.00, 1.99, 6.59, 4, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1054, 1023, '2026-10-20 17:27:09' + INTERVAL @decalage DAY, 'Alice', 'Morel', '79 allée des Tilleuls, 13005 Marseille', 29.00, 0.00, 0.00, 29.00, 29, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1055, 1024, '2026-08-28 10:37:42' + INTERVAL @decalage DAY, 'Noah', 'Girard', '91 boulevard de la Croix-Rousse, 33000 Bordeaux', 3.10, 0.00, 1.99, 5.09, 3, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1056, 1024, '2026-09-11 15:39:53' + INTERVAL @decalage DAY, 'Noah', 'Girard', '91 boulevard de la Croix-Rousse, 33000 Bordeaux', 7.50, 0.00, 1.99, 9.49, 7, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1057, 1024, '2026-09-15 22:17:55' + INTERVAL @decalage DAY, 'Noah', 'Girard', '91 boulevard de la Croix-Rousse, 33000 Bordeaux', 23.30, 0.00, 0.00, 23.30, 23, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1058, 1024, '2026-09-21 22:13:10' + INTERVAL @decalage DAY, 'Noah', 'Girard', '91 boulevard de la Croix-Rousse, 33000 Bordeaux', 23.40, 0.00, 0.00, 23.40, 23, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1059, 1024, '2026-10-03 14:26:15' + INTERVAL @decalage DAY, 'Noah', 'Girard', '91 boulevard de la Croix-Rousse, 33000 Bordeaux', 13.30, 0.00, 1.99, 15.29, 13, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1060, 1025, '2025-10-18 09:33:07' + INTERVAL @decalage DAY, 'Lina', 'André', '77 boulevard de la Croix-Rousse, 44000 Nantes', 28.09, 0.00, 0.00, 28.09, 28, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1061, 1025, '2025-11-14 14:30:06' + INTERVAL @decalage DAY, 'Lina', 'André', '77 boulevard de la Croix-Rousse, 44000 Nantes', 15.80, 0.00, 1.99, 17.79, 15, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1062, 1025, '2025-12-15 08:41:45' + INTERVAL @decalage DAY, 'Lina', 'André', '77 boulevard de la Croix-Rousse, 44000 Nantes', 24.90, 0.00, 0.00, 24.90, 24, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1063, 1025, '2026-03-22 09:43:01' + INTERVAL @decalage DAY, 'Lina', 'André', '77 boulevard de la Croix-Rousse, 44000 Nantes', 11.40, 0.00, 1.99, 13.39, 11, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1064, 1025, '2026-03-24 12:02:01' + INTERVAL @decalage DAY, 'Lina', 'André', '77 boulevard de la Croix-Rousse, 44000 Nantes', 88.90, 8.89, 0.00, 75.01, 75, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1065, 1025, '2026-04-06 10:37:24' + INTERVAL @decalage DAY, 'Lina', 'André', '77 boulevard de la Croix-Rousse, 44000 Nantes', 47.80, 2.39, 0.00, 45.41, 45, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1066, 1025, '2026-04-14 08:31:53' + INTERVAL @decalage DAY, 'Lina', 'André', '77 boulevard de la Croix-Rousse, 44000 Nantes', 2.20, 0.00, 1.99, 4.19, 2, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1067, 1025, '2026-07-17 12:50:16' + INTERVAL @decalage DAY, 'Lina', 'André', '77 boulevard de la Croix-Rousse, 44000 Nantes', 6.40, 0.00, 1.99, 8.39, 6, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1068, 1025, '2026-07-31 15:50:18' + INTERVAL @decalage DAY, 'Lina', 'André', '77 boulevard de la Croix-Rousse, 44000 Nantes', 8.80, 0.00, 1.99, 10.79, 8, 'annulee', '2026-08-01 15:50:18' + INTERVAL @decalage DAY, 0, 0.00, 0, 1, 'Livre introuvable lors de la préparation du colis'),
(1069, 1025, '2026-09-12 17:41:13' + INTERVAL @decalage DAY, 'Lina', 'André', '77 boulevard de la Croix-Rousse, 44000 Nantes', 13.20, 0.00, 1.99, 15.19, 13, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1070, 1026, '2025-05-25 22:33:16' + INTERVAL @decalage DAY, 'Ethan', 'Mercier', '78 allée des Tilleuls, 31000 Toulouse', 70.50, 7.05, 0.00, 63.45, 63, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1071, 1026, '2025-07-29 20:31:10' + INTERVAL @decalage DAY, 'Ethan', 'Mercier', '78 allée des Tilleuls, 31000 Toulouse', 9.00, 0.00, 1.99, 10.99, 9, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1072, 1026, '2025-08-24 16:50:56' + INTERVAL @decalage DAY, 'Ethan', 'Mercier', '78 allée des Tilleuls, 31000 Toulouse', 43.80, 2.19, 0.00, 36.61, 36, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1073, 1026, '2026-02-18 10:20:18' + INTERVAL @decalage DAY, 'Ethan', 'Mercier', '78 allée des Tilleuls, 31000 Toulouse', 36.70, 1.84, 0.00, 34.86, 34, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1074, 1026, '2026-04-10 20:41:02' + INTERVAL @decalage DAY, 'Ethan', 'Mercier', '78 allée des Tilleuls, 31000 Toulouse', 44.60, 2.23, 0.00, 42.37, 42, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1075, 1026, '2026-07-15 10:25:44' + INTERVAL @decalage DAY, 'Ethan', 'Mercier', '78 allée des Tilleuls, 31000 Toulouse', 38.49, 1.92, 0.00, 36.57, 36, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1076, 1027, '2026-03-07 22:37:11' + INTERVAL @decalage DAY, 'Rose', 'Dupont', '5 rue Pasteur, 38000 Grenoble', 55.50, 5.55, 0.00, 49.95, 49, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1077, 1027, '2026-03-15 19:58:14' + INTERVAL @decalage DAY, 'Rose', 'Dupont', '5 rue Pasteur, 38000 Grenoble', 16.90, 0.00, 1.99, 18.89, 16, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1078, 1027, '2026-04-03 15:36:27' + INTERVAL @decalage DAY, 'Rose', 'Dupont', '5 rue Pasteur, 38000 Grenoble', 16.99, 0.00, 1.99, 18.98, 16, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1079, 1027, '2026-04-21 09:32:43' + INTERVAL @decalage DAY, 'Rose', 'Dupont', '5 rue Pasteur, 38000 Grenoble', 10.60, 0.00, 1.99, 12.59, 10, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1080, 1027, '2026-04-27 22:32:55' + INTERVAL @decalage DAY, 'Rose', 'Dupont', '5 rue Pasteur, 38000 Grenoble', 28.90, 0.00, 0.00, 23.90, 23, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1081, 1027, '2026-07-07 15:15:36' + INTERVAL @decalage DAY, 'Rose', 'Dupont', '5 rue Pasteur, 38000 Grenoble', 36.10, 1.81, 0.00, 34.29, 34, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1082, 1027, '2026-08-12 10:27:39' + INTERVAL @decalage DAY, 'Rose', 'Dupont', '5 rue Pasteur, 38000 Grenoble', 4.50, 0.00, 1.99, 6.49, 4, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1083, 1027, '2026-08-21 13:30:10' + INTERVAL @decalage DAY, 'Rose', 'Dupont', '5 rue Pasteur, 38000 Grenoble', 9.10, 0.00, 1.99, 11.09, 9, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1084, 1027, '2026-08-30 20:37:46' + INTERVAL @decalage DAY, 'Rose', 'Dupont', '5 rue Pasteur, 38000 Grenoble', 21.00, 0.00, 0.00, 21.00, 21, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1085, 1027, '2026-10-04 08:23:12' + INTERVAL @decalage DAY, 'Rose', 'Dupont', '5 rue Pasteur, 38000 Grenoble', 21.90, 0.00, 0.00, 21.90, 21, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1086, 1027, '2026-10-12 13:56:30' + INTERVAL @decalage DAY, 'Rose', 'Dupont', '5 rue Pasteur, 38000 Grenoble', 43.00, 2.15, 0.00, 40.85, 40, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1087, 1027, '2026-10-23 15:08:46' + INTERVAL @decalage DAY, 'Rose', 'Dupont', '5 rue Pasteur, 38000 Grenoble', 81.40, 8.14, 0.00, 73.26, 73, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1088, 1028, '2026-03-13 09:07:41' + INTERVAL @decalage DAY, 'Paul', 'Lambert', '15 rue du Moulin, 60200 Compiègne', 27.05, 0.00, 0.00, 27.05, 27, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1089, 1028, '2026-04-03 20:05:41' + INTERVAL @decalage DAY, 'Paul', 'Lambert', '15 rue du Moulin, 60200 Compiègne', 25.00, 0.00, 0.00, 25.00, 25, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1090, 1028, '2026-04-03 22:50:15' + INTERVAL @decalage DAY, 'Paul', 'Lambert', '15 rue du Moulin, 60200 Compiègne', 7.80, 0.00, 1.99, 9.79, 7, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1091, 1028, '2026-04-19 22:10:08' + INTERVAL @decalage DAY, 'Paul', 'Lambert', '15 rue du Moulin, 60200 Compiègne', 2.70, 0.00, 1.99, 4.69, 2, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1092, 1028, '2026-05-09 13:02:22' + INTERVAL @decalage DAY, 'Paul', 'Lambert', '15 rue du Moulin, 60200 Compiègne', 5.20, 0.00, 1.99, 7.19, 5, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1093, 1028, '2026-05-31 08:06:06' + INTERVAL @decalage DAY, 'Paul', 'Lambert', '15 rue du Moulin, 60200 Compiègne', 28.80, 0.00, 0.00, 28.80, 28, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1094, 1028, '2026-06-01 18:29:00' + INTERVAL @decalage DAY, 'Paul', 'Lambert', '15 rue du Moulin, 60200 Compiègne', 5.20, 0.00, 1.99, 7.19, 5, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1095, 1028, '2026-06-13 11:52:13' + INTERVAL @decalage DAY, 'Paul', 'Lambert', '15 rue du Moulin, 60200 Compiègne', 4.50, 0.00, 1.99, 6.49, 4, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1096, 1028, '2026-06-16 09:33:47' + INTERVAL @decalage DAY, 'Paul', 'Lambert', '15 rue du Moulin, 60200 Compiègne', 6.90, 0.00, 1.99, 8.89, 6, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1097, 1028, '2026-06-22 12:16:41' + INTERVAL @decalage DAY, 'Paul', 'Lambert', '15 rue du Moulin, 60200 Compiègne', 15.85, 0.00, 1.99, 17.84, 15, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1098, 1028, '2026-07-18 09:52:21' + INTERVAL @decalage DAY, 'Paul', 'Lambert', '15 rue du Moulin, 60200 Compiègne', 16.70, 0.00, 1.99, 18.69, 16, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1099, 1028, '2026-08-16 22:00:39' + INTERVAL @decalage DAY, 'Paul', 'Lambert', '15 rue du Moulin, 60200 Compiègne', 10.30, 0.00, 1.99, 12.29, 10, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1100, 1028, '2026-09-01 22:27:57' + INTERVAL @decalage DAY, 'Paul', 'Lambert', '15 rue du Moulin, 60200 Compiègne', 43.00, 2.15, 0.00, 30.35, 30, 'validee', NULL, 200, 10.50, 0, NULL, NULL),
(1101, 1028, '2026-10-09 22:34:29' + INTERVAL @decalage DAY, 'Paul', 'Lambert', '15 rue du Moulin, 60200 Compiègne', 50.25, 5.03, 0.00, 45.22, 45, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1102, 1029, '2025-11-05 20:47:52' + INTERVAL @decalage DAY, 'Anna', 'Bonnet', '33 chemin des Vignes, 35000 Rennes', 2.30, 0.00, 1.99, 4.29, 2, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1103, 1029, '2025-11-11 17:16:45' + INTERVAL @decalage DAY, 'Anna', 'Bonnet', '33 chemin des Vignes, 35000 Rennes', 9.70, 0.00, 1.99, 11.69, 9, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1104, 1029, '2025-11-17 13:58:37' + INTERVAL @decalage DAY, 'Anna', 'Bonnet', '33 chemin des Vignes, 35000 Rennes', 18.60, 0.00, 1.99, 20.59, 18, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1105, 1029, '2025-12-10 12:29:58' + INTERVAL @decalage DAY, 'Anna', 'Bonnet', '33 chemin des Vignes, 35000 Rennes', 5.10, 0.00, 1.99, 7.09, 5, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1106, 1029, '2025-12-21 21:49:58' + INTERVAL @decalage DAY, 'Anna', 'Bonnet', '33 chemin des Vignes, 35000 Rennes', 5.60, 0.00, 1.99, 7.59, 5, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1107, 1029, '2026-02-01 21:49:39' + INTERVAL @decalage DAY, 'Anna', 'Bonnet', '33 chemin des Vignes, 35000 Rennes', 6.70, 0.00, 1.99, 8.69, 6, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1108, 1029, '2026-02-28 11:53:38' + INTERVAL @decalage DAY, 'Anna', 'Bonnet', '33 chemin des Vignes, 35000 Rennes', 11.50, 0.00, 1.99, 13.49, 11, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1109, 1029, '2026-03-15 21:41:25' + INTERVAL @decalage DAY, 'Anna', 'Bonnet', '33 chemin des Vignes, 35000 Rennes', 18.00, 0.00, 1.99, 14.99, 13, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1110, 1029, '2026-03-21 20:47:01' + INTERVAL @decalage DAY, 'Anna', 'Bonnet', '33 chemin des Vignes, 35000 Rennes', 15.10, 0.00, 1.99, 17.09, 15, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1111, 1029, '2026-04-15 13:35:06' + INTERVAL @decalage DAY, 'Anna', 'Bonnet', '33 chemin des Vignes, 35000 Rennes', 3.80, 0.00, 1.99, 5.79, 3, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1112, 1029, '2026-06-12 20:53:26' + INTERVAL @decalage DAY, 'Anna', 'Bonnet', '33 chemin des Vignes, 35000 Rennes', 21.50, 0.00, 0.00, 21.50, 21, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1113, 1029, '2026-06-15 11:06:51' + INTERVAL @decalage DAY, 'Anna', 'Bonnet', '33 chemin des Vignes, 35000 Rennes', 26.55, 0.00, 0.00, 26.55, 26, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1114, 1029, '2026-06-30 13:49:39' + INTERVAL @decalage DAY, 'Anna', 'Bonnet', '33 chemin des Vignes, 35000 Rennes', 16.00, 0.00, 1.99, 17.99, 16, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1115, 1029, '2026-07-06 08:14:53' + INTERVAL @decalage DAY, 'Anna', 'Bonnet', '33 chemin des Vignes, 35000 Rennes', 6.30, 0.00, 1.99, 8.29, 6, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1116, 1029, '2026-09-01 09:08:00' + INTERVAL @decalage DAY, 'Anna', 'Bonnet', '33 chemin des Vignes, 35000 Rennes', 11.60, 0.00, 1.99, 13.59, 11, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1117, 1029, '2026-09-04 19:12:29' + INTERVAL @decalage DAY, 'Anna', 'Bonnet', '33 chemin des Vignes, 35000 Rennes', 22.80, 0.00, 0.00, 22.80, 22, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1118, 1029, '2026-09-15 10:07:20' + INTERVAL @decalage DAY, 'Anna', 'Bonnet', '33 chemin des Vignes, 35000 Rennes', 10.90, 0.00, 1.99, 12.89, 10, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1119, 1029, '2026-09-17 18:53:55' + INTERVAL @decalage DAY, 'Anna', 'Bonnet', '33 chemin des Vignes, 35000 Rennes', 7.50, 0.00, 1.99, 9.49, 7, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1120, 1029, '2026-09-22 09:50:34' + INTERVAL @decalage DAY, 'Anna', 'Bonnet', '33 chemin des Vignes, 35000 Rennes', 12.90, 0.00, 1.99, 14.89, 12, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1121, 1030, '2026-06-05 18:22:09' + INTERVAL @decalage DAY, 'Yanis', 'François', '3 chemin des Vignes, 67000 Strasbourg', 11.00, 0.00, 1.99, 12.99, 11, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1122, 1030, '2026-06-25 08:58:38' + INTERVAL @decalage DAY, 'Yanis', 'François', '3 chemin des Vignes, 67000 Strasbourg', 38.65, 1.93, 0.00, 36.72, 36, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1123, 1030, '2026-08-02 10:55:22' + INTERVAL @decalage DAY, 'Yanis', 'François', '3 chemin des Vignes, 67000 Strasbourg', 27.00, 0.00, 0.00, 27.00, 27, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1124, 1030, '2026-08-16 17:54:56' + INTERVAL @decalage DAY, 'Yanis', 'François', '3 chemin des Vignes, 67000 Strasbourg', 7.20, 0.00, 1.99, 9.19, 7, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1125, 1030, '2026-08-17 09:39:05' + INTERVAL @decalage DAY, 'Yanis', 'François', '3 chemin des Vignes, 67000 Strasbourg', 15.80, 0.00, 1.99, 12.79, 10, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1126, 1030, '2026-08-18 08:27:10' + INTERVAL @decalage DAY, 'Yanis', 'François', '3 chemin des Vignes, 67000 Strasbourg', 29.90, 0.00, 0.00, 29.90, 29, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1127, 1030, '2026-08-24 18:30:10' + INTERVAL @decalage DAY, 'Yanis', 'François', '3 chemin des Vignes, 67000 Strasbourg', 17.40, 0.00, 1.99, 19.39, 17, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1128, 1030, '2026-08-29 17:54:26' + INTERVAL @decalage DAY, 'Yanis', 'François', '3 chemin des Vignes, 67000 Strasbourg', 37.69, 1.88, 0.00, 35.81, 35, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1129, 1030, '2026-09-07 08:04:28' + INTERVAL @decalage DAY, 'Yanis', 'François', '3 chemin des Vignes, 67000 Strasbourg', 10.00, 0.00, 1.99, 11.99, 10, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1130, 1030, '2026-10-20 19:50:37' + INTERVAL @decalage DAY, 'Yanis', 'François', '3 chemin des Vignes, 67000 Strasbourg', 34.30, 1.72, 0.00, 32.58, 32, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1131, 1031, '2025-07-23 09:30:29' + INTERVAL @decalage DAY, 'Juliette', 'Martinez', '4 cours Gambetta, 69003 Lyon', 13.70, 0.00, 1.99, 15.69, 13, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1132, 1031, '2025-08-03 08:45:45' + INTERVAL @decalage DAY, 'Juliette', 'Martinez', '4 cours Gambetta, 69003 Lyon', 31.90, 1.60, 0.00, 30.30, 30, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1133, 1031, '2025-08-24 15:52:17' + INTERVAL @decalage DAY, 'Juliette', 'Martinez', '4 cours Gambetta, 69003 Lyon', 22.80, 0.00, 0.00, 22.80, 22, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1134, 1031, '2025-10-11 12:17:55' + INTERVAL @decalage DAY, 'Juliette', 'Martinez', '4 cours Gambetta, 69003 Lyon', 13.70, 0.00, 1.99, 15.69, 13, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1135, 1031, '2025-11-06 22:01:46' + INTERVAL @decalage DAY, 'Juliette', 'Martinez', '4 cours Gambetta, 69003 Lyon', 4.50, 0.00, 1.99, 6.49, 4, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1136, 1031, '2025-11-23 11:39:37' + INTERVAL @decalage DAY, 'Juliette', 'Martinez', '4 cours Gambetta, 69003 Lyon', 37.30, 1.87, 0.00, 35.43, 35, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1137, 1031, '2026-03-15 12:13:33' + INTERVAL @decalage DAY, 'Juliette', 'Martinez', '4 cours Gambetta, 69003 Lyon', 21.70, 0.00, 0.00, 21.70, 21, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1138, 1031, '2026-06-24 18:35:16' + INTERVAL @decalage DAY, 'Juliette', 'Martinez', '4 cours Gambetta, 69003 Lyon', 17.90, 0.00, 1.99, 19.89, 17, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1139, 1031, '2026-06-25 17:29:50' + INTERVAL @decalage DAY, 'Juliette', 'Martinez', '4 cours Gambetta, 69003 Lyon', 7.00, 0.00, 1.99, 8.99, 7, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1140, 1031, '2026-07-23 18:39:38' + INTERVAL @decalage DAY, 'Juliette', 'Martinez', '4 cours Gambetta, 69003 Lyon', 5.90, 0.00, 1.99, 7.89, 5, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1141, 1031, '2026-09-04 17:49:00' + INTERVAL @decalage DAY, 'Juliette', 'Martinez', '4 cours Gambetta, 69003 Lyon', 21.00, 0.00, 0.00, 21.00, 21, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1142, 1031, '2026-09-28 10:32:17' + INTERVAL @decalage DAY, 'Juliette', 'Martinez', '4 cours Gambetta, 69003 Lyon', 33.60, 1.68, 0.00, 31.92, 31, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1143, 1031, '2026-09-29 22:43:06' + INTERVAL @decalage DAY, 'Juliette', 'Martinez', '4 cours Gambetta, 69003 Lyon', 26.70, 0.00, 0.00, 16.20, 16, 'validee', NULL, 200, 10.50, 0, NULL, NULL),
(1144, 1031, '2026-10-16 17:41:45' + INTERVAL @decalage DAY, 'Juliette', 'Martinez', '4 cours Gambetta, 69003 Lyon', 29.90, 0.00, 0.00, 29.90, 29, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1145, 1032, '2026-05-26 16:45:00' + INTERVAL @decalage DAY, 'Sacha', 'Legrand', '56 rue Pasteur, 69007 Lyon', 5.80, 0.00, 1.99, 7.79, 5, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1146, 1032, '2026-05-27 16:12:44' + INTERVAL @decalage DAY, 'Sacha', 'Legrand', '56 rue Pasteur, 69007 Lyon', 39.80, 1.99, 0.00, 37.81, 37, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1147, 1032, '2026-06-09 09:25:19' + INTERVAL @decalage DAY, 'Sacha', 'Legrand', '56 rue Pasteur, 69007 Lyon', 52.10, 5.21, 0.00, 46.89, 46, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1148, 1032, '2026-06-13 12:47:14' + INTERVAL @decalage DAY, 'Sacha', 'Legrand', '56 rue Pasteur, 69007 Lyon', 12.80, 0.00, 1.99, 14.79, 12, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1149, 1032, '2026-07-16 21:46:56' + INTERVAL @decalage DAY, 'Sacha', 'Legrand', '56 rue Pasteur, 69007 Lyon', 8.00, 0.00, 1.99, 9.99, 8, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1150, 1032, '2026-09-05 17:52:27' + INTERVAL @decalage DAY, 'Sacha', 'Legrand', '56 rue Pasteur, 69007 Lyon', 22.90, 0.00, 0.00, 22.90, 22, 'validee', NULL, 0, 0.00, 0, NULL, NULL);

INSERT INTO commande (id_commande, id_user, date_commande, prenom, nom, adresse, sous_total, remise, frais_livraison, total, points_gagnes, statut, date_annulation, points_utilises, reduction_points, bonus_niveau, id_annulateur, motif_annulation) VALUES
(1151, 1032, '2026-09-17 13:00:34' + INTERVAL @decalage DAY, 'Sacha', 'Legrand', '56 rue Pasteur, 69007 Lyon', 19.00, 0.00, 1.99, 15.99, 14, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1152, 1032, '2026-09-20 13:39:32' + INTERVAL @decalage DAY, 'Sacha', 'Legrand', '56 rue Pasteur, 69007 Lyon', 30.30, 1.52, 0.00, 28.78, 28, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1153, 1032, '2026-09-22 20:01:17' + INTERVAL @decalage DAY, 'Sacha', 'Legrand', '56 rue Pasteur, 69007 Lyon', 4.90, 0.00, 1.99, 6.89, 4, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1154, 1032, '2026-09-25 17:36:19' + INTERVAL @decalage DAY, 'Sacha', 'Legrand', '56 rue Pasteur, 69007 Lyon', 73.20, 7.32, 0.00, 60.88, 60, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1155, 1032, '2026-10-13 10:39:42' + INTERVAL @decalage DAY, 'Sacha', 'Legrand', '56 rue Pasteur, 69007 Lyon', 8.95, 0.00, 1.99, 10.94, 8, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1156, 1033, '2025-10-17 15:04:41' + INTERVAL @decalage DAY, 'Mila', 'Garnier', '62 chemin des Vignes, 69100 Villeurbanne', 10.60, 0.00, 1.99, 12.59, 10, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1157, 1033, '2025-11-06 21:08:41' + INTERVAL @decalage DAY, 'Mila', 'Garnier', '62 chemin des Vignes, 69100 Villeurbanne', 7.50, 0.00, 1.99, 9.49, 7, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1158, 1033, '2025-12-02 11:49:46' + INTERVAL @decalage DAY, 'Mila', 'Garnier', '62 chemin des Vignes, 69100 Villeurbanne', 31.20, 1.56, 0.00, 29.64, 29, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1159, 1033, '2025-12-23 20:17:37' + INTERVAL @decalage DAY, 'Mila', 'Garnier', '62 chemin des Vignes, 69100 Villeurbanne', 27.75, 0.00, 0.00, 27.75, 27, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1160, 1033, '2026-01-20 18:34:31' + INTERVAL @decalage DAY, 'Mila', 'Garnier', '62 chemin des Vignes, 69100 Villeurbanne', 16.10, 0.00, 1.99, 18.09, 16, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1161, 1033, '2026-01-29 21:45:23' + INTERVAL @decalage DAY, 'Mila', 'Garnier', '62 chemin des Vignes, 69100 Villeurbanne', 15.40, 0.00, 1.99, 17.39, 15, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1162, 1033, '2026-02-28 10:29:00' + INTERVAL @decalage DAY, 'Mila', 'Garnier', '62 chemin des Vignes, 69100 Villeurbanne', 15.30, 0.00, 1.99, 17.29, 15, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1163, 1033, '2026-03-14 08:49:00' + INTERVAL @decalage DAY, 'Mila', 'Garnier', '62 chemin des Vignes, 69100 Villeurbanne', 14.40, 0.00, 1.99, 16.39, 14, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1164, 1033, '2026-05-04 15:19:56' + INTERVAL @decalage DAY, 'Mila', 'Garnier', '62 chemin des Vignes, 69100 Villeurbanne', 31.15, 1.56, 0.00, 24.59, 24, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1165, 1033, '2026-05-22 10:44:39' + INTERVAL @decalage DAY, 'Mila', 'Garnier', '62 chemin des Vignes, 69100 Villeurbanne', 2.10, 0.00, 1.99, 4.09, 2, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1166, 1033, '2026-06-08 21:56:03' + INTERVAL @decalage DAY, 'Mila', 'Garnier', '62 chemin des Vignes, 69100 Villeurbanne', 25.90, 0.00, 0.00, 25.90, 25, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1167, 1033, '2026-06-11 13:44:40' + INTERVAL @decalage DAY, 'Mila', 'Garnier', '62 chemin des Vignes, 69100 Villeurbanne', 11.10, 0.00, 1.99, 13.09, 11, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1168, 1033, '2026-06-13 11:15:49' + INTERVAL @decalage DAY, 'Mila', 'Garnier', '62 chemin des Vignes, 69100 Villeurbanne', 6.50, 0.00, 1.99, 8.49, 6, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1169, 1033, '2026-06-29 20:05:10' + INTERVAL @decalage DAY, 'Mila', 'Garnier', '62 chemin des Vignes, 69100 Villeurbanne', 21.70, 0.00, 0.00, 16.70, 16, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1170, 1033, '2026-07-10 10:06:49' + INTERVAL @decalage DAY, 'Mila', 'Garnier', '62 chemin des Vignes, 69100 Villeurbanne', 6.50, 0.00, 1.99, 8.49, 6, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1171, 1033, '2026-08-02 13:23:50' + INTERVAL @decalage DAY, 'Mila', 'Garnier', '62 chemin des Vignes, 69100 Villeurbanne', 22.70, 0.00, 0.00, 22.70, 22, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1172, 1033, '2026-08-25 20:35:13' + INTERVAL @decalage DAY, 'Mila', 'Garnier', '62 chemin des Vignes, 69100 Villeurbanne', 2.30, 0.00, 1.99, 4.29, 2, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1173, 1033, '2026-08-26 21:38:20' + INTERVAL @decalage DAY, 'Mila', 'Garnier', '62 chemin des Vignes, 69100 Villeurbanne', 10.90, 0.00, 1.99, 12.89, 10, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1174, 1033, '2026-09-22 08:08:27' + INTERVAL @decalage DAY, 'Mila', 'Garnier', '62 chemin des Vignes, 69100 Villeurbanne', 19.80, 0.00, 1.99, 21.79, 19, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1175, 1033, '2026-09-26 12:29:19' + INTERVAL @decalage DAY, 'Mila', 'Garnier', '62 chemin des Vignes, 69100 Villeurbanne', 33.70, 1.69, 0.00, 27.01, 27, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1176, 1034, '2026-03-30 11:35:54' + INTERVAL @decalage DAY, 'Tom', 'Faure', '51 rue Victor Hugo, 75011 Paris', 36.30, 1.82, 0.00, 34.48, 34, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1177, 1034, '2026-03-31 10:41:57' + INTERVAL @decalage DAY, 'Tom', 'Faure', '51 rue Victor Hugo, 75011 Paris', 10.70, 0.00, 1.99, 12.69, 10, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1178, 1034, '2026-04-10 09:36:17' + INTERVAL @decalage DAY, 'Tom', 'Faure', '51 rue Victor Hugo, 75011 Paris', 30.60, 1.53, 0.00, 29.07, 29, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1179, 1034, '2026-04-13 16:32:21' + INTERVAL @decalage DAY, 'Tom', 'Faure', '51 rue Victor Hugo, 75011 Paris', 25.90, 0.00, 0.00, 25.90, 25, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1180, 1034, '2026-04-19 13:08:16' + INTERVAL @decalage DAY, 'Tom', 'Faure', '51 rue Victor Hugo, 75011 Paris', 5.60, 0.00, 1.99, 7.59, 5, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1181, 1034, '2026-05-28 19:57:34' + INTERVAL @decalage DAY, 'Tom', 'Faure', '51 rue Victor Hugo, 75011 Paris', 3.00, 0.00, 1.99, 4.99, 3, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1182, 1034, '2026-06-03 20:32:04' + INTERVAL @decalage DAY, 'Tom', 'Faure', '51 rue Victor Hugo, 75011 Paris', 27.90, 0.00, 0.00, 22.90, 22, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1183, 1034, '2026-06-12 16:37:29' + INTERVAL @decalage DAY, 'Tom', 'Faure', '51 rue Victor Hugo, 75011 Paris', 27.20, 0.00, 0.00, 27.20, 27, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1184, 1034, '2026-06-12 21:34:49' + INTERVAL @decalage DAY, 'Tom', 'Faure', '51 rue Victor Hugo, 75011 Paris', 14.40, 0.00, 1.99, 16.39, 14, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1185, 1034, '2026-07-14 22:06:16' + INTERVAL @decalage DAY, 'Tom', 'Faure', '51 rue Victor Hugo, 75011 Paris', 18.00, 0.00, 1.99, 19.99, 18, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1186, 1034, '2026-07-23 13:23:57' + INTERVAL @decalage DAY, 'Tom', 'Faure', '51 rue Victor Hugo, 75011 Paris', 47.70, 2.39, 0.00, 40.31, 40, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1187, 1034, '2026-08-23 08:51:32' + INTERVAL @decalage DAY, 'Tom', 'Faure', '51 rue Victor Hugo, 75011 Paris', 9.50, 0.00, 1.99, 11.49, 9, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1188, 1034, '2026-10-23 10:52:15' + INTERVAL @decalage DAY, 'Tom', 'Faure', '51 rue Victor Hugo, 75011 Paris', 40.60, 2.03, 0.00, 38.57, 38, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1189, 1035, '2026-02-25 15:57:44' + INTERVAL @decalage DAY, 'Nina', 'Rousseau', '54 rue Pasteur, 75018 Paris', 9.80, 0.00, 1.99, 11.79, 9, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1190, 1035, '2026-03-03 12:31:15' + INTERVAL @decalage DAY, 'Nina', 'Rousseau', '54 rue Pasteur, 75018 Paris', 4.30, 0.00, 1.99, 6.29, 4, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1191, 1035, '2026-04-26 10:48:36' + INTERVAL @decalage DAY, 'Nina', 'Rousseau', '54 rue Pasteur, 75018 Paris', 13.20, 0.00, 1.99, 15.19, 13, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1192, 1035, '2026-05-10 08:54:42' + INTERVAL @decalage DAY, 'Nina', 'Rousseau', '54 rue Pasteur, 75018 Paris', 21.50, 0.00, 0.00, 21.50, 21, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1193, 1035, '2026-05-10 11:17:00' + INTERVAL @decalage DAY, 'Nina', 'Rousseau', '54 rue Pasteur, 75018 Paris', 6.90, 0.00, 1.99, 8.89, 6, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1194, 1035, '2026-05-14 09:11:50' + INTERVAL @decalage DAY, 'Nina', 'Rousseau', '54 rue Pasteur, 75018 Paris', 20.10, 0.00, 0.00, 20.10, 20, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1195, 1035, '2026-05-15 11:51:08' + INTERVAL @decalage DAY, 'Nina', 'Rousseau', '54 rue Pasteur, 75018 Paris', 15.80, 0.00, 1.99, 12.79, 10, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1196, 1035, '2026-07-12 14:01:26' + INTERVAL @decalage DAY, 'Nina', 'Rousseau', '54 rue Pasteur, 75018 Paris', 29.10, 0.00, 0.00, 29.10, 29, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1197, 1035, '2026-07-17 11:07:24' + INTERVAL @decalage DAY, 'Nina', 'Rousseau', '54 rue Pasteur, 75018 Paris', 12.70, 0.00, 1.99, 14.69, 12, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1198, 1035, '2026-07-25 11:03:13' + INTERVAL @decalage DAY, 'Nina', 'Rousseau', '54 rue Pasteur, 75018 Paris', 11.40, 0.00, 1.99, 13.39, 11, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1199, 1035, '2026-08-02 14:27:43' + INTERVAL @decalage DAY, 'Nina', 'Rousseau', '54 rue Pasteur, 75018 Paris', 4.40, 0.00, 1.99, 6.39, 4, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1200, 1035, '2026-08-12 13:35:06' + INTERVAL @decalage DAY, 'Nina', 'Rousseau', '54 rue Pasteur, 75018 Paris', 8.40, 0.00, 1.99, 10.39, 8, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1201, 1035, '2026-09-13 22:04:16' + INTERVAL @decalage DAY, 'Nina', 'Rousseau', '54 rue Pasteur, 75018 Paris', 16.70, 0.00, 1.99, 13.69, 11, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1202, 1035, '2026-09-21 12:41:01' + INTERVAL @decalage DAY, 'Nina', 'Rousseau', '54 rue Pasteur, 75018 Paris', 8.70, 0.00, 1.99, 10.69, 8, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1203, 1035, '2026-09-21 21:16:54' + INTERVAL @decalage DAY, 'Nina', 'Rousseau', '54 rue Pasteur, 75018 Paris', 35.40, 1.77, 0.00, 33.63, 33, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1204, 1035, '2026-10-04 10:31:55' + INTERVAL @decalage DAY, 'Nina', 'Rousseau', '54 rue Pasteur, 75018 Paris', 27.80, 0.00, 0.00, 27.80, 27, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1205, 1035, '2026-10-16 20:00:18' + INTERVAL @decalage DAY, 'Nina', 'Rousseau', '54 rue Pasteur, 75018 Paris', 17.40, 0.00, 1.99, 19.39, 17, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1206, 1035, '2026-10-22 14:28:35' + INTERVAL @decalage DAY, 'Nina', 'Rousseau', '54 rue Pasteur, 75018 Paris', 34.10, 1.71, 0.00, 32.39, 32, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1207, 1036, '2025-08-11 19:40:09' + INTERVAL @decalage DAY, 'Malo', 'Blanc', '94 rue des Lilas, 59000 Lille', 10.20, 0.00, 1.99, 12.19, 10, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1208, 1036, '2026-01-25 13:43:58' + INTERVAL @decalage DAY, 'Malo', 'Blanc', '94 rue des Lilas, 59000 Lille', 29.19, 0.00, 0.00, 29.19, 29, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1209, 1036, '2026-02-08 12:44:58' + INTERVAL @decalage DAY, 'Malo', 'Blanc', '94 rue des Lilas, 59000 Lille', 11.20, 0.00, 1.99, 13.19, 11, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1210, 1036, '2026-03-27 17:20:14' + INTERVAL @decalage DAY, 'Malo', 'Blanc', '94 rue des Lilas, 59000 Lille', 15.70, 0.00, 1.99, 17.69, 15, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1211, 1036, '2026-04-16 11:44:18' + INTERVAL @decalage DAY, 'Malo', 'Blanc', '94 rue des Lilas, 59000 Lille', 11.30, 0.00, 1.99, 13.29, 11, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1212, 1036, '2026-05-24 08:40:10' + INTERVAL @decalage DAY, 'Malo', 'Blanc', '94 rue des Lilas, 59000 Lille', 24.00, 0.00, 0.00, 19.00, 19, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1213, 1036, '2026-06-05 09:56:08' + INTERVAL @decalage DAY, 'Malo', 'Blanc', '94 rue des Lilas, 59000 Lille', 20.55, 0.00, 0.00, 20.55, 20, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1214, 1036, '2026-06-30 19:04:25' + INTERVAL @decalage DAY, 'Malo', 'Blanc', '94 rue des Lilas, 59000 Lille', 23.50, 0.00, 0.00, 23.50, 23, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1215, 1036, '2026-07-14 14:10:11' + INTERVAL @decalage DAY, 'Malo', 'Blanc', '94 rue des Lilas, 59000 Lille', 10.90, 0.00, 1.99, 12.89, 10, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1216, 1036, '2026-09-21 22:07:26' + INTERVAL @decalage DAY, 'Malo', 'Blanc', '94 rue des Lilas, 59000 Lille', 19.70, 0.00, 1.99, 16.69, 14, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1217, 1037, '2025-07-14 08:01:16' + INTERVAL @decalage DAY, 'Lou', 'Guerin', '52 boulevard de la Croix-Rousse, 13005 Marseille', 21.20, 0.00, 0.00, 21.20, 21, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1218, 1037, '2025-12-24 10:07:16' + INTERVAL @decalage DAY, 'Lou', 'Guerin', '52 boulevard de la Croix-Rousse, 13005 Marseille', 9.90, 0.00, 1.99, 11.89, 9, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1219, 1037, '2025-12-25 12:53:31' + INTERVAL @decalage DAY, 'Lou', 'Guerin', '52 boulevard de la Croix-Rousse, 13005 Marseille', 5.30, 0.00, 1.99, 7.29, 5, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1220, 1037, '2025-12-25 17:58:36' + INTERVAL @decalage DAY, 'Lou', 'Guerin', '52 boulevard de la Croix-Rousse, 13005 Marseille', 17.30, 0.00, 1.99, 19.29, 17, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1221, 1037, '2025-12-28 13:47:33' + INTERVAL @decalage DAY, 'Lou', 'Guerin', '52 boulevard de la Croix-Rousse, 13005 Marseille', 48.90, 2.45, 0.00, 46.45, 46, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1222, 1037, '2026-01-16 11:51:57' + INTERVAL @decalage DAY, 'Lou', 'Guerin', '52 boulevard de la Croix-Rousse, 13005 Marseille', 4.50, 0.00, 1.99, 6.49, 4, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1223, 1037, '2026-02-25 19:39:21' + INTERVAL @decalage DAY, 'Lou', 'Guerin', '52 boulevard de la Croix-Rousse, 13005 Marseille', 8.90, 0.00, 1.99, 10.89, 8, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1224, 1037, '2026-02-26 18:47:25' + INTERVAL @decalage DAY, 'Lou', 'Guerin', '52 boulevard de la Croix-Rousse, 13005 Marseille', 9.90, 0.00, 1.99, 11.89, 9, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1225, 1037, '2026-03-07 21:22:55' + INTERVAL @decalage DAY, 'Lou', 'Guerin', '52 boulevard de la Croix-Rousse, 13005 Marseille', 10.10, 0.00, 1.99, 12.09, 10, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1226, 1037, '2026-05-12 11:29:45' + INTERVAL @decalage DAY, 'Lou', 'Guerin', '52 boulevard de la Croix-Rousse, 13005 Marseille', 20.70, 0.00, 0.00, 15.70, 15, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1227, 1037, '2026-07-03 13:33:34' + INTERVAL @decalage DAY, 'Lou', 'Guerin', '52 boulevard de la Croix-Rousse, 13005 Marseille', 11.80, 0.00, 1.99, 13.79, 11, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1228, 1037, '2026-09-07 18:34:05' + INTERVAL @decalage DAY, 'Lou', 'Guerin', '52 boulevard de la Croix-Rousse, 13005 Marseille', 19.70, 0.00, 1.99, 16.69, 14, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1229, 1037, '2026-09-15 08:13:03' + INTERVAL @decalage DAY, 'Lou', 'Guerin', '52 boulevard de la Croix-Rousse, 13005 Marseille', 3.40, 0.00, 1.99, 5.39, 3, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1230, 1037, '2026-09-18 12:10:17' + INTERVAL @decalage DAY, 'Lou', 'Guerin', '52 boulevard de la Croix-Rousse, 13005 Marseille', 28.20, 0.00, 0.00, 28.20, 28, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1231, 1037, '2026-10-15 13:07:33' + INTERVAL @decalage DAY, 'Lou', 'Guerin', '52 boulevard de la Croix-Rousse, 13005 Marseille', 17.60, 0.00, 1.99, 19.59, 17, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1232, 1038, '2026-03-11 16:00:51' + INTERVAL @decalage DAY, 'Mathis', 'Muller', '39 rue de la République, 33000 Bordeaux', 7.20, 0.00, 1.99, 9.19, 7, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1233, 1038, '2026-03-29 17:04:18' + INTERVAL @decalage DAY, 'Mathis', 'Muller', '39 rue de la République, 33000 Bordeaux', 79.20, 7.92, 0.00, 71.28, 71, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1234, 1038, '2026-04-03 08:58:15' + INTERVAL @decalage DAY, 'Mathis', 'Muller', '39 rue de la République, 33000 Bordeaux', 35.40, 1.77, 0.00, 28.63, 28, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1235, 1038, '2026-04-26 21:11:47' + INTERVAL @decalage DAY, 'Mathis', 'Muller', '39 rue de la République, 33000 Bordeaux', 2.10, 0.00, 1.99, 4.09, 2, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1236, 1038, '2026-05-13 22:39:45' + INTERVAL @decalage DAY, 'Mathis', 'Muller', '39 rue de la République, 33000 Bordeaux', 14.40, 0.00, 1.99, 16.39, 14, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1237, 1038, '2026-05-20 14:27:06' + INTERVAL @decalage DAY, 'Mathis', 'Muller', '39 rue de la République, 33000 Bordeaux', 26.60, 0.00, 0.00, 26.60, 26, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1238, 1038, '2026-06-06 19:29:31' + INTERVAL @decalage DAY, 'Mathis', 'Muller', '39 rue de la République, 33000 Bordeaux', 14.30, 0.00, 1.99, 16.29, 14, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1239, 1038, '2026-07-04 10:00:35' + INTERVAL @decalage DAY, 'Mathis', 'Muller', '39 rue de la République, 33000 Bordeaux', 11.60, 0.00, 1.99, 13.59, 11, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1240, 1038, '2026-07-10 21:28:37' + INTERVAL @decalage DAY, 'Mathis', 'Muller', '39 rue de la République, 33000 Bordeaux', 12.10, 0.00, 1.99, 14.09, 12, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1241, 1038, '2026-08-22 16:24:05' + INTERVAL @decalage DAY, 'Mathis', 'Muller', '39 rue de la République, 33000 Bordeaux', 38.60, 1.93, 0.00, 36.67, 36, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1242, 1039, '2025-09-20 22:26:30' + INTERVAL @decalage DAY, 'Clara', 'Henry', '1 rue Saint-Michel, 44000 Nantes', 15.70, 0.00, 1.99, 17.69, 15, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1243, 1039, '2025-09-21 11:00:53' + INTERVAL @decalage DAY, 'Clara', 'Henry', '1 rue Saint-Michel, 44000 Nantes', 22.60, 0.00, 0.00, 22.60, 22, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1244, 1039, '2025-09-25 12:16:03' + INTERVAL @decalage DAY, 'Clara', 'Henry', '1 rue Saint-Michel, 44000 Nantes', 3.80, 0.00, 1.99, 5.79, 3, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1245, 1039, '2025-10-31 16:13:35' + INTERVAL @decalage DAY, 'Clara', 'Henry', '1 rue Saint-Michel, 44000 Nantes', 13.70, 0.00, 1.99, 15.69, 13, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1246, 1039, '2025-12-01 14:43:07' + INTERVAL @decalage DAY, 'Clara', 'Henry', '1 rue Saint-Michel, 44000 Nantes', 8.10, 0.00, 1.99, 10.09, 8, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1247, 1039, '2025-12-03 20:07:30' + INTERVAL @decalage DAY, 'Clara', 'Henry', '1 rue Saint-Michel, 44000 Nantes', 5.20, 0.00, 1.99, 7.19, 5, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1248, 1039, '2025-12-05 21:25:37' + INTERVAL @decalage DAY, 'Clara', 'Henry', '1 rue Saint-Michel, 44000 Nantes', 24.40, 0.00, 0.00, 24.40, 24, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1249, 1039, '2025-12-17 10:48:47' + INTERVAL @decalage DAY, 'Clara', 'Henry', '1 rue Saint-Michel, 44000 Nantes', 17.00, 0.00, 1.99, 18.99, 17, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1250, 1039, '2025-12-26 08:02:41' + INTERVAL @decalage DAY, 'Clara', 'Henry', '1 rue Saint-Michel, 44000 Nantes', 8.90, 0.00, 1.99, 10.89, 8, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1251, 1039, '2025-12-31 17:01:45' + INTERVAL @decalage DAY, 'Clara', 'Henry', '1 rue Saint-Michel, 44000 Nantes', 25.70, 0.00, 0.00, 20.70, 20, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1252, 1039, '2026-01-18 12:22:13' + INTERVAL @decalage DAY, 'Clara', 'Henry', '1 rue Saint-Michel, 44000 Nantes', 9.70, 0.00, 1.99, 11.69, 9, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1253, 1039, '2026-01-19 16:34:35' + INTERVAL @decalage DAY, 'Clara', 'Henry', '1 rue Saint-Michel, 44000 Nantes', 4.90, 0.00, 1.99, 6.89, 4, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1254, 1039, '2026-02-09 19:22:25' + INTERVAL @decalage DAY, 'Clara', 'Henry', '1 rue Saint-Michel, 44000 Nantes', 9.60, 0.00, 1.99, 11.59, 9, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1255, 1039, '2026-02-17 12:09:02' + INTERVAL @decalage DAY, 'Clara', 'Henry', '1 rue Saint-Michel, 44000 Nantes', 130.30, 13.03, 0.00, 117.27, 117, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1256, 1039, '2026-03-17 16:14:38' + INTERVAL @decalage DAY, 'Clara', 'Henry', '1 rue Saint-Michel, 44000 Nantes', 6.30, 0.00, 1.99, 8.29, 6, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1257, 1039, '2026-03-18 12:35:47' + INTERVAL @decalage DAY, 'Clara', 'Henry', '1 rue Saint-Michel, 44000 Nantes', 19.80, 0.00, 1.99, 16.79, 14, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1258, 1039, '2026-03-23 16:23:25' + INTERVAL @decalage DAY, 'Clara', 'Henry', '1 rue Saint-Michel, 44000 Nantes', 4.40, 0.00, 1.99, 6.39, 4, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1259, 1039, '2026-04-07 21:23:40' + INTERVAL @decalage DAY, 'Clara', 'Henry', '1 rue Saint-Michel, 44000 Nantes', 9.00, 0.00, 1.99, 10.99, 9, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1260, 1039, '2026-04-15 13:23:23' + INTERVAL @decalage DAY, 'Clara', 'Henry', '1 rue Saint-Michel, 44000 Nantes', 13.50, 0.00, 1.99, 15.49, 13, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1261, 1039, '2026-04-25 13:42:34' + INTERVAL @decalage DAY, 'Clara', 'Henry', '1 rue Saint-Michel, 44000 Nantes', 31.40, 1.57, 0.00, 29.83, 29, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1262, 1039, '2026-05-02 22:07:42' + INTERVAL @decalage DAY, 'Clara', 'Henry', '1 rue Saint-Michel, 44000 Nantes', 16.10, 0.00, 1.99, 13.09, 11, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1263, 1039, '2026-05-19 22:55:34' + INTERVAL @decalage DAY, 'Clara', 'Henry', '1 rue Saint-Michel, 44000 Nantes', 6.90, 0.00, 1.99, 8.89, 6, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1264, 1039, '2026-05-29 09:31:38' + INTERVAL @decalage DAY, 'Clara', 'Henry', '1 rue Saint-Michel, 44000 Nantes', 14.99, 0.00, 1.99, 16.98, 14, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1265, 1039, '2026-06-24 22:24:00' + INTERVAL @decalage DAY, 'Clara', 'Henry', '1 rue Saint-Michel, 44000 Nantes', 58.00, 5.80, 0.00, 41.70, 41, 'validee', NULL, 200, 10.50, 1, NULL, NULL),
(1266, 1039, '2026-07-01 16:53:29' + INTERVAL @decalage DAY, 'Clara', 'Henry', '1 rue Saint-Michel, 44000 Nantes', 21.90, 0.00, 0.00, 21.90, 21, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1267, 1039, '2026-08-24 09:26:33' + INTERVAL @decalage DAY, 'Clara', 'Henry', '1 rue Saint-Michel, 44000 Nantes', 16.80, 0.00, 1.99, 18.79, 16, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1268, 1039, '2026-08-27 21:33:58' + INTERVAL @decalage DAY, 'Clara', 'Henry', '1 rue Saint-Michel, 44000 Nantes', 16.20, 0.00, 1.99, 18.19, 16, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1269, 1039, '2026-09-06 09:52:43' + INTERVAL @decalage DAY, 'Clara', 'Henry', '1 rue Saint-Michel, 44000 Nantes', 7.40, 0.00, 1.99, 9.39, 7, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1270, 1039, '2026-09-06 10:08:08' + INTERVAL @decalage DAY, 'Clara', 'Henry', '1 rue Saint-Michel, 44000 Nantes', 51.50, 5.15, 0.00, 41.35, 41, 'validee', NULL, 100, 5.00, 1, NULL, NULL),
(1271, 1039, '2026-09-07 18:59:44' + INTERVAL @decalage DAY, 'Clara', 'Henry', '1 rue Saint-Michel, 44000 Nantes', 15.70, 0.00, 1.99, 17.69, 15, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1272, 1039, '2026-09-08 14:01:19' + INTERVAL @decalage DAY, 'Clara', 'Henry', '1 rue Saint-Michel, 44000 Nantes', 19.60, 0.00, 1.99, 16.59, 14, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1273, 1039, '2026-09-14 14:28:35' + INTERVAL @decalage DAY, 'Clara', 'Henry', '1 rue Saint-Michel, 44000 Nantes', 29.20, 0.00, 0.00, 29.20, 29, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1274, 1039, '2026-09-30 08:44:44' + INTERVAL @decalage DAY, 'Clara', 'Henry', '1 rue Saint-Michel, 44000 Nantes', 8.90, 0.00, 1.99, 10.89, 8, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1275, 1039, '2026-10-01 21:02:21' + INTERVAL @decalage DAY, 'Clara', 'Henry', '1 rue Saint-Michel, 44000 Nantes', 35.70, 1.79, 0.00, 28.91, 28, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1276, 1039, '2026-10-23 20:25:37' + INTERVAL @decalage DAY, 'Clara', 'Henry', '1 rue Saint-Michel, 44000 Nantes', 32.50, 1.63, 0.00, 30.87, 30, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1277, 1040, '2025-04-24 17:01:00' + INTERVAL @decalage DAY, 'Rayan', 'Roussel', '91 rue des Lilas, 31000 Toulouse', 40.93, 2.05, 0.00, 38.88, 38, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1278, 1040, '2025-05-02 20:51:57' + INTERVAL @decalage DAY, 'Rayan', 'Roussel', '91 rue des Lilas, 31000 Toulouse', 13.80, 0.00, 1.99, 15.79, 13, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1279, 1040, '2025-06-14 22:01:18' + INTERVAL @decalage DAY, 'Rayan', 'Roussel', '91 rue des Lilas, 31000 Toulouse', 3.10, 0.00, 1.99, 5.09, 3, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1280, 1040, '2025-07-13 16:45:58' + INTERVAL @decalage DAY, 'Rayan', 'Roussel', '91 rue des Lilas, 31000 Toulouse', 48.90, 2.45, 0.00, 46.45, 46, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1281, 1040, '2025-08-06 11:13:20' + INTERVAL @decalage DAY, 'Rayan', 'Roussel', '91 rue des Lilas, 31000 Toulouse', 3.50, 0.00, 1.99, 5.49, 3, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1282, 1040, '2025-08-15 12:20:34' + INTERVAL @decalage DAY, 'Rayan', 'Roussel', '91 rue des Lilas, 31000 Toulouse', 11.25, 0.00, 1.99, 13.24, 11, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1283, 1040, '2025-08-25 10:38:25' + INTERVAL @decalage DAY, 'Rayan', 'Roussel', '91 rue des Lilas, 31000 Toulouse', 20.10, 0.00, 0.00, 15.10, 15, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1284, 1040, '2025-08-25 17:02:41' + INTERVAL @decalage DAY, 'Rayan', 'Roussel', '91 rue des Lilas, 31000 Toulouse', 15.80, 0.00, 1.99, 17.79, 15, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1285, 1040, '2025-10-05 22:08:39' + INTERVAL @decalage DAY, 'Rayan', 'Roussel', '91 rue des Lilas, 31000 Toulouse', 8.30, 0.00, 1.99, 10.29, 8, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1286, 1040, '2025-10-07 12:41:40' + INTERVAL @decalage DAY, 'Rayan', 'Roussel', '91 rue des Lilas, 31000 Toulouse', 18.70, 0.00, 1.99, 15.69, 13, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1287, 1040, '2025-11-08 13:46:35' + INTERVAL @decalage DAY, 'Rayan', 'Roussel', '91 rue des Lilas, 31000 Toulouse', 14.60, 0.00, 1.99, 16.59, 14, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1288, 1040, '2025-12-03 12:38:31' + INTERVAL @decalage DAY, 'Rayan', 'Roussel', '91 rue des Lilas, 31000 Toulouse', 11.50, 0.00, 1.99, 13.49, 11, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1289, 1040, '2025-12-17 10:07:41' + INTERVAL @decalage DAY, 'Rayan', 'Roussel', '91 rue des Lilas, 31000 Toulouse', 4.30, 0.00, 1.99, 6.29, 4, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1290, 1040, '2025-12-24 10:13:23' + INTERVAL @decalage DAY, 'Rayan', 'Roussel', '91 rue des Lilas, 31000 Toulouse', 95.10, 9.51, 0.00, 85.59, 85, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1291, 1040, '2026-01-15 14:40:03' + INTERVAL @decalage DAY, 'Rayan', 'Roussel', '91 rue des Lilas, 31000 Toulouse', 1.80, 0.00, 1.99, 3.79, 1, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1292, 1040, '2026-01-20 19:28:01' + INTERVAL @decalage DAY, 'Rayan', 'Roussel', '91 rue des Lilas, 31000 Toulouse', 18.00, 0.00, 1.99, 14.99, 13, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1293, 1040, '2026-05-04 22:46:29' + INTERVAL @decalage DAY, 'Rayan', 'Roussel', '91 rue des Lilas, 31000 Toulouse', 17.70, 0.00, 1.99, 19.69, 17, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1294, 1040, '2026-05-07 09:40:56' + INTERVAL @decalage DAY, 'Rayan', 'Roussel', '91 rue des Lilas, 31000 Toulouse', 32.69, 1.63, 0.00, 26.06, 26, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1295, 1040, '2026-05-08 19:03:37' + INTERVAL @decalage DAY, 'Rayan', 'Roussel', '91 rue des Lilas, 31000 Toulouse', 7.10, 0.00, 1.99, 9.09, 7, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1296, 1040, '2026-05-14 20:49:51' + INTERVAL @decalage DAY, 'Rayan', 'Roussel', '91 rue des Lilas, 31000 Toulouse', 10.99, 0.00, 1.99, 12.98, 10, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1297, 1040, '2026-05-21 16:35:18' + INTERVAL @decalage DAY, 'Rayan', 'Roussel', '91 rue des Lilas, 31000 Toulouse', 7.90, 0.00, 1.99, 9.89, 7, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1298, 1040, '2026-06-04 14:15:25' + INTERVAL @decalage DAY, 'Rayan', 'Roussel', '91 rue des Lilas, 31000 Toulouse', 20.20, 0.00, 0.00, 20.20, 20, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1299, 1040, '2026-06-08 16:56:04' + INTERVAL @decalage DAY, 'Rayan', 'Roussel', '91 rue des Lilas, 31000 Toulouse', 23.60, 0.00, 0.00, 23.60, 23, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1300, 1040, '2026-07-12 19:34:54' + INTERVAL @decalage DAY, 'Rayan', 'Roussel', '91 rue des Lilas, 31000 Toulouse', 17.90, 0.00, 1.99, 14.89, 12, 'validee', NULL, 100, 5.00, 0, NULL, NULL);

INSERT INTO commande (id_commande, id_user, date_commande, prenom, nom, adresse, sous_total, remise, frais_livraison, total, points_gagnes, statut, date_annulation, points_utilises, reduction_points, bonus_niveau, id_annulateur, motif_annulation) VALUES
(1301, 1040, '2026-07-18 08:15:23' + INTERVAL @decalage DAY, 'Rayan', 'Roussel', '91 rue des Lilas, 31000 Toulouse', 47.50, 2.38, 0.00, 45.12, 45, 'validee', NULL, 0, 0.00, 1, NULL, NULL),
(1302, 1040, '2026-07-29 17:04:21' + INTERVAL @decalage DAY, 'Rayan', 'Roussel', '91 rue des Lilas, 31000 Toulouse', 18.50, 0.00, 1.99, 15.49, 13, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1303, 1040, '2026-08-12 16:01:09' + INTERVAL @decalage DAY, 'Rayan', 'Roussel', '91 rue des Lilas, 31000 Toulouse', 10.00, 0.00, 1.99, 11.99, 10, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1304, 1040, '2026-08-29 10:02:01' + INTERVAL @decalage DAY, 'Rayan', 'Roussel', '91 rue des Lilas, 31000 Toulouse', 4.90, 0.00, 1.99, 6.89, 4, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1305, 1040, '2026-09-11 21:43:27' + INTERVAL @decalage DAY, 'Rayan', 'Roussel', '91 rue des Lilas, 31000 Toulouse', 8.20, 0.00, 1.99, 10.19, 8, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1306, 1040, '2026-09-18 12:12:35' + INTERVAL @decalage DAY, 'Rayan', 'Roussel', '91 rue des Lilas, 31000 Toulouse', 8.00, 0.00, 1.99, 9.99, 8, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1307, 1040, '2026-09-18 20:20:46' + INTERVAL @decalage DAY, 'Rayan', 'Roussel', '91 rue des Lilas, 31000 Toulouse', 47.10, 2.36, 0.00, 44.74, 44, 'validee', NULL, 0, 0.00, 1, NULL, NULL),
(1308, 1040, '2026-09-20 14:05:45' + INTERVAL @decalage DAY, 'Rayan', 'Roussel', '91 rue des Lilas, 31000 Toulouse', 87.40, 8.74, 0.00, 73.66, 73, 'validee', NULL, 100, 5.00, 2, NULL, NULL),
(1309, 1040, '2026-10-16 13:04:02' + INTERVAL @decalage DAY, 'Rayan', 'Roussel', '91 rue des Lilas, 31000 Toulouse', 21.50, 0.00, 0.00, 21.50, 21, 'annulee', '2026-10-17 13:04:02' + INTERVAL @decalage DAY, 0, 0.00, 0, 1, 'Annulation demandée par le client'),
(1310, 1040, '2026-10-17 12:23:12' + INTERVAL @decalage DAY, 'Rayan', 'Roussel', '91 rue des Lilas, 31000 Toulouse', 75.50, 7.55, 0.00, 62.95, 62, 'validee', NULL, 100, 5.00, 1, NULL, NULL),
(1311, 1041, '2025-08-29 16:54:35' + INTERVAL @decalage DAY, 'Eva', 'Nicolas', '78 allée des Tilleuls, 38000 Grenoble', 12.00, 0.00, 1.99, 13.99, 12, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1312, 1041, '2025-09-19 16:02:17' + INTERVAL @decalage DAY, 'Eva', 'Nicolas', '78 allée des Tilleuls, 38000 Grenoble', 43.30, 2.17, 0.00, 41.13, 41, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1313, 1041, '2025-09-20 15:35:19' + INTERVAL @decalage DAY, 'Eva', 'Nicolas', '78 allée des Tilleuls, 38000 Grenoble', 12.00, 0.00, 1.99, 13.99, 12, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1314, 1041, '2025-10-04 16:04:55' + INTERVAL @decalage DAY, 'Eva', 'Nicolas', '78 allée des Tilleuls, 38000 Grenoble', 8.20, 0.00, 1.99, 10.19, 8, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1315, 1041, '2025-11-03 18:46:48' + INTERVAL @decalage DAY, 'Eva', 'Nicolas', '78 allée des Tilleuls, 38000 Grenoble', 17.90, 0.00, 1.99, 19.89, 17, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1316, 1041, '2025-11-22 11:57:12' + INTERVAL @decalage DAY, 'Eva', 'Nicolas', '78 allée des Tilleuls, 38000 Grenoble', 9.30, 0.00, 1.99, 11.29, 9, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1317, 1041, '2025-12-29 11:26:17' + INTERVAL @decalage DAY, 'Eva', 'Nicolas', '78 allée des Tilleuls, 38000 Grenoble', 4.30, 0.00, 1.99, 6.29, 4, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1318, 1041, '2026-01-11 10:40:31' + INTERVAL @decalage DAY, 'Eva', 'Nicolas', '78 allée des Tilleuls, 38000 Grenoble', 38.60, 1.93, 0.00, 31.67, 31, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1319, 1041, '2026-01-14 20:02:30' + INTERVAL @decalage DAY, 'Eva', 'Nicolas', '78 allée des Tilleuls, 38000 Grenoble', 17.70, 0.00, 1.99, 19.69, 17, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1320, 1041, '2026-03-04 17:57:31' + INTERVAL @decalage DAY, 'Eva', 'Nicolas', '78 allée des Tilleuls, 38000 Grenoble', 13.20, 0.00, 1.99, 15.19, 13, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1321, 1041, '2026-05-15 15:56:53' + INTERVAL @decalage DAY, 'Eva', 'Nicolas', '78 allée des Tilleuls, 38000 Grenoble', 16.90, 0.00, 1.99, 13.89, 11, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1322, 1041, '2026-05-18 09:29:18' + INTERVAL @decalage DAY, 'Eva', 'Nicolas', '78 allée des Tilleuls, 38000 Grenoble', 22.80, 0.00, 0.00, 22.80, 22, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1323, 1041, '2026-05-20 08:09:02' + INTERVAL @decalage DAY, 'Eva', 'Nicolas', '78 allée des Tilleuls, 38000 Grenoble', 19.00, 0.00, 1.99, 20.99, 19, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1324, 1041, '2026-06-19 09:40:43' + INTERVAL @decalage DAY, 'Eva', 'Nicolas', '78 allée des Tilleuls, 38000 Grenoble', 26.55, 0.00, 0.00, 26.55, 26, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1325, 1041, '2026-06-22 17:20:02' + INTERVAL @decalage DAY, 'Eva', 'Nicolas', '78 allée des Tilleuls, 38000 Grenoble', 43.50, 2.18, 0.00, 36.32, 36, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1326, 1041, '2026-07-03 11:06:11' + INTERVAL @decalage DAY, 'Eva', 'Nicolas', '78 allée des Tilleuls, 38000 Grenoble', 27.45, 0.00, 0.00, 27.45, 27, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1327, 1041, '2026-07-15 11:42:09' + INTERVAL @decalage DAY, 'Eva', 'Nicolas', '78 allée des Tilleuls, 38000 Grenoble', 30.95, 1.55, 0.00, 29.40, 29, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1328, 1041, '2026-08-12 16:43:37' + INTERVAL @decalage DAY, 'Eva', 'Nicolas', '78 allée des Tilleuls, 38000 Grenoble', 11.50, 0.00, 1.99, 13.49, 11, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1329, 1041, '2026-08-28 17:19:57' + INTERVAL @decalage DAY, 'Eva', 'Nicolas', '78 allée des Tilleuls, 38000 Grenoble', 13.90, 0.00, 1.99, 15.89, 13, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1330, 1041, '2026-08-31 08:15:14' + INTERVAL @decalage DAY, 'Eva', 'Nicolas', '78 allée des Tilleuls, 38000 Grenoble', 14.70, 0.00, 1.99, 16.69, 14, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1331, 1041, '2026-09-16 20:28:13' + INTERVAL @decalage DAY, 'Eva', 'Nicolas', '78 allée des Tilleuls, 38000 Grenoble', 77.50, 7.75, 0.00, 69.75, 69, 'validee', NULL, 0, 0.00, 2, NULL, NULL),
(1332, 1042, '2025-09-15 12:25:05' + INTERVAL @decalage DAY, 'Victor', 'Perrin', '81 rue de la République, 60200 Compiègne', 22.00, 0.00, 0.00, 22.00, 22, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1333, 1042, '2025-10-03 11:45:53' + INTERVAL @decalage DAY, 'Victor', 'Perrin', '81 rue de la République, 60200 Compiègne', 6.90, 0.00, 1.99, 8.89, 6, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1334, 1042, '2025-11-29 13:39:12' + INTERVAL @decalage DAY, 'Victor', 'Perrin', '81 rue de la République, 60200 Compiègne', 20.70, 0.00, 0.00, 20.70, 20, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1335, 1042, '2025-12-08 12:00:47' + INTERVAL @decalage DAY, 'Victor', 'Perrin', '81 rue de la République, 60200 Compiègne', 20.20, 0.00, 0.00, 20.20, 20, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1336, 1042, '2026-01-02 13:41:32' + INTERVAL @decalage DAY, 'Victor', 'Perrin', '81 rue de la République, 60200 Compiègne', 37.50, 1.88, 0.00, 30.62, 30, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1337, 1042, '2026-01-04 08:59:25' + INTERVAL @decalage DAY, 'Victor', 'Perrin', '81 rue de la République, 60200 Compiègne', 12.50, 0.00, 1.99, 14.49, 12, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1338, 1042, '2026-01-07 22:27:55' + INTERVAL @decalage DAY, 'Victor', 'Perrin', '81 rue de la République, 60200 Compiègne', 40.80, 2.04, 0.00, 38.76, 38, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1339, 1042, '2026-01-09 13:30:01' + INTERVAL @decalage DAY, 'Victor', 'Perrin', '81 rue de la République, 60200 Compiègne', 18.50, 0.00, 1.99, 15.49, 13, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1340, 1042, '2026-01-16 21:20:58' + INTERVAL @decalage DAY, 'Victor', 'Perrin', '81 rue de la République, 60200 Compiègne', 30.40, 1.52, 0.00, 28.88, 28, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1341, 1042, '2026-01-27 16:56:30' + INTERVAL @decalage DAY, 'Victor', 'Perrin', '81 rue de la République, 60200 Compiègne', 6.95, 0.00, 1.99, 8.94, 6, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1342, 1042, '2026-03-21 12:08:31' + INTERVAL @decalage DAY, 'Victor', 'Perrin', '81 rue de la République, 60200 Compiègne', 54.10, 5.41, 0.00, 48.69, 48, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1343, 1042, '2026-04-03 18:32:09' + INTERVAL @decalage DAY, 'Victor', 'Perrin', '81 rue de la République, 60200 Compiègne', 21.00, 0.00, 0.00, 21.00, 21, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1344, 1042, '2026-05-13 22:42:46' + INTERVAL @decalage DAY, 'Victor', 'Perrin', '81 rue de la République, 60200 Compiègne', 7.70, 0.00, 1.99, 9.69, 7, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1345, 1042, '2026-06-28 17:31:38' + INTERVAL @decalage DAY, 'Victor', 'Perrin', '81 rue de la République, 60200 Compiègne', 6.00, 0.00, 1.99, 7.99, 6, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1346, 1042, '2026-08-06 08:41:40' + INTERVAL @decalage DAY, 'Victor', 'Perrin', '81 rue de la République, 60200 Compiègne', 31.00, 1.55, 0.00, 24.45, 24, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1347, 1042, '2026-08-08 21:56:42' + INTERVAL @decalage DAY, 'Victor', 'Perrin', '81 rue de la République, 60200 Compiègne', 19.20, 0.00, 1.99, 21.19, 19, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1348, 1042, '2026-08-15 21:33:39' + INTERVAL @decalage DAY, 'Victor', 'Perrin', '81 rue de la République, 60200 Compiègne', 7.20, 0.00, 1.99, 9.19, 7, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1349, 1042, '2026-09-04 08:21:11' + INTERVAL @decalage DAY, 'Victor', 'Perrin', '81 rue de la République, 60200 Compiègne', 29.00, 0.00, 0.00, 24.00, 24, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1350, 1042, '2026-09-20 20:29:45' + INTERVAL @decalage DAY, 'Victor', 'Perrin', '81 rue de la République, 60200 Compiègne', 11.50, 0.00, 1.99, 13.49, 11, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1351, 1042, '2026-10-04 18:49:18' + INTERVAL @decalage DAY, 'Victor', 'Perrin', '81 rue de la République, 60200 Compiègne', 38.70, 1.94, 0.00, 36.76, 36, 'validee', NULL, 0, 0.00, 1, NULL, NULL),
(1352, 1042, '2026-10-15 10:27:07' + INTERVAL @decalage DAY, 'Victor', 'Perrin', '81 rue de la République, 60200 Compiègne', 26.70, 0.00, 0.00, 26.70, 26, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1353, 1043, '2025-02-06 16:36:44' + INTERVAL @decalage DAY, 'Margaux', 'Morin', '64 rue Victor Hugo, 35000 Rennes', 50.39, 5.04, 0.00, 45.35, 45, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1354, 1043, '2025-03-20 17:59:49' + INTERVAL @decalage DAY, 'Margaux', 'Morin', '64 rue Victor Hugo, 35000 Rennes', 118.80, 11.88, 0.00, 106.92, 106, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1355, 1043, '2025-06-15 13:05:11' + INTERVAL @decalage DAY, 'Margaux', 'Morin', '64 rue Victor Hugo, 35000 Rennes', 6.30, 0.00, 1.99, 8.29, 6, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1356, 1043, '2025-06-28 19:23:34' + INTERVAL @decalage DAY, 'Margaux', 'Morin', '64 rue Victor Hugo, 35000 Rennes', 15.80, 0.00, 1.99, 12.79, 10, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1357, 1043, '2025-09-21 15:43:47' + INTERVAL @decalage DAY, 'Margaux', 'Morin', '64 rue Victor Hugo, 35000 Rennes', 9.70, 0.00, 1.99, 11.69, 9, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1358, 1043, '2025-10-24 17:22:07' + INTERVAL @decalage DAY, 'Margaux', 'Morin', '64 rue Victor Hugo, 35000 Rennes', 69.00, 6.90, 0.00, 62.10, 62, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1359, 1043, '2025-10-30 19:19:45' + INTERVAL @decalage DAY, 'Margaux', 'Morin', '64 rue Victor Hugo, 35000 Rennes', 49.30, 2.47, 0.00, 36.33, 36, 'validee', NULL, 200, 10.50, 0, NULL, NULL),
(1360, 1043, '2025-11-19 09:47:25' + INTERVAL @decalage DAY, 'Margaux', 'Morin', '64 rue Victor Hugo, 35000 Rennes', 57.20, 5.72, 0.00, 51.48, 51, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1361, 1043, '2026-01-12 08:33:52' + INTERVAL @decalage DAY, 'Margaux', 'Morin', '64 rue Victor Hugo, 35000 Rennes', 15.80, 0.00, 1.99, 17.79, 15, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1362, 1043, '2026-02-22 21:36:21' + INTERVAL @decalage DAY, 'Margaux', 'Morin', '64 rue Victor Hugo, 35000 Rennes', 51.80, 5.18, 0.00, 46.62, 46, 'validee', NULL, 0, 0.00, 1, NULL, NULL),
(1363, 1043, '2026-03-24 14:33:21' + INTERVAL @decalage DAY, 'Margaux', 'Morin', '64 rue Victor Hugo, 35000 Rennes', 17.85, 0.00, 1.99, 14.84, 12, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1364, 1043, '2026-04-30 11:03:43' + INTERVAL @decalage DAY, 'Margaux', 'Morin', '64 rue Victor Hugo, 35000 Rennes', 90.20, 9.02, 0.00, 76.18, 76, 'validee', NULL, 100, 5.00, 2, NULL, NULL),
(1365, 1043, '2026-08-02 10:09:12' + INTERVAL @decalage DAY, 'Margaux', 'Morin', '64 rue Victor Hugo, 35000 Rennes', 43.70, 2.19, 0.00, 41.51, 41, 'annulee', '2026-08-03 10:09:12' + INTERVAL @decalage DAY, 0, 0.00, 1, 1, 'Adresse de livraison incomplète'),
(1366, 1043, '2026-10-20 10:02:02' + INTERVAL @decalage DAY, 'Margaux', 'Morin', '64 rue Victor Hugo, 35000 Rennes', 9.00, 0.00, 1.99, 10.99, 9, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1367, 1044, '2025-03-13 19:58:52' + INTERVAL @decalage DAY, 'Samuel', 'Mathieu', '92 rue de la République, 67000 Strasbourg', 24.70, 0.00, 0.00, 24.70, 24, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1368, 1044, '2025-03-23 11:06:30' + INTERVAL @decalage DAY, 'Samuel', 'Mathieu', '92 rue de la République, 67000 Strasbourg', 18.00, 0.00, 1.99, 19.99, 18, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1369, 1044, '2025-04-11 11:25:30' + INTERVAL @decalage DAY, 'Samuel', 'Mathieu', '92 rue de la République, 67000 Strasbourg', 8.30, 0.00, 1.99, 10.29, 8, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1370, 1044, '2025-04-24 12:09:14' + INTERVAL @decalage DAY, 'Samuel', 'Mathieu', '92 rue de la République, 67000 Strasbourg', 7.50, 0.00, 1.99, 9.49, 7, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1371, 1044, '2025-05-15 08:03:38' + INTERVAL @decalage DAY, 'Samuel', 'Mathieu', '92 rue de la République, 67000 Strasbourg', 19.20, 0.00, 1.99, 21.19, 19, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1372, 1044, '2025-05-23 08:26:11' + INTERVAL @decalage DAY, 'Samuel', 'Mathieu', '92 rue de la République, 67000 Strasbourg', 27.30, 0.00, 0.00, 22.30, 22, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1373, 1044, '2025-06-12 19:42:50' + INTERVAL @decalage DAY, 'Samuel', 'Mathieu', '92 rue de la République, 67000 Strasbourg', 7.10, 0.00, 1.99, 9.09, 7, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1374, 1044, '2025-08-08 20:59:06' + INTERVAL @decalage DAY, 'Samuel', 'Mathieu', '92 rue de la République, 67000 Strasbourg', 4.20, 0.00, 1.99, 6.19, 4, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1375, 1044, '2025-08-14 09:03:06' + INTERVAL @decalage DAY, 'Samuel', 'Mathieu', '92 rue de la République, 67000 Strasbourg', 7.60, 0.00, 1.99, 9.59, 7, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1376, 1044, '2025-08-15 14:50:09' + INTERVAL @decalage DAY, 'Samuel', 'Mathieu', '92 rue de la République, 67000 Strasbourg', 14.80, 0.00, 1.99, 16.79, 14, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1377, 1044, '2025-08-19 13:48:53' + INTERVAL @decalage DAY, 'Samuel', 'Mathieu', '92 rue de la République, 67000 Strasbourg', 23.20, 0.00, 0.00, 23.20, 23, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1378, 1044, '2025-09-07 09:36:10' + INTERVAL @decalage DAY, 'Samuel', 'Mathieu', '92 rue de la République, 67000 Strasbourg', 10.80, 0.00, 1.99, 12.79, 10, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1379, 1044, '2025-09-11 19:47:05' + INTERVAL @decalage DAY, 'Samuel', 'Mathieu', '92 rue de la République, 67000 Strasbourg', 3.20, 0.00, 1.99, 5.19, 3, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1380, 1044, '2025-09-17 22:08:08' + INTERVAL @decalage DAY, 'Samuel', 'Mathieu', '92 rue de la République, 67000 Strasbourg', 14.25, 0.00, 1.99, 16.24, 14, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1381, 1044, '2025-10-21 17:30:28' + INTERVAL @decalage DAY, 'Samuel', 'Mathieu', '92 rue de la République, 67000 Strasbourg', 70.70, 7.07, 0.00, 63.63, 63, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1382, 1044, '2025-12-13 08:14:30' + INTERVAL @decalage DAY, 'Samuel', 'Mathieu', '92 rue de la République, 67000 Strasbourg', 20.20, 0.00, 0.00, 20.20, 20, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1383, 1044, '2025-12-13 21:53:38' + INTERVAL @decalage DAY, 'Samuel', 'Mathieu', '92 rue de la République, 67000 Strasbourg', 12.50, 0.00, 1.99, 14.49, 12, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1384, 1044, '2025-12-23 20:39:46' + INTERVAL @decalage DAY, 'Samuel', 'Mathieu', '92 rue de la République, 67000 Strasbourg', 21.10, 0.00, 0.00, 21.10, 21, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1385, 1044, '2025-12-26 22:31:00' + INTERVAL @decalage DAY, 'Samuel', 'Mathieu', '92 rue de la République, 67000 Strasbourg', 22.90, 0.00, 0.00, 22.90, 22, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1386, 1044, '2026-02-09 18:45:05' + INTERVAL @decalage DAY, 'Samuel', 'Mathieu', '92 rue de la République, 67000 Strasbourg', 14.80, 0.00, 1.99, 16.79, 14, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1387, 1044, '2026-02-11 18:08:42' + INTERVAL @decalage DAY, 'Samuel', 'Mathieu', '92 rue de la République, 67000 Strasbourg', 3.60, 0.00, 1.99, 5.59, 3, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1388, 1044, '2026-02-22 19:29:06' + INTERVAL @decalage DAY, 'Samuel', 'Mathieu', '92 rue de la République, 67000 Strasbourg', 4.00, 0.00, 1.99, 5.99, 4, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1389, 1044, '2026-02-27 12:51:31' + INTERVAL @decalage DAY, 'Samuel', 'Mathieu', '92 rue de la République, 67000 Strasbourg', 6.00, 0.00, 1.99, 7.99, 6, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1390, 1044, '2026-02-27 22:59:11' + INTERVAL @decalage DAY, 'Samuel', 'Mathieu', '92 rue de la République, 67000 Strasbourg', 12.00, 0.00, 1.99, 13.99, 12, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1391, 1044, '2026-03-02 17:46:49' + INTERVAL @decalage DAY, 'Samuel', 'Mathieu', '92 rue de la République, 67000 Strasbourg', 14.70, 0.00, 1.99, 16.69, 14, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1392, 1044, '2026-03-18 10:07:52' + INTERVAL @decalage DAY, 'Samuel', 'Mathieu', '92 rue de la République, 67000 Strasbourg', 7.50, 0.00, 1.99, 9.49, 7, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1393, 1044, '2026-03-22 16:39:44' + INTERVAL @decalage DAY, 'Samuel', 'Mathieu', '92 rue de la République, 67000 Strasbourg', 19.30, 0.00, 1.99, 16.29, 14, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1394, 1044, '2026-03-24 21:33:16' + INTERVAL @decalage DAY, 'Samuel', 'Mathieu', '92 rue de la République, 67000 Strasbourg', 34.70, 1.74, 0.00, 32.96, 32, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1395, 1044, '2026-03-28 09:16:55' + INTERVAL @decalage DAY, 'Samuel', 'Mathieu', '92 rue de la République, 67000 Strasbourg', 14.30, 0.00, 1.99, 16.29, 14, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1396, 1044, '2026-03-31 19:10:31' + INTERVAL @decalage DAY, 'Samuel', 'Mathieu', '92 rue de la République, 67000 Strasbourg', 9.20, 0.00, 1.99, 11.19, 9, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1397, 1044, '2026-04-01 08:14:04' + INTERVAL @decalage DAY, 'Samuel', 'Mathieu', '92 rue de la République, 67000 Strasbourg', 22.20, 0.00, 0.00, 22.20, 22, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1398, 1044, '2026-04-15 14:37:36' + INTERVAL @decalage DAY, 'Samuel', 'Mathieu', '92 rue de la République, 67000 Strasbourg', 4.80, 0.00, 1.99, 6.79, 4, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1399, 1044, '2026-05-16 15:21:16' + INTERVAL @decalage DAY, 'Samuel', 'Mathieu', '92 rue de la République, 67000 Strasbourg', 4.10, 0.00, 1.99, 6.09, 4, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1400, 1044, '2026-05-24 21:59:02' + INTERVAL @decalage DAY, 'Samuel', 'Mathieu', '92 rue de la République, 67000 Strasbourg', 18.00, 0.00, 1.99, 19.99, 18, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1401, 1044, '2026-06-19 18:16:50' + INTERVAL @decalage DAY, 'Samuel', 'Mathieu', '92 rue de la République, 67000 Strasbourg', 4.80, 0.00, 1.99, 6.79, 4, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1402, 1044, '2026-07-02 17:45:26' + INTERVAL @decalage DAY, 'Samuel', 'Mathieu', '92 rue de la République, 67000 Strasbourg', 26.40, 0.00, 0.00, 15.90, 15, 'validee', NULL, 200, 10.50, 0, NULL, NULL),
(1403, 1044, '2026-07-08 18:44:31' + INTERVAL @decalage DAY, 'Samuel', 'Mathieu', '92 rue de la République, 67000 Strasbourg', 32.00, 1.60, 0.00, 19.90, 19, 'validee', NULL, 200, 10.50, 0, NULL, NULL),
(1404, 1044, '2026-07-28 17:00:35' + INTERVAL @decalage DAY, 'Samuel', 'Mathieu', '92 rue de la République, 67000 Strasbourg', 3.40, 0.00, 1.99, 5.39, 3, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1405, 1044, '2026-08-07 16:59:08' + INTERVAL @decalage DAY, 'Samuel', 'Mathieu', '92 rue de la République, 67000 Strasbourg', 33.90, 1.70, 0.00, 32.20, 32, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1406, 1044, '2026-08-12 13:22:59' + INTERVAL @decalage DAY, 'Samuel', 'Mathieu', '92 rue de la République, 67000 Strasbourg', 35.00, 1.75, 0.00, 28.25, 28, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1407, 1044, '2026-10-07 17:56:24' + INTERVAL @decalage DAY, 'Samuel', 'Mathieu', '92 rue de la République, 67000 Strasbourg', 11.20, 0.00, 1.99, 13.19, 11, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1408, 1044, '2026-10-08 22:20:44' + INTERVAL @decalage DAY, 'Samuel', 'Mathieu', '92 rue de la République, 67000 Strasbourg', 31.65, 1.58, 0.00, 30.07, 30, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1409, 1044, '2026-10-17 19:01:37' + INTERVAL @decalage DAY, 'Samuel', 'Mathieu', '92 rue de la République, 67000 Strasbourg', 16.60, 0.00, 1.99, 18.59, 16, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1410, 1045, '2025-07-08 13:36:46' + INTERVAL @decalage DAY, 'Elsa', 'Clement', '24 chemin des Vignes, 69003 Lyon', 25.00, 0.00, 0.00, 25.00, 25, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1411, 1045, '2025-07-21 14:51:13' + INTERVAL @decalage DAY, 'Elsa', 'Clement', '24 chemin des Vignes, 69003 Lyon', 8.60, 0.00, 1.99, 10.59, 8, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1412, 1045, '2025-07-22 13:47:35' + INTERVAL @decalage DAY, 'Elsa', 'Clement', '24 chemin des Vignes, 69003 Lyon', 5.50, 0.00, 1.99, 7.49, 5, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1413, 1045, '2025-08-01 13:35:37' + INTERVAL @decalage DAY, 'Elsa', 'Clement', '24 chemin des Vignes, 69003 Lyon', 5.60, 0.00, 1.99, 7.59, 5, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1414, 1045, '2025-08-19 13:24:53' + INTERVAL @decalage DAY, 'Elsa', 'Clement', '24 chemin des Vignes, 69003 Lyon', 9.90, 0.00, 1.99, 11.89, 9, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1415, 1045, '2025-08-30 16:14:01' + INTERVAL @decalage DAY, 'Elsa', 'Clement', '24 chemin des Vignes, 69003 Lyon', 13.15, 0.00, 1.99, 15.14, 13, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1416, 1045, '2025-09-29 09:54:40' + INTERVAL @decalage DAY, 'Elsa', 'Clement', '24 chemin des Vignes, 69003 Lyon', 7.50, 0.00, 1.99, 9.49, 7, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1417, 1045, '2025-10-12 17:48:59' + INTERVAL @decalage DAY, 'Elsa', 'Clement', '24 chemin des Vignes, 69003 Lyon', 9.20, 0.00, 1.99, 11.19, 9, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1418, 1045, '2025-10-27 14:00:18' + INTERVAL @decalage DAY, 'Elsa', 'Clement', '24 chemin des Vignes, 69003 Lyon', 7.20, 0.00, 1.99, 9.19, 7, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1419, 1045, '2025-11-03 18:58:02' + INTERVAL @decalage DAY, 'Elsa', 'Clement', '24 chemin des Vignes, 69003 Lyon', 23.20, 0.00, 0.00, 23.20, 23, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1420, 1045, '2025-11-26 21:09:46' + INTERVAL @decalage DAY, 'Elsa', 'Clement', '24 chemin des Vignes, 69003 Lyon', 8.20, 0.00, 1.99, 10.19, 8, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1421, 1045, '2026-01-19 14:04:58' + INTERVAL @decalage DAY, 'Elsa', 'Clement', '24 chemin des Vignes, 69003 Lyon', 15.30, 0.00, 1.99, 17.29, 15, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1422, 1045, '2026-01-27 08:44:48' + INTERVAL @decalage DAY, 'Elsa', 'Clement', '24 chemin des Vignes, 69003 Lyon', 44.80, 2.24, 0.00, 42.56, 42, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1423, 1045, '2026-03-05 14:42:59' + INTERVAL @decalage DAY, 'Elsa', 'Clement', '24 chemin des Vignes, 69003 Lyon', 4.90, 0.00, 1.99, 6.89, 4, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1424, 1045, '2026-03-16 14:01:46' + INTERVAL @decalage DAY, 'Elsa', 'Clement', '24 chemin des Vignes, 69003 Lyon', 15.45, 0.00, 1.99, 12.44, 10, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1425, 1045, '2026-03-17 20:38:45' + INTERVAL @decalage DAY, 'Elsa', 'Clement', '24 chemin des Vignes, 69003 Lyon', 4.10, 0.00, 1.99, 6.09, 4, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1426, 1045, '2026-03-23 13:22:02' + INTERVAL @decalage DAY, 'Elsa', 'Clement', '24 chemin des Vignes, 69003 Lyon', 5.60, 0.00, 1.99, 7.59, 5, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1427, 1045, '2026-05-23 14:05:02' + INTERVAL @decalage DAY, 'Elsa', 'Clement', '24 chemin des Vignes, 69003 Lyon', 11.30, 0.00, 1.99, 13.29, 11, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1428, 1045, '2026-05-26 20:25:00' + INTERVAL @decalage DAY, 'Elsa', 'Clement', '24 chemin des Vignes, 69003 Lyon', 17.00, 0.00, 1.99, 13.99, 12, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1429, 1045, '2026-05-31 12:13:00' + INTERVAL @decalage DAY, 'Elsa', 'Clement', '24 chemin des Vignes, 69003 Lyon', 20.80, 0.00, 0.00, 20.80, 20, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1430, 1045, '2026-07-10 21:48:43' + INTERVAL @decalage DAY, 'Elsa', 'Clement', '24 chemin des Vignes, 69003 Lyon', 38.20, 1.91, 0.00, 36.29, 36, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1431, 1045, '2026-07-18 09:00:06' + INTERVAL @decalage DAY, 'Elsa', 'Clement', '24 chemin des Vignes, 69003 Lyon', 10.25, 0.00, 1.99, 12.24, 10, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1432, 1045, '2026-07-21 08:00:56' + INTERVAL @decalage DAY, 'Elsa', 'Clement', '24 chemin des Vignes, 69003 Lyon', 14.50, 0.00, 1.99, 16.49, 14, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1433, 1045, '2026-07-28 20:05:12' + INTERVAL @decalage DAY, 'Elsa', 'Clement', '24 chemin des Vignes, 69003 Lyon', 19.15, 0.00, 1.99, 16.14, 14, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1434, 1045, '2026-08-07 15:17:45' + INTERVAL @decalage DAY, 'Elsa', 'Clement', '24 chemin des Vignes, 69003 Lyon', 19.55, 0.00, 1.99, 21.54, 19, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1435, 1045, '2026-08-24 12:49:09' + INTERVAL @decalage DAY, 'Elsa', 'Clement', '24 chemin des Vignes, 69003 Lyon', 21.45, 0.00, 0.00, 16.45, 16, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1436, 1045, '2026-09-10 19:48:46' + INTERVAL @decalage DAY, 'Elsa', 'Clement', '24 chemin des Vignes, 69003 Lyon', 22.80, 0.00, 0.00, 22.80, 22, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1437, 1045, '2026-10-12 18:40:00' + INTERVAL @decalage DAY, 'Elsa', 'Clement', '24 chemin des Vignes, 69003 Lyon', 106.30, 10.63, 0.00, 95.67, 95, 'validee', NULL, 0, 0.00, 2, NULL, NULL),
(1438, 1046, '2025-05-02 13:07:13' + INTERVAL @decalage DAY, 'Axel', 'Gauthier', '90 place Bellecour, 69007 Lyon', 68.40, 6.84, 0.00, 61.56, 61, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1439, 1046, '2025-05-08 17:13:46' + INTERVAL @decalage DAY, 'Axel', 'Gauthier', '90 place Bellecour, 69007 Lyon', 28.90, 0.00, 0.00, 28.90, 28, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1440, 1046, '2025-05-10 17:52:31' + INTERVAL @decalage DAY, 'Axel', 'Gauthier', '90 place Bellecour, 69007 Lyon', 4.50, 0.00, 1.99, 6.49, 4, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1441, 1046, '2025-06-14 21:02:25' + INTERVAL @decalage DAY, 'Axel', 'Gauthier', '90 place Bellecour, 69007 Lyon', 111.95, 11.20, 0.00, 100.75, 100, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1442, 1046, '2025-07-31 11:27:34' + INTERVAL @decalage DAY, 'Axel', 'Gauthier', '90 place Bellecour, 69007 Lyon', 27.90, 0.00, 0.00, 17.40, 17, 'validee', NULL, 200, 10.50, 0, NULL, NULL),
(1443, 1046, '2025-08-17 22:56:26' + INTERVAL @decalage DAY, 'Axel', 'Gauthier', '90 place Bellecour, 69007 Lyon', 56.80, 5.68, 0.00, 51.12, 51, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1444, 1046, '2025-09-06 14:18:35' + INTERVAL @decalage DAY, 'Axel', 'Gauthier', '90 place Bellecour, 69007 Lyon', 17.65, 0.00, 1.99, 14.64, 12, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1445, 1046, '2025-11-08 09:07:13' + INTERVAL @decalage DAY, 'Axel', 'Gauthier', '90 place Bellecour, 69007 Lyon', 23.90, 0.00, 0.00, 23.90, 23, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1446, 1046, '2025-11-28 12:09:58' + INTERVAL @decalage DAY, 'Axel', 'Gauthier', '90 place Bellecour, 69007 Lyon', 2.40, 0.00, 1.99, 4.39, 2, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1447, 1046, '2025-12-28 14:31:45' + INTERVAL @decalage DAY, 'Axel', 'Gauthier', '90 place Bellecour, 69007 Lyon', 4.50, 0.00, 1.99, 6.49, 4, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1448, 1046, '2026-01-08 10:01:40' + INTERVAL @decalage DAY, 'Axel', 'Gauthier', '90 place Bellecour, 69007 Lyon', 124.45, 12.45, 0.00, 112.00, 112, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1449, 1046, '2026-05-05 18:45:59' + INTERVAL @decalage DAY, 'Axel', 'Gauthier', '90 place Bellecour, 69007 Lyon', 22.40, 0.00, 0.00, 22.40, 22, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1450, 1046, '2026-05-25 20:08:45' + INTERVAL @decalage DAY, 'Axel', 'Gauthier', '90 place Bellecour, 69007 Lyon', 15.30, 0.00, 1.99, 17.29, 15, 'validee', NULL, 0, 0.00, 0, NULL, NULL);

INSERT INTO commande (id_commande, id_user, date_commande, prenom, nom, adresse, sous_total, remise, frais_livraison, total, points_gagnes, statut, date_annulation, points_utilises, reduction_points, bonus_niveau, id_annulateur, motif_annulation) VALUES
(1451, 1046, '2026-08-13 08:44:20' + INTERVAL @decalage DAY, 'Axel', 'Gauthier', '90 place Bellecour, 69007 Lyon', 6.00, 0.00, 1.99, 7.99, 6, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1452, 1046, '2026-09-14 10:04:50' + INTERVAL @decalage DAY, 'Axel', 'Gauthier', '90 place Bellecour, 69007 Lyon', 55.00, 5.50, 0.00, 49.50, 49, 'validee', NULL, 0, 0.00, 1, NULL, NULL),
(1453, 1047, '2025-04-20 10:15:15' + INTERVAL @decalage DAY, 'Agathe', 'Dumont', '80 boulevard de la Croix-Rousse, 69100 Villeurbanne', 79.00, 7.90, 0.00, 71.10, 71, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1454, 1047, '2025-05-01 20:52:26' + INTERVAL @decalage DAY, 'Agathe', 'Dumont', '80 boulevard de la Croix-Rousse, 69100 Villeurbanne', 144.10, 14.41, 0.00, 129.69, 129, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1455, 1047, '2025-05-28 10:08:47' + INTERVAL @decalage DAY, 'Agathe', 'Dumont', '80 boulevard de la Croix-Rousse, 69100 Villeurbanne', 29.40, 0.00, 0.00, 18.90, 18, 'validee', NULL, 200, 10.50, 0, NULL, NULL),
(1456, 1047, '2025-06-10 11:08:04' + INTERVAL @decalage DAY, 'Agathe', 'Dumont', '80 boulevard de la Croix-Rousse, 69100 Villeurbanne', 58.75, 5.88, 0.00, 52.87, 52, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1457, 1047, '2025-06-22 11:41:34' + INTERVAL @decalage DAY, 'Agathe', 'Dumont', '80 boulevard de la Croix-Rousse, 69100 Villeurbanne', 29.70, 0.00, 0.00, 29.70, 29, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1458, 1047, '2025-06-29 15:43:22' + INTERVAL @decalage DAY, 'Agathe', 'Dumont', '80 boulevard de la Croix-Rousse, 69100 Villeurbanne', 134.70, 13.47, 0.00, 116.23, 116, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1459, 1047, '2025-07-12 21:33:16' + INTERVAL @decalage DAY, 'Agathe', 'Dumont', '80 boulevard de la Croix-Rousse, 69100 Villeurbanne', 19.70, 0.00, 1.99, 21.69, 19, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1460, 1047, '2025-07-28 08:17:21' + INTERVAL @decalage DAY, 'Agathe', 'Dumont', '80 boulevard de la Croix-Rousse, 69100 Villeurbanne', 100.80, 10.08, 0.00, 85.72, 85, 'validee', NULL, 100, 5.00, 2, NULL, NULL),
(1461, 1047, '2025-09-10 16:30:03' + INTERVAL @decalage DAY, 'Agathe', 'Dumont', '80 boulevard de la Croix-Rousse, 69100 Villeurbanne', 32.10, 1.61, 0.00, 25.49, 25, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1462, 1047, '2025-12-30 21:15:22' + INTERVAL @decalage DAY, 'Agathe', 'Dumont', '80 boulevard de la Croix-Rousse, 69100 Villeurbanne', 132.60, 13.26, 0.00, 114.34, 114, 'validee', NULL, 100, 5.00, 3, NULL, NULL),
(1463, 1047, '2026-01-30 15:27:51' + INTERVAL @decalage DAY, 'Agathe', 'Dumont', '80 boulevard de la Croix-Rousse, 69100 Villeurbanne', 47.00, 2.35, 0.00, 44.65, 44, 'validee', NULL, 0, 0.00, 1, NULL, NULL),
(1464, 1047, '2026-01-30 20:26:58' + INTERVAL @decalage DAY, 'Agathe', 'Dumont', '80 boulevard de la Croix-Rousse, 69100 Villeurbanne', 114.95, 11.50, 0.00, 103.45, 103, 'validee', NULL, 0, 0.00, 3, NULL, NULL),
(1465, 1047, '2026-02-01 08:20:20' + INTERVAL @decalage DAY, 'Agathe', 'Dumont', '80 boulevard de la Croix-Rousse, 69100 Villeurbanne', 33.40, 1.67, 0.00, 31.73, 31, 'validee', NULL, 0, 0.00, 1, NULL, NULL),
(1466, 1047, '2026-03-07 19:42:27' + INTERVAL @decalage DAY, 'Agathe', 'Dumont', '80 boulevard de la Croix-Rousse, 69100 Villeurbanne', 25.00, 0.00, 0.00, 14.50, 14, 'validee', NULL, 200, 10.50, 0, NULL, NULL),
(1467, 1047, '2026-08-04 10:57:24' + INTERVAL @decalage DAY, 'Agathe', 'Dumont', '80 boulevard de la Croix-Rousse, 69100 Villeurbanne', 57.35, 5.74, 0.00, 51.61, 51, 'validee', NULL, 0, 0.00, 2, NULL, NULL),
(1468, 1047, '2026-09-22 17:30:50' + INTERVAL @decalage DAY, 'Agathe', 'Dumont', '80 boulevard de la Croix-Rousse, 69100 Villeurbanne', 49.80, 2.49, 0.00, 47.31, 47, 'validee', NULL, 0, 0.00, 2, NULL, NULL),
(1469, 1048, '2025-03-07 17:40:42' + INTERVAL @decalage DAY, 'Karim', 'Diallo', '98 avenue Jean Jaurès, 75011 Paris', 64.90, 6.49, 0.00, 58.41, 58, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1470, 1048, '2025-03-11 15:18:57' + INTERVAL @decalage DAY, 'Karim', 'Diallo', '98 avenue Jean Jaurès, 75011 Paris', 32.90, 1.65, 0.00, 31.25, 31, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1471, 1048, '2025-03-16 21:52:36' + INTERVAL @decalage DAY, 'Karim', 'Diallo', '98 avenue Jean Jaurès, 75011 Paris', 46.40, 2.32, 0.00, 44.08, 44, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1472, 1048, '2025-04-04 18:44:03' + INTERVAL @decalage DAY, 'Karim', 'Diallo', '98 avenue Jean Jaurès, 75011 Paris', 69.75, 6.98, 0.00, 62.77, 62, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1473, 1048, '2025-06-02 10:35:59' + INTERVAL @decalage DAY, 'Karim', 'Diallo', '98 avenue Jean Jaurès, 75011 Paris', 26.40, 0.00, 0.00, 15.90, 15, 'validee', NULL, 200, 10.50, 0, NULL, NULL),
(1474, 1048, '2025-06-18 20:07:15' + INTERVAL @decalage DAY, 'Karim', 'Diallo', '98 avenue Jean Jaurès, 75011 Paris', 24.50, 0.00, 0.00, 24.50, 24, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1475, 1048, '2025-08-15 22:50:07' + INTERVAL @decalage DAY, 'Karim', 'Diallo', '98 avenue Jean Jaurès, 75011 Paris', 44.00, 2.20, 0.00, 36.80, 36, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1476, 1048, '2025-08-22 18:10:07' + INTERVAL @decalage DAY, 'Karim', 'Diallo', '98 avenue Jean Jaurès, 75011 Paris', 14.20, 0.00, 1.99, 16.19, 14, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1477, 1048, '2025-09-14 19:10:59' + INTERVAL @decalage DAY, 'Karim', 'Diallo', '98 avenue Jean Jaurès, 75011 Paris', 30.40, 1.52, 0.00, 28.88, 28, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1478, 1048, '2025-09-18 12:52:46' + INTERVAL @decalage DAY, 'Karim', 'Diallo', '98 avenue Jean Jaurès, 75011 Paris', 24.80, 0.00, 0.00, 19.80, 19, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1479, 1048, '2025-09-20 12:27:59' + INTERVAL @decalage DAY, 'Karim', 'Diallo', '98 avenue Jean Jaurès, 75011 Paris', 26.20, 0.00, 0.00, 26.20, 26, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1480, 1048, '2025-10-06 22:04:46' + INTERVAL @decalage DAY, 'Karim', 'Diallo', '98 avenue Jean Jaurès, 75011 Paris', 14.25, 0.00, 1.99, 16.24, 14, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1481, 1048, '2025-10-18 22:59:37' + INTERVAL @decalage DAY, 'Karim', 'Diallo', '98 avenue Jean Jaurès, 75011 Paris', 36.30, 1.82, 0.00, 34.48, 34, 'validee', NULL, 0, 0.00, 1, NULL, NULL),
(1482, 1048, '2025-10-24 20:25:52' + INTERVAL @decalage DAY, 'Karim', 'Diallo', '98 avenue Jean Jaurès, 75011 Paris', 10.45, 0.00, 1.99, 12.44, 10, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1483, 1048, '2025-10-29 10:24:24' + INTERVAL @decalage DAY, 'Karim', 'Diallo', '98 avenue Jean Jaurès, 75011 Paris', 55.90, 5.59, 0.00, 50.31, 50, 'validee', NULL, 0, 0.00, 1, NULL, NULL),
(1484, 1048, '2025-11-02 08:48:00' + INTERVAL @decalage DAY, 'Karim', 'Diallo', '98 avenue Jean Jaurès, 75011 Paris', 32.80, 1.64, 0.00, 31.16, 31, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1485, 1048, '2025-12-19 08:27:33' + INTERVAL @decalage DAY, 'Karim', 'Diallo', '98 avenue Jean Jaurès, 75011 Paris', 28.70, 0.00, 0.00, 18.20, 18, 'validee', NULL, 200, 10.50, 0, NULL, NULL),
(1486, 1048, '2025-12-20 13:49:34' + INTERVAL @decalage DAY, 'Karim', 'Diallo', '98 avenue Jean Jaurès, 75011 Paris', 58.20, 5.82, 0.00, 52.38, 52, 'validee', NULL, 0, 0.00, 1, NULL, NULL),
(1487, 1048, '2025-12-23 19:17:40' + INTERVAL @decalage DAY, 'Karim', 'Diallo', '98 avenue Jean Jaurès, 75011 Paris', 50.00, 5.00, 0.00, 40.00, 40, 'validee', NULL, 100, 5.00, 1, NULL, NULL),
(1488, 1048, '2026-01-28 10:23:41' + INTERVAL @decalage DAY, 'Karim', 'Diallo', '98 avenue Jean Jaurès, 75011 Paris', 68.29, 6.83, 0.00, 61.46, 61, 'validee', NULL, 0, 0.00, 1, NULL, NULL),
(1489, 1048, '2026-02-21 10:39:49' + INTERVAL @decalage DAY, 'Karim', 'Diallo', '98 avenue Jean Jaurès, 75011 Paris', 25.40, 0.00, 0.00, 20.40, 20, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1490, 1048, '2026-03-18 14:44:04' + INTERVAL @decalage DAY, 'Karim', 'Diallo', '98 avenue Jean Jaurès, 75011 Paris', 50.00, 5.00, 0.00, 45.00, 45, 'validee', NULL, 0, 0.00, 1, NULL, NULL),
(1491, 1048, '2026-03-21 13:38:58' + INTERVAL @decalage DAY, 'Karim', 'Diallo', '98 avenue Jean Jaurès, 75011 Paris', 72.20, 7.22, 0.00, 64.98, 64, 'validee', NULL, 0, 0.00, 3, NULL, NULL),
(1492, 1048, '2026-04-03 17:39:47' + INTERVAL @decalage DAY, 'Karim', 'Diallo', '98 avenue Jean Jaurès, 75011 Paris', 36.30, 1.82, 0.00, 23.98, 23, 'validee', NULL, 200, 10.50, 1, NULL, NULL),
(1493, 1048, '2026-04-09 17:16:13' + INTERVAL @decalage DAY, 'Karim', 'Diallo', '98 avenue Jean Jaurès, 75011 Paris', 53.10, 5.31, 0.00, 47.79, 47, 'validee', NULL, 0, 0.00, 2, NULL, NULL),
(1494, 1048, '2026-05-01 09:44:16' + INTERVAL @decalage DAY, 'Karim', 'Diallo', '98 avenue Jean Jaurès, 75011 Paris', 19.20, 0.00, 0.00, 19.20, 19, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1495, 1048, '2026-05-03 17:41:16' + INTERVAL @decalage DAY, 'Karim', 'Diallo', '98 avenue Jean Jaurès, 75011 Paris', 46.20, 2.31, 0.00, 43.89, 43, 'validee', NULL, 0, 0.00, 2, NULL, NULL),
(1496, 1048, '2026-05-06 09:33:46' + INTERVAL @decalage DAY, 'Karim', 'Diallo', '98 avenue Jean Jaurès, 75011 Paris', 27.10, 0.00, 0.00, 22.10, 22, 'validee', NULL, 100, 5.00, 1, NULL, NULL),
(1497, 1048, '2026-05-20 21:54:36' + INTERVAL @decalage DAY, 'Karim', 'Diallo', '98 avenue Jean Jaurès, 75011 Paris', 22.60, 0.00, 0.00, 22.60, 22, 'validee', NULL, 0, 0.00, 1, NULL, NULL),
(1498, 1048, '2026-05-23 17:31:31' + INTERVAL @decalage DAY, 'Karim', 'Diallo', '98 avenue Jean Jaurès, 75011 Paris', 15.50, 0.00, 0.00, 15.50, 15, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1499, 1048, '2026-06-08 14:46:16' + INTERVAL @decalage DAY, 'Karim', 'Diallo', '98 avenue Jean Jaurès, 75011 Paris', 60.15, 6.02, 0.00, 49.13, 49, 'validee', NULL, 100, 5.00, 2, NULL, NULL),
(1500, 1048, '2026-06-12 20:04:46' + INTERVAL @decalage DAY, 'Karim', 'Diallo', '98 avenue Jean Jaurès, 75011 Paris', 82.60, 8.26, 0.00, 74.34, 74, 'validee', NULL, 0, 0.00, 3, NULL, NULL),
(1501, 1048, '2026-06-21 11:43:14' + INTERVAL @decalage DAY, 'Karim', 'Diallo', '98 avenue Jean Jaurès, 75011 Paris', 26.70, 0.00, 0.00, 26.70, 26, 'validee', NULL, 0, 0.00, 1, NULL, NULL),
(1502, 1048, '2026-07-07 19:45:42' + INTERVAL @decalage DAY, 'Karim', 'Diallo', '98 avenue Jean Jaurès, 75011 Paris', 37.70, 1.89, 0.00, 30.81, 30, 'validee', NULL, 100, 5.00, 1, NULL, NULL),
(1503, 1048, '2026-09-07 17:24:21' + INTERVAL @decalage DAY, 'Karim', 'Diallo', '98 avenue Jean Jaurès, 75011 Paris', 24.00, 0.00, 0.00, 24.00, 24, 'validee', NULL, 0, 0.00, 1, NULL, NULL),
(1504, 1048, '2026-09-09 10:35:32' + INTERVAL @decalage DAY, 'Karim', 'Diallo', '98 avenue Jean Jaurès, 75011 Paris', 29.60, 0.00, 0.00, 24.60, 24, 'validee', NULL, 100, 5.00, 1, NULL, NULL),
(1505, 1048, '2026-09-14 16:16:38' + INTERVAL @decalage DAY, 'Karim', 'Diallo', '98 avenue Jean Jaurès, 75011 Paris', 38.20, 1.91, 0.00, 36.29, 36, 'validee', NULL, 0, 0.00, 1, NULL, NULL),
(1506, 1049, '2025-01-04 14:53:23' + INTERVAL @decalage DAY, 'Fatou', 'Ndiaye', '79 avenue Jean Jaurès, 75018 Paris', 93.40, 9.34, 0.00, 84.06, 84, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1507, 1049, '2025-02-12 16:11:43' + INTERVAL @decalage DAY, 'Fatou', 'Ndiaye', '79 avenue Jean Jaurès, 75018 Paris', 10.00, 0.00, 1.99, 11.99, 10, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1508, 1049, '2025-03-12 15:52:31' + INTERVAL @decalage DAY, 'Fatou', 'Ndiaye', '79 avenue Jean Jaurès, 75018 Paris', 22.40, 0.00, 0.00, 22.40, 22, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1509, 1049, '2025-04-14 16:25:59' + INTERVAL @decalage DAY, 'Fatou', 'Ndiaye', '79 avenue Jean Jaurès, 75018 Paris', 50.40, 5.04, 0.00, 45.36, 45, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1510, 1049, '2025-04-25 22:30:12' + INTERVAL @decalage DAY, 'Fatou', 'Ndiaye', '79 avenue Jean Jaurès, 75018 Paris', 15.15, 0.00, 1.99, 12.14, 10, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1511, 1049, '2025-05-03 20:56:58' + INTERVAL @decalage DAY, 'Fatou', 'Ndiaye', '79 avenue Jean Jaurès, 75018 Paris', 114.95, 11.50, 0.00, 98.45, 98, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1512, 1049, '2025-05-30 10:03:01' + INTERVAL @decalage DAY, 'Fatou', 'Ndiaye', '79 avenue Jean Jaurès, 75018 Paris', 21.60, 0.00, 0.00, 21.60, 21, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1513, 1049, '2025-07-17 17:01:59' + INTERVAL @decalage DAY, 'Fatou', 'Ndiaye', '79 avenue Jean Jaurès, 75018 Paris', 104.00, 10.40, 0.00, 93.60, 93, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1514, 1049, '2025-07-21 16:17:36' + INTERVAL @decalage DAY, 'Fatou', 'Ndiaye', '79 avenue Jean Jaurès, 75018 Paris', 13.69, 0.00, 1.99, 15.68, 13, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1515, 1049, '2025-08-05 21:15:59' + INTERVAL @decalage DAY, 'Fatou', 'Ndiaye', '79 avenue Jean Jaurès, 75018 Paris', 72.70, 7.27, 0.00, 65.43, 65, 'validee', NULL, 0, 0.00, 1, NULL, NULL),
(1516, 1049, '2025-09-17 19:12:42' + INTERVAL @decalage DAY, 'Fatou', 'Ndiaye', '79 avenue Jean Jaurès, 75018 Paris', 72.60, 7.26, 0.00, 65.34, 65, 'validee', NULL, 0, 0.00, 1, NULL, NULL),
(1517, 1049, '2025-10-15 18:48:32' + INTERVAL @decalage DAY, 'Fatou', 'Ndiaye', '79 avenue Jean Jaurès, 75018 Paris', 45.40, 2.27, 0.00, 43.13, 43, 'validee', NULL, 0, 0.00, 1, NULL, NULL),
(1518, 1049, '2025-11-10 09:25:28' + INTERVAL @decalage DAY, 'Fatou', 'Ndiaye', '79 avenue Jean Jaurès, 75018 Paris', 103.00, 10.30, 0.00, 92.70, 92, 'validee', NULL, 0, 0.00, 2, NULL, NULL),
(1519, 1049, '2025-11-12 17:49:20' + INTERVAL @decalage DAY, 'Fatou', 'Ndiaye', '79 avenue Jean Jaurès, 75018 Paris', 27.60, 0.00, 0.00, 27.60, 27, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1520, 1049, '2025-12-27 09:04:02' + INTERVAL @decalage DAY, 'Fatou', 'Ndiaye', '79 avenue Jean Jaurès, 75018 Paris', 91.00, 9.10, 0.00, 60.40, 60, 'validee', NULL, 400, 21.50, 1, NULL, NULL),
(1521, 1049, '2026-02-06 20:47:40' + INTERVAL @decalage DAY, 'Fatou', 'Ndiaye', '79 avenue Jean Jaurès, 75018 Paris', 62.60, 6.26, 0.00, 56.34, 56, 'validee', NULL, 0, 0.00, 2, NULL, NULL),
(1522, 1049, '2026-04-04 10:26:41' + INTERVAL @decalage DAY, 'Fatou', 'Ndiaye', '79 avenue Jean Jaurès, 75018 Paris', 86.10, 8.61, 0.00, 77.49, 77, 'validee', NULL, 0, 0.00, 3, NULL, NULL),
(1523, 1049, '2026-04-16 08:53:10' + INTERVAL @decalage DAY, 'Fatou', 'Ndiaye', '79 avenue Jean Jaurès, 75018 Paris', 28.90, 0.00, 0.00, 28.90, 28, 'validee', NULL, 0, 0.00, 1, NULL, NULL),
(1524, 1049, '2026-04-20 21:32:52' + INTERVAL @decalage DAY, 'Fatou', 'Ndiaye', '79 avenue Jean Jaurès, 75018 Paris', 45.90, 2.30, 0.00, 43.60, 43, 'validee', NULL, 0, 0.00, 2, NULL, NULL),
(1525, 1049, '2026-05-24 18:33:05' + INTERVAL @decalage DAY, 'Fatou', 'Ndiaye', '79 avenue Jean Jaurès, 75018 Paris', 58.10, 5.81, 0.00, 52.29, 52, 'validee', NULL, 0, 0.00, 2, NULL, NULL),
(1526, 1049, '2026-06-11 14:59:41' + INTERVAL @decalage DAY, 'Fatou', 'Ndiaye', '79 avenue Jean Jaurès, 75018 Paris', 57.50, 5.75, 0.00, 51.75, 51, 'validee', NULL, 0, 0.00, 2, NULL, NULL),
(1527, 1049, '2026-07-31 19:42:58' + INTERVAL @decalage DAY, 'Fatou', 'Ndiaye', '79 avenue Jean Jaurès, 75018 Paris', 36.70, 1.84, 0.00, 34.86, 34, 'validee', NULL, 0, 0.00, 1, NULL, NULL),
(1528, 1049, '2026-08-20 15:48:30' + INTERVAL @decalage DAY, 'Fatou', 'Ndiaye', '79 avenue Jean Jaurès, 75018 Paris', 63.09, 6.31, 0.00, 56.78, 56, 'validee', NULL, 0, 0.00, 2, NULL, NULL),
(1529, 1050, '2024-09-10 12:42:48' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 39.90, 2.00, 0.00, 37.90, 37, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1530, 1050, '2024-09-10 18:39:07' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 13.30, 0.00, 1.99, 15.29, 13, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1531, 1050, '2024-09-17 12:48:09' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 27.50, 0.00, 0.00, 27.50, 27, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1532, 1050, '2024-09-22 13:40:40' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 76.25, 7.63, 0.00, 68.62, 68, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1533, 1050, '2024-10-27 14:24:27' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 25.90, 0.00, 0.00, 15.40, 15, 'validee', NULL, 200, 10.50, 0, NULL, NULL),
(1534, 1050, '2024-12-09 08:16:04' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 10.20, 0.00, 1.99, 12.19, 10, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1535, 1050, '2024-12-12 17:30:10' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 18.80, 0.00, 1.99, 20.79, 18, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1536, 1050, '2024-12-16 11:22:21' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 6.20, 0.00, 1.99, 8.19, 6, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1537, 1050, '2024-12-22 13:40:17' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 10.00, 0.00, 1.99, 11.99, 10, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1538, 1050, '2024-12-23 18:35:11' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 28.80, 0.00, 0.00, 23.80, 23, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1539, 1050, '2025-01-07 20:48:09' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 50.30, 5.03, 0.00, 45.27, 45, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1540, 1050, '2025-01-10 13:07:42' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 66.70, 6.67, 0.00, 60.03, 60, 'validee', NULL, 0, 0.00, 1, NULL, NULL),
(1541, 1050, '2025-01-10 16:52:49' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 20.60, 0.00, 0.00, 20.60, 20, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1542, 1050, '2025-01-12 12:16:04' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 20.80, 0.00, 0.00, 20.80, 20, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1543, 1050, '2025-01-21 13:45:41' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 18.80, 0.00, 1.99, 15.79, 13, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1544, 1050, '2025-03-02 19:25:52' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 22.30, 0.00, 0.00, 17.30, 17, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1545, 1050, '2025-03-06 16:26:46' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 23.60, 0.00, 0.00, 23.60, 23, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1546, 1050, '2025-03-09 10:02:06' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 5.50, 0.00, 1.99, 7.49, 5, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1547, 1050, '2025-03-23 20:31:48' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 82.60, 8.26, 0.00, 69.34, 69, 'validee', NULL, 100, 5.00, 2, NULL, NULL),
(1548, 1050, '2025-04-11 17:30:55' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 30.90, 1.55, 0.00, 29.35, 29, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1549, 1050, '2025-04-22 15:55:14' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 9.80, 0.00, 1.99, 11.79, 9, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1550, 1050, '2025-04-24 15:44:53' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 23.00, 0.00, 0.00, 23.00, 23, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1551, 1050, '2025-05-24 12:47:22' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 13.90, 0.00, 1.99, 15.89, 13, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1552, 1050, '2025-06-06 13:54:42' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 20.10, 0.00, 0.00, 15.10, 15, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1553, 1050, '2025-06-16 14:02:57' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 17.85, 0.00, 1.99, 19.84, 17, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1554, 1050, '2025-07-16 13:33:19' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 8.70, 0.00, 1.99, 10.69, 8, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1555, 1050, '2025-07-28 18:06:21' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 38.10, 1.91, 0.00, 36.19, 36, 'validee', NULL, 0, 0.00, 1, NULL, NULL),
(1556, 1050, '2025-08-20 20:40:49' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 48.00, 2.40, 0.00, 40.60, 40, 'validee', NULL, 100, 5.00, 1, NULL, NULL),
(1557, 1050, '2025-09-02 21:11:20' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 6.90, 0.00, 1.99, 8.89, 6, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1558, 1050, '2025-09-07 12:46:37' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 16.10, 0.00, 0.00, 16.10, 16, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1559, 1050, '2025-09-10 08:32:24' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 30.00, 1.50, 0.00, 28.50, 28, 'validee', NULL, 0, 0.00, 1, NULL, NULL),
(1560, 1050, '2025-09-10 13:30:43' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 30.60, 1.53, 0.00, 29.07, 29, 'validee', NULL, 0, 0.00, 1, NULL, NULL),
(1561, 1050, '2025-09-13 11:59:39' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 12.50, 0.00, 1.99, 14.49, 12, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1562, 1050, '2025-09-17 13:41:31' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 20.40, 0.00, 0.00, 15.40, 15, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1563, 1050, '2025-09-21 10:35:10' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 7.90, 0.00, 1.99, 9.89, 7, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1564, 1050, '2025-09-21 12:26:36' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 23.60, 0.00, 0.00, 23.60, 23, 'validee', NULL, 0, 0.00, 1, NULL, NULL),
(1565, 1050, '2025-09-22 20:28:01' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 26.40, 0.00, 0.00, 26.40, 26, 'validee', NULL, 0, 0.00, 1, NULL, NULL),
(1566, 1050, '2025-09-30 20:45:08' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 39.70, 1.99, 0.00, 37.71, 37, 'validee', NULL, 0, 0.00, 1, NULL, NULL),
(1567, 1050, '2025-10-04 20:15:06' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 82.80, 8.28, 0.00, 64.02, 64, 'validee', NULL, 200, 10.50, 3, NULL, NULL),
(1568, 1050, '2025-10-12 09:38:18' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 26.00, 0.00, 0.00, 26.00, 26, 'validee', NULL, 0, 0.00, 1, NULL, NULL),
(1569, 1050, '2025-10-26 10:14:38' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 26.25, 0.00, 0.00, 26.25, 26, 'validee', NULL, 0, 0.00, 1, NULL, NULL),
(1570, 1050, '2025-11-08 11:11:15' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 48.50, 2.43, 0.00, 41.07, 41, 'validee', NULL, 100, 5.00, 2, NULL, NULL),
(1571, 1050, '2025-11-11 12:37:27' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 15.60, 0.00, 0.00, 15.60, 15, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1572, 1050, '2025-12-06 14:16:40' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 19.90, 0.00, 0.00, 19.90, 19, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1573, 1050, '2025-12-10 18:04:44' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 17.30, 0.00, 0.00, 12.30, 12, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1574, 1050, '2025-12-18 15:17:36' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 21.30, 0.00, 0.00, 21.30, 21, 'validee', NULL, 0, 0.00, 1, NULL, NULL),
(1575, 1050, '2026-01-06 12:43:36' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 31.90, 1.60, 0.00, 30.30, 30, 'validee', NULL, 0, 0.00, 1, NULL, NULL),
(1576, 1050, '2026-01-08 11:29:05' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 14.00, 0.00, 1.99, 15.99, 14, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1577, 1050, '2026-01-10 18:37:59' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 16.60, 0.00, 0.00, 16.60, 16, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1578, 1050, '2026-01-13 16:49:42' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 13.80, 0.00, 1.99, 15.79, 13, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1579, 1050, '2026-01-19 20:52:54' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 7.30, 0.00, 1.99, 9.29, 7, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1580, 1050, '2026-01-28 08:05:23' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 36.10, 1.81, 0.00, 29.29, 29, 'validee', NULL, 100, 5.00, 1, NULL, NULL),
(1581, 1050, '2026-02-15 15:18:00' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 40.90, 2.05, 0.00, 38.85, 38, 'validee', NULL, 0, 0.00, 1, NULL, NULL),
(1582, 1050, '2026-03-10 11:58:48' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 46.90, 2.35, 0.00, 44.55, 44, 'validee', NULL, 0, 0.00, 2, NULL, NULL),
(1583, 1050, '2026-03-26 08:29:08' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 49.00, 2.45, 0.00, 41.55, 41, 'validee', NULL, 100, 5.00, 2, NULL, NULL),
(1584, 1050, '2026-04-06 08:29:06' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 22.50, 0.00, 0.00, 22.50, 22, 'validee', NULL, 0, 0.00, 1, NULL, NULL),
(1585, 1050, '2026-05-17 22:49:49' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 6.60, 0.00, 1.99, 8.59, 6, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1586, 1050, '2026-06-02 12:51:53' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 30.30, 1.52, 0.00, 28.78, 28, 'validee', NULL, 0, 0.00, 1, NULL, NULL),
(1587, 1050, '2026-06-02 19:41:27' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 12.70, 0.00, 1.99, 14.69, 12, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1588, 1050, '2026-06-12 09:39:04' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 52.90, 5.29, 0.00, 42.61, 42, 'validee', NULL, 100, 5.00, 2, NULL, NULL),
(1589, 1050, '2026-06-25 09:26:40' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 18.40, 0.00, 0.00, 13.40, 13, 'validee', NULL, 100, 5.00, 0, NULL, NULL),
(1590, 1050, '2026-07-10 17:29:23' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 11.80, 0.00, 1.99, 13.79, 11, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1591, 1050, '2026-07-27 09:41:09' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 22.50, 0.00, 0.00, 22.50, 22, 'validee', NULL, 0, 0.00, 1, NULL, NULL),
(1592, 1050, '2026-08-13 19:28:09' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 15.00, 0.00, 0.00, 15.00, 15, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1593, 1050, '2026-08-21 16:52:43' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 27.80, 0.00, 0.00, 27.80, 27, 'validee', NULL, 0, 0.00, 1, NULL, NULL),
(1594, 1050, '2026-08-23 17:10:55' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 13.60, 0.00, 1.99, 15.59, 13, 'validee', NULL, 0, 0.00, 0, NULL, NULL),
(1595, 1050, '2026-09-09 12:46:40' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 51.75, 5.18, 0.00, 46.57, 46, 'validee', NULL, 0, 0.00, 3, NULL, NULL),
(1596, 1050, '2026-09-28 13:59:41' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 13.70, 0.00, 1.99, 15.69, 13, 'validee', NULL, 0, 0.00, 1, NULL, NULL),
(1597, 1050, '2026-09-30 22:44:25' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 13.70, 0.00, 1.99, 15.69, 13, 'validee', NULL, 0, 0.00, 1, NULL, NULL),
(1598, 1050, '2026-10-23 08:05:12' + INTERVAL @decalage DAY, 'Mehdi', 'Benali', '3 rue des Lilas, 59000 Lille', 42.00, 2.10, 0.00, 34.90, 34, 'validee', NULL, 100, 5.00, 2, NULL, NULL);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1007, 6, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 6 AND etat = 'bon'), 'Le Problème à trois corps', 'bon', 4.70, 2 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 6);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1011, 3, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 3 AND etat = 'tres bon'), 'Bleach - Tome 01', 'tres bon', 4.30, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 3);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1012, 2, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 2 AND etat = 'tres bon'), 'Jujutsu Kaisen - Tome 22', 'tres bon', 4.30, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 2);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1014, 4, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 4 AND etat = 'neuf'), 'L''Étranger', 'neuf', 7.50, 2 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 4);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1019, 9, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 9 AND etat = 'neuf'), 'I Am Not Your Negro', 'neuf', 7.60, 2 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 9);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1020, 10, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 10 AND etat = 'neuf'), 'Absolute Batman - Tome 2', 'neuf', 25.00, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 10);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1021, 11, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 11 AND etat = 'bon'), 'Jujutsu Kaisen - Tome 30', 'bon', 3.10, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 11);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1022, 2, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 2 AND etat = 'tres bon'), 'Jujutsu Kaisen - Tome 22', 'tres bon', 4.30, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 2);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1024, 7, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 7 AND etat = 'bon'), 'L''Arabe du futur - Volume 6', 'bon', 10.80, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 7);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1025, 10, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 10 AND etat = 'tres bon'), 'Absolute Batman - Tome 2', 'tres bon', 15.00, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 10);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1025, 11, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 11 AND etat = 'bon'), 'Jujutsu Kaisen - Tome 30', 'bon', 3.10, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 11);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1026, 3, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 3 AND etat = 'use'), 'Bleach - Tome 01', 'use', 2.10, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 3);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1029, 10, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 10 AND etat = 'use'), 'Absolute Batman - Tome 2', 'use', 7.50, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 10);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1030, 2, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 2 AND etat = 'neuf'), 'Jujutsu Kaisen - Tome 22', 'neuf', 7.10, 2 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 2);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1036, 8, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 8 AND etat = 'use'), 'La Chronique des Bridgerton - Tomes 1 & 2', 'use', 4.80, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 8);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1036, 11, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 11 AND etat = 'bon'), 'Jujutsu Kaisen - Tome 30', 'bon', 3.10, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 11);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1040, 6, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 6 AND etat = 'neuf'), 'Le Problème à trois corps', 'neuf', 10.50, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 6);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1042, 3, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 3 AND etat = 'use'), 'Bleach - Tome 01', 'use', 2.10, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 3);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1045, 10, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 10 AND etat = 'neuf'), 'Absolute Batman - Tome 2', 'neuf', 25.00, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 10);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1046, 2, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 2 AND etat = 'tres bon'), 'Jujutsu Kaisen - Tome 22', 'tres bon', 4.30, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 2);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1047, 1, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 1 AND etat = 'bon'), 'Harry Potter et la Coupe de Feu', 'bon', 5.20, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 1);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1048, 4, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 4 AND etat = 'bon'), 'L''Étranger', 'bon', 3.40, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 4);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1053, 9, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 9 AND etat = 'tres bon'), 'I Am Not Your Negro', 'tres bon', 4.60, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 9);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1057, 4, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 4 AND etat = 'neuf'), 'L''Étranger', 'neuf', 7.50, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 4);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1059, 6, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 6 AND etat = 'neuf'), 'Le Problème à trois corps', 'neuf', 10.50, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 6);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1060, 11, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 11 AND etat = 'use'), 'Jujutsu Kaisen - Tome 30', 'use', 2.10, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 11);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1062, 11, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 11 AND etat = 'bon'), 'Jujutsu Kaisen - Tome 30', 'bon', 3.10, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 11);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1064, 7, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 7 AND etat = 'neuf'), 'L''Arabe du futur - Volume 6', 'neuf', 23.90, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 7);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1065, 7, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 7 AND etat = 'neuf'), 'L''Arabe du futur - Volume 6', 'neuf', 23.90, 2 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 7);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1067, 2, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 2 AND etat = 'bon'), 'Jujutsu Kaisen - Tome 22', 'bon', 3.20, 2 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 2);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1069, 9, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 9 AND etat = 'neuf'), 'I Am Not Your Negro', 'neuf', 7.60, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 9);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1069, 11, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 11 AND etat = 'use'), 'Jujutsu Kaisen - Tome 30', 'use', 2.10, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 11);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1069, 1, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 1 AND etat = 'use'), 'Harry Potter et la Coupe de Feu', 'use', 3.50, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 1);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1073, 9, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 9 AND etat = 'neuf'), 'I Am Not Your Negro', 'neuf', 7.60, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 9);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1076, 7, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 7 AND etat = 'neuf'), 'L''Arabe du futur - Volume 6', 'neuf', 23.90, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 7);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1079, 1, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 1 AND etat = 'use'), 'Harry Potter et la Coupe de Feu', 'use', 3.50, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 1);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1079, 2, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 2 AND etat = 'neuf'), 'Jujutsu Kaisen - Tome 22', 'neuf', 7.10, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 2);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1080, 1, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 1 AND etat = 'bon'), 'Harry Potter et la Coupe de Feu', 'bon', 5.20, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 1);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1081, 11, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 11 AND etat = 'bon'), 'Jujutsu Kaisen - Tome 30', 'bon', 3.10, 2 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 11);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1088, 8, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 8 AND etat = 'tres bon'), 'La Chronique des Bridgerton - Tomes 1 & 2', 'tres bon', 9.60, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 8);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1088, 4, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 4 AND etat = 'neuf'), 'L''Étranger', 'neuf', 7.50, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 4);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1089, 10, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 10 AND etat = 'neuf'), 'Absolute Batman - Tome 2', 'neuf', 25.00, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 10);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1090, 6, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 6 AND etat = 'tres bon'), 'Le Problème à trois corps', 'tres bon', 6.30, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 6);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1095, 4, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 4 AND etat = 'tres bon'), 'L''Étranger', 'tres bon', 4.50, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 4);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1097, 6, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 6 AND etat = 'bon'), 'Le Problème à trois corps', 'bon', 4.70, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 6);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1097, 8, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 8 AND etat = 'bon'), 'La Chronique des Bridgerton - Tomes 1 & 2', 'bon', 7.20, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 8);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1097, 5, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 5 AND etat = 'neuf'), 'Thérèse Raquin', 'neuf', 3.95, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 5);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1098, 6, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 6 AND etat = 'tres bon'), 'Le Problème à trois corps', 'tres bon', 6.30, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 6);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1100, 10, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 10 AND etat = 'neuf'), 'Absolute Batman - Tome 2', 'neuf', 25.00, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 10);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1102, 4, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 4 AND etat = 'use'), 'L''Étranger', 'use', 2.30, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 4);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1104, 1, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 1 AND etat = 'tres bon'), 'Harry Potter et la Coupe de Feu', 'tres bon', 6.90, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 1);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1112, 9, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 9 AND etat = 'tres bon'), 'I Am Not Your Negro', 'tres bon', 4.60, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 9);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1113, 11, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 11 AND etat = 'neuf'), 'Jujutsu Kaisen - Tome 30', 'neuf', 6.95, 2 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 11);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1114, 8, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 8 AND etat = 'neuf'), 'La Chronique des Bridgerton - Tomes 1 & 2', 'neuf', 16.00, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 8);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1115, 6, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 6 AND etat = 'tres bon'), 'Le Problème à trois corps', 'tres bon', 6.30, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 6);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1117, 6, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 6 AND etat = 'bon'), 'Le Problème à trois corps', 'bon', 4.70, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 6);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1119, 4, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 4 AND etat = 'neuf'), 'L''Étranger', 'neuf', 7.50, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 4);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1120, 5, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 5 AND etat = 'neuf'), 'Thérèse Raquin', 'neuf', 3.95, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 5);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1128, 1, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 1 AND etat = 'tres bon'), 'Harry Potter et la Coupe de Feu', 'tres bon', 6.90, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 1);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1139, 3, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 3 AND etat = 'tres bon'), 'Bleach - Tome 01', 'tres bon', 4.30, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 3);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1142, 7, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 7 AND etat = 'neuf'), 'L''Arabe du futur - Volume 6', 'neuf', 23.90, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 7);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1143, 3, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 3 AND etat = 'tres bon'), 'Bleach - Tome 01', 'tres bon', 4.30, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 3);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1144, 3, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 3 AND etat = 'tres bon'), 'Bleach - Tome 01', 'tres bon', 4.30, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 3);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1152, 6, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 6 AND etat = 'neuf'), 'Le Problème à trois corps', 'neuf', 10.50, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 6);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1154, 4, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 4 AND etat = 'use'), 'L''Étranger', 'use', 2.30, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 4);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1156, 6, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 6 AND etat = 'bon'), 'Le Problème à trois corps', 'bon', 4.70, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 6);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1159, 1, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 1 AND etat = 'use'), 'Harry Potter et la Coupe de Feu', 'use', 3.50, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 1);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1159, 3, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 3 AND etat = 'tres bon'), 'Bleach - Tome 01', 'tres bon', 4.30, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 3);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1165, 2, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 2 AND etat = 'use'), 'Jujutsu Kaisen - Tome 22', 'use', 2.10, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 2);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1169, 9, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 9 AND etat = 'tres bon'), 'I Am Not Your Negro', 'tres bon', 4.60, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 9);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1170, 9, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 9 AND etat = 'use'), 'I Am Not Your Negro', 'use', 2.30, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 9);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1170, 11, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 11 AND etat = 'tres bon'), 'Jujutsu Kaisen - Tome 30', 'tres bon', 4.20, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 11);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1173, 2, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 2 AND etat = 'bon'), 'Jujutsu Kaisen - Tome 22', 'bon', 3.20, 2 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 2);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1174, 9, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 9 AND etat = 'neuf'), 'I Am Not Your Negro', 'neuf', 7.60, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 9);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1178, 8, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 8 AND etat = 'bon'), 'La Chronique des Bridgerton - Tomes 1 & 2', 'bon', 7.20, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 8);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1183, 8, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 8 AND etat = 'neuf'), 'La Chronique des Bridgerton - Tomes 1 & 2', 'neuf', 16.00, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 8);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1183, 5, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 5 AND etat = 'neuf'), 'Thérèse Raquin', 'neuf', 3.95, 2 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 5);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1184, 8, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 8 AND etat = 'bon'), 'La Chronique des Bridgerton - Tomes 1 & 2', 'bon', 7.20, 2 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 8);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1186, 4, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 4 AND etat = 'tres bon'), 'L''Étranger', 'tres bon', 4.50, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 4);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1186, 8, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 8 AND etat = 'bon'), 'La Chronique des Bridgerton - Tomes 1 & 2', 'bon', 7.20, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 8);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1188, 4, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 4 AND etat = 'bon'), 'L''Étranger', 'bon', 3.40, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 4);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1188, 7, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 7 AND etat = 'tres bon'), 'L''Arabe du futur - Volume 6', 'tres bon', 14.30, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 7);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1189, 3, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 3 AND etat = 'neuf'), 'Bleach - Tome 01', 'neuf', 7.10, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 3);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1190, 2, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 2 AND etat = 'tres bon'), 'Jujutsu Kaisen - Tome 22', 'tres bon', 4.30, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 2);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1192, 10, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 10 AND etat = 'tres bon'), 'Absolute Batman - Tome 2', 'tres bon', 15.00, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 10);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1196, 3, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 3 AND etat = 'use'), 'Bleach - Tome 01', 'use', 2.10, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 3);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1198, 11, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 11 AND etat = 'bon'), 'Jujutsu Kaisen - Tome 30', 'bon', 3.10, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 11);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1200, 11, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 11 AND etat = 'tres bon'), 'Jujutsu Kaisen - Tome 30', 'tres bon', 4.20, 2 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 11);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1201, 11, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 11 AND etat = 'tres bon'), 'Jujutsu Kaisen - Tome 30', 'tres bon', 4.20, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 11);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1202, 3, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 3 AND etat = 'tres bon'), 'Bleach - Tome 01', 'tres bon', 4.30, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 3);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1204, 2, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 2 AND etat = 'neuf'), 'Jujutsu Kaisen - Tome 22', 'neuf', 7.10, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 2);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1205, 7, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 7 AND etat = 'bon'), 'L''Arabe du futur - Volume 6', 'bon', 10.80, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 7);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1206, 11, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 11 AND etat = 'tres bon'), 'Jujutsu Kaisen - Tome 30', 'tres bon', 4.20, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 11);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1207, 3, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 3 AND etat = 'neuf'), 'Bleach - Tome 01', 'neuf', 7.10, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 3);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1207, 11, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 11 AND etat = 'bon'), 'Jujutsu Kaisen - Tome 30', 'bon', 3.10, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 11);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1209, 3, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 3 AND etat = 'tres bon'), 'Bleach - Tome 01', 'tres bon', 4.30, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 3);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1211, 10, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 10 AND etat = 'bon'), 'Absolute Batman - Tome 2', 'bon', 11.30, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 10);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1213, 11, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 11 AND etat = 'neuf'), 'Jujutsu Kaisen - Tome 30', 'neuf', 6.95, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 11);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1215, 1, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 1 AND etat = 'tres bon'), 'Harry Potter et la Coupe de Feu', 'tres bon', 6.90, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 1);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1217, 1, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 1 AND etat = 'use'), 'Harry Potter et la Coupe de Feu', 'use', 3.50, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 1);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1220, 8, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 8 AND etat = 'use'), 'La Chronique des Bridgerton - Tomes 1 & 2', 'use', 4.80, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 8);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1221, 7, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 7 AND etat = 'bon'), 'L''Arabe du futur - Volume 6', 'bon', 10.80, 2 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 7);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1230, 2, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 2 AND etat = 'tres bon'), 'Jujutsu Kaisen - Tome 22', 'tres bon', 4.30, 2 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 2);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1236, 4, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 4 AND etat = 'bon'), 'L''Étranger', 'bon', 3.40, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 4);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1254, 8, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 8 AND etat = 'tres bon'), 'La Chronique des Bridgerton - Tomes 1 & 2', 'tres bon', 9.60, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 8);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1255, 5, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 5 AND etat = 'tres bon'), 'Thérèse Raquin', 'tres bon', 2.40, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 5);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1256, 6, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 6 AND etat = 'tres bon'), 'Le Problème à trois corps', 'tres bon', 6.30, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 6);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1276, 3, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 3 AND etat = 'neuf'), 'Bleach - Tome 01', 'neuf', 7.10, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 3);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1278, 10, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 10 AND etat = 'bon'), 'Absolute Batman - Tome 2', 'bon', 11.30, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 10);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1279, 11, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 11 AND etat = 'bon'), 'Jujutsu Kaisen - Tome 30', 'bon', 3.10, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 11);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1280, 7, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 7 AND etat = 'neuf'), 'L''Arabe du futur - Volume 6', 'neuf', 23.90, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 7);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1281, 1, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 1 AND etat = 'use'), 'Harry Potter et la Coupe de Feu', 'use', 3.50, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 1);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1282, 3, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 3 AND etat = 'tres bon'), 'Bleach - Tome 01', 'tres bon', 4.30, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 3);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1282, 11, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 11 AND etat = 'neuf'), 'Jujutsu Kaisen - Tome 30', 'neuf', 6.95, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 11);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1285, 1, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 1 AND etat = 'bon'), 'Harry Potter et la Coupe de Feu', 'bon', 5.20, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 1);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1287, 3, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 3 AND etat = 'use'), 'Bleach - Tome 01', 'use', 2.10, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 3);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1288, 1, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 1 AND etat = 'neuf'), 'Harry Potter et la Coupe de Feu', 'neuf', 11.50, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 1);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1290, 7, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 7 AND etat = 'neuf'), 'L''Arabe du futur - Volume 6', 'neuf', 23.90, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 7);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1291, 5, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 5 AND etat = 'bon'), 'Thérèse Raquin', 'bon', 1.80, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 5);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1294, 7, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 7 AND etat = 'tres bon'), 'L''Arabe du futur - Volume 6', 'tres bon', 14.30, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 7);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1295, 3, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 3 AND etat = 'neuf'), 'Bleach - Tome 01', 'neuf', 7.10, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 3);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1299, 10, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 10 AND etat = 'bon'), 'Absolute Batman - Tome 2', 'bon', 11.30, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 10);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1307, 11, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 11 AND etat = 'tres bon'), 'Jujutsu Kaisen - Tome 30', 'tres bon', 4.20, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 11);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1308, 10, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 10 AND etat = 'neuf'), 'Absolute Batman - Tome 2', 'neuf', 25.00, 2 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 10);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1309, 9, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 9 AND etat = 'tres bon'), 'I Am Not Your Negro', 'tres bon', 4.60, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 9);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1312, 10, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 10 AND etat = 'use'), 'Absolute Batman - Tome 2', 'use', 7.50, 2 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 10);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1314, 8, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 8 AND etat = 'use'), 'La Chronique des Bridgerton - Tomes 1 & 2', 'use', 4.80, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 8);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1324, 9, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 9 AND etat = 'tres bon'), 'I Am Not Your Negro', 'tres bon', 4.60, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 9);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1328, 1, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 1 AND etat = 'neuf'), 'Harry Potter et la Coupe de Feu', 'neuf', 11.50, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 1);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1334, 9, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 9 AND etat = 'neuf'), 'I Am Not Your Negro', 'neuf', 7.60, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 9);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1335, 8, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 8 AND etat = 'neuf'), 'La Chronique des Bridgerton - Tomes 1 & 2', 'neuf', 16.00, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 8);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1339, 9, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 9 AND etat = 'neuf'), 'I Am Not Your Negro', 'neuf', 7.60, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 9);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1340, 8, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 8 AND etat = 'neuf'), 'La Chronique des Bridgerton - Tomes 1 & 2', 'neuf', 16.00, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 8);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1341, 11, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 11 AND etat = 'neuf'), 'Jujutsu Kaisen - Tome 30', 'neuf', 6.95, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 11);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1344, 3, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 3 AND etat = 'bon'), 'Bleach - Tome 01', 'bon', 3.20, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 3);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1350, 1, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 1 AND etat = 'neuf'), 'Harry Potter et la Coupe de Feu', 'neuf', 11.50, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 1);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1351, 10, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 10 AND etat = 'neuf'), 'Absolute Batman - Tome 2', 'neuf', 25.00, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 10);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1352, 9, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 9 AND etat = 'tres bon'), 'I Am Not Your Negro', 'tres bon', 4.60, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 9);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1362, 7, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 7 AND etat = 'tres bon'), 'L''Arabe du futur - Volume 6', 'tres bon', 14.30, 2 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 7);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1365, 3, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 3 AND etat = 'tres bon'), 'Bleach - Tome 01', 'tres bon', 4.30, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 3);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1369, 9, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 9 AND etat = 'tres bon'), 'I Am Not Your Negro', 'tres bon', 4.60, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 9);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1375, 9, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 9 AND etat = 'neuf'), 'I Am Not Your Negro', 'neuf', 7.60, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 9);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1379, 6, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 6 AND etat = 'use'), 'Le Problème à trois corps', 'use', 3.20, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 6);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1381, 9, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 9 AND etat = 'tres bon'), 'I Am Not Your Negro', 'tres bon', 4.60, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 9);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1382, 9, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 9 AND etat = 'tres bon'), 'I Am Not Your Negro', 'tres bon', 4.60, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 9);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1384, 9, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 9 AND etat = 'tres bon'), 'I Am Not Your Negro', 'tres bon', 4.60, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 9);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1397, 7, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 7 AND etat = 'use'), 'L''Arabe du futur - Volume 6', 'use', 7.20, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 7);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1398, 8, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 8 AND etat = 'use'), 'La Chronique des Bridgerton - Tomes 1 & 2', 'use', 4.80, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 8);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1401, 5, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 5 AND etat = 'tres bon'), 'Thérèse Raquin', 'tres bon', 2.40, 2 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 5);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1402, 9, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 9 AND etat = 'neuf'), 'I Am Not Your Negro', 'neuf', 7.60, 2 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 9);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1404, 9, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 9 AND etat = 'bon'), 'I Am Not Your Negro', 'bon', 3.40, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 9);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1408, 11, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 11 AND etat = 'neuf'), 'Jujutsu Kaisen - Tome 30', 'neuf', 6.95, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 11);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1410, 6, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 6 AND etat = 'tres bon'), 'Le Problème à trois corps', 'tres bon', 6.30, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 6);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1411, 5, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 5 AND etat = 'bon'), 'Thérèse Raquin', 'bon', 1.80, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 5);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1411, 8, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 8 AND etat = 'use'), 'La Chronique des Bridgerton - Tomes 1 & 2', 'use', 4.80, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 8);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1414, 6, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 6 AND etat = 'tres bon'), 'Le Problème à trois corps', 'tres bon', 6.30, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 6);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1415, 5, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 5 AND etat = 'neuf'), 'Thérèse Raquin', 'neuf', 3.95, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 5);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1416, 4, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 4 AND etat = 'neuf'), 'L''Étranger', 'neuf', 7.50, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 4);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1418, 8, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 8 AND etat = 'bon'), 'La Chronique des Bridgerton - Tomes 1 & 2', 'bon', 7.20, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 8);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1419, 8, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 8 AND etat = 'neuf'), 'La Chronique des Bridgerton - Tomes 1 & 2', 'neuf', 16.00, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 8);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1421, 4, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 4 AND etat = 'use'), 'L''Étranger', 'use', 2.30, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 4);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1422, 5, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 5 AND etat = 'tres bon'), 'Thérèse Raquin', 'tres bon', 2.40, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 5);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1424, 11, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 11 AND etat = 'neuf'), 'Jujutsu Kaisen - Tome 30', 'neuf', 6.95, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 11);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1427, 10, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 10 AND etat = 'bon'), 'Absolute Batman - Tome 2', 'bon', 11.30, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 10);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1431, 5, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 5 AND etat = 'neuf'), 'Thérèse Raquin', 'neuf', 3.95, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 5);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1433, 11, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 11 AND etat = 'neuf'), 'Jujutsu Kaisen - Tome 30', 'neuf', 6.95, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 11);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1434, 4, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 4 AND etat = 'bon'), 'L''Étranger', 'bon', 3.40, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 4);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1434, 8, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 8 AND etat = 'bon'), 'La Chronique des Bridgerton - Tomes 1 & 2', 'bon', 7.20, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 8);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1435, 4, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 4 AND etat = 'neuf'), 'L''Étranger', 'neuf', 7.50, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 4);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1436, 6, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 6 AND etat = 'tres bon'), 'Le Problème à trois corps', 'tres bon', 6.30, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 6);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1436, 8, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 8 AND etat = 'tres bon'), 'La Chronique des Bridgerton - Tomes 1 & 2', 'tres bon', 9.60, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 8);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1444, 3, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 3 AND etat = 'bon'), 'Bleach - Tome 01', 'bon', 3.20, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 3);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1445, 7, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 7 AND etat = 'neuf'), 'L''Arabe du futur - Volume 6', 'neuf', 23.90, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 7);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1446, 5, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 5 AND etat = 'tres bon'), 'Thérèse Raquin', 'tres bon', 2.40, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 5);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1449, 10, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 10 AND etat = 'use'), 'Absolute Batman - Tome 2', 'use', 7.50, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 10);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1454, 6, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 6 AND etat = 'neuf'), 'Le Problème à trois corps', 'neuf', 10.50, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 6);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1455, 6, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 6 AND etat = 'tres bon'), 'Le Problème à trois corps', 'tres bon', 6.30, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 6);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1460, 11, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 11 AND etat = 'use'), 'Jujutsu Kaisen - Tome 30', 'use', 2.10, 2 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 11);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1461, 11, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 11 AND etat = 'tres bon'), 'Jujutsu Kaisen - Tome 30', 'tres bon', 4.20, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 11);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1462, 7, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 7 AND etat = 'bon'), 'L''Arabe du futur - Volume 6', 'bon', 10.80, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 7);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1463, 6, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 6 AND etat = 'bon'), 'Le Problème à trois corps', 'bon', 4.70, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 6);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1469, 11, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 11 AND etat = 'neuf'), 'Jujutsu Kaisen - Tome 30', 'neuf', 6.95, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 11);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1470, 2, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 2 AND etat = 'neuf'), 'Jujutsu Kaisen - Tome 22', 'neuf', 7.10, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 2);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1470, 3, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 3 AND etat = 'tres bon'), 'Bleach - Tome 01', 'tres bon', 4.30, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 3);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1471, 11, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 11 AND etat = 'tres bon'), 'Jujutsu Kaisen - Tome 30', 'tres bon', 4.20, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 11);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1471, 2, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 2 AND etat = 'bon'), 'Jujutsu Kaisen - Tome 22', 'bon', 3.20, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 2);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1471, 8, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 8 AND etat = 'neuf'), 'La Chronique des Bridgerton - Tomes 1 & 2', 'neuf', 16.00, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 8);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1472, 1, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 1 AND etat = 'neuf'), 'Harry Potter et la Coupe de Feu', 'neuf', 11.50, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 1);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1472, 8, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 8 AND etat = 'bon'), 'La Chronique des Bridgerton - Tomes 1 & 2', 'bon', 7.20, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 8);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1472, 3, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 3 AND etat = 'neuf'), 'Bleach - Tome 01', 'neuf', 7.10, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 3);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1473, 7, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 7 AND etat = 'tres bon'), 'L''Arabe du futur - Volume 6', 'tres bon', 14.30, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 7);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1473, 11, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 11 AND etat = 'tres bon'), 'Jujutsu Kaisen - Tome 30', 'tres bon', 4.20, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 11);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1475, 2, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 2 AND etat = 'use'), 'Jujutsu Kaisen - Tome 22', 'use', 2.10, 2 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 2);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1478, 2, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 2 AND etat = 'neuf'), 'Jujutsu Kaisen - Tome 22', 'neuf', 7.10, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 2);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1478, 3, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 3 AND etat = 'bon'), 'Bleach - Tome 01', 'bon', 3.20, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 3);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1478, 6, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 6 AND etat = 'tres bon'), 'Le Problème à trois corps', 'tres bon', 6.30, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 6);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1479, 2, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 2 AND etat = 'bon'), 'Jujutsu Kaisen - Tome 22', 'bon', 3.20, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 2);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1481, 3, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 3 AND etat = 'use'), 'Bleach - Tome 01', 'use', 2.10, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 3);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1482, 11, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 11 AND etat = 'neuf'), 'Jujutsu Kaisen - Tome 30', 'neuf', 6.95, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 11);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1482, 1, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 1 AND etat = 'use'), 'Harry Potter et la Coupe de Feu', 'use', 3.50, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 1);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1483, 7, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 7 AND etat = 'neuf'), 'L''Arabe du futur - Volume 6', 'neuf', 23.90, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 7);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1484, 11, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 11 AND etat = 'use'), 'Jujutsu Kaisen - Tome 30', 'use', 2.10, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 11);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1485, 7, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 7 AND etat = 'bon'), 'L''Arabe du futur - Volume 6', 'bon', 10.80, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 7);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1487, 2, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 2 AND etat = 'bon'), 'Jujutsu Kaisen - Tome 22', 'bon', 3.20, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 2);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1488, 1, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 1 AND etat = 'tres bon'), 'Harry Potter et la Coupe de Feu', 'tres bon', 6.90, 2 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 1);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1488, 7, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 7 AND etat = 'tres bon'), 'L''Arabe du futur - Volume 6', 'tres bon', 14.30, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 7);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1489, 1, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 1 AND etat = 'neuf'), 'Harry Potter et la Coupe de Feu', 'neuf', 11.50, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 1);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1489, 2, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 2 AND etat = 'bon'), 'Jujutsu Kaisen - Tome 22', 'bon', 3.20, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 2);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1492, 1, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 1 AND etat = 'bon'), 'Harry Potter et la Coupe de Feu', 'bon', 5.20, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 1);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1495, 7, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 7 AND etat = 'neuf'), 'L''Arabe du futur - Volume 6', 'neuf', 23.90, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 7);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1496, 7, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 7 AND etat = 'neuf'), 'L''Arabe du futur - Volume 6', 'neuf', 23.90, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 7);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1496, 2, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 2 AND etat = 'bon'), 'Jujutsu Kaisen - Tome 22', 'bon', 3.20, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 2);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1497, 7, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 7 AND etat = 'tres bon'), 'L''Arabe du futur - Volume 6', 'tres bon', 14.30, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 7);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1498, 11, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 11 AND etat = 'bon'), 'Jujutsu Kaisen - Tome 30', 'bon', 3.10, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 11);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1499, 7, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 7 AND etat = 'neuf'), 'L''Arabe du futur - Volume 6', 'neuf', 23.90, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 7);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1499, 6, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 6 AND etat = 'neuf'), 'Le Problème à trois corps', 'neuf', 10.50, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 6);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1500, 2, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 2 AND etat = 'bon'), 'Jujutsu Kaisen - Tome 22', 'bon', 3.20, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 2);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1501, 3, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 3 AND etat = 'bon'), 'Bleach - Tome 01', 'bon', 3.20, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 3);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1502, 7, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 7 AND etat = 'neuf'), 'L''Arabe du futur - Volume 6', 'neuf', 23.90, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 7);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1502, 3, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 3 AND etat = 'bon'), 'Bleach - Tome 01', 'bon', 3.20, 2 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 3);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1503, 1, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 1 AND etat = 'tres bon'), 'Harry Potter et la Coupe de Feu', 'tres bon', 6.90, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 1);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1505, 2, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 2 AND etat = 'bon'), 'Jujutsu Kaisen - Tome 22', 'bon', 3.20, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 2);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1515, 4, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 4 AND etat = 'use'), 'L''Étranger', 'use', 2.30, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 4);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1516, 10, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 10 AND etat = 'tres bon'), 'Absolute Batman - Tome 2', 'tres bon', 15.00, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 10);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1518, 10, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 10 AND etat = 'bon'), 'Absolute Batman - Tome 2', 'bon', 11.30, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 10);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1518, 5, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 5 AND etat = 'tres bon'), 'Thérèse Raquin', 'tres bon', 2.40, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 5);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1525, 5, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 5 AND etat = 'bon'), 'Thérèse Raquin', 'bon', 1.80, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 5);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1526, 1, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 1 AND etat = 'use'), 'Harry Potter et la Coupe de Feu', 'use', 3.50, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 1);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1526, 11, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 11 AND etat = 'use'), 'Jujutsu Kaisen - Tome 30', 'use', 2.10, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 11);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1527, 11, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 11 AND etat = 'tres bon'), 'Jujutsu Kaisen - Tome 30', 'tres bon', 4.20, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 11);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1531, 4, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 4 AND etat = 'neuf'), 'L''Étranger', 'neuf', 7.50, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 4);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1535, 6, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 6 AND etat = 'bon'), 'Le Problème à trois corps', 'bon', 4.70, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 6);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1537, 5, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 5 AND etat = 'tres bon'), 'Thérèse Raquin', 'tres bon', 2.40, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 5);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1538, 4, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 4 AND etat = 'neuf'), 'L''Étranger', 'neuf', 7.50, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 4);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1538, 6, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 6 AND etat = 'bon'), 'Le Problème à trois corps', 'bon', 4.70, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 6);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1539, 11, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 11 AND etat = 'bon'), 'Jujutsu Kaisen - Tome 30', 'bon', 3.10, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 11);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1541, 10, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 10 AND etat = 'tres bon'), 'Absolute Batman - Tome 2', 'tres bon', 15.00, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 10);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1542, 1, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 1 AND etat = 'tres bon'), 'Harry Potter et la Coupe de Feu', 'tres bon', 6.90, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 1);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1545, 8, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 8 AND etat = 'bon'), 'La Chronique des Bridgerton - Tomes 1 & 2', 'bon', 7.20, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 8);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1547, 7, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 7 AND etat = 'bon'), 'L''Arabe du futur - Volume 6', 'bon', 10.80, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 7);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1547, 5, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 5 AND etat = 'bon'), 'Thérèse Raquin', 'bon', 1.80, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 5);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1548, 5, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 5 AND etat = 'bon'), 'Thérèse Raquin', 'bon', 1.80, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 5);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1550, 5, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 5 AND etat = 'tres bon'), 'Thérèse Raquin', 'tres bon', 2.40, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 5);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1550, 6, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 6 AND etat = 'use'), 'Le Problème à trois corps', 'use', 3.20, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 6);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1551, 6, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 6 AND etat = 'tres bon'), 'Le Problème à trois corps', 'tres bon', 6.30, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 6);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1554, 7, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 7 AND etat = 'use'), 'L''Arabe du futur - Volume 6', 'use', 7.20, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 7);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1557, 4, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 4 AND etat = 'bon'), 'L''Étranger', 'bon', 3.40, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 4);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1558, 6, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 6 AND etat = 'bon'), 'Le Problème à trois corps', 'bon', 4.70, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 6);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1559, 11, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 11 AND etat = 'bon'), 'Jujutsu Kaisen - Tome 30', 'bon', 3.10, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 11);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1560, 8, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 8 AND etat = 'neuf'), 'La Chronique des Bridgerton - Tomes 1 & 2', 'neuf', 16.00, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 8);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1562, 8, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 8 AND etat = 'use'), 'La Chronique des Bridgerton - Tomes 1 & 2', 'use', 4.80, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 8);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1562, 6, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 6 AND etat = 'use'), 'Le Problème à trois corps', 'use', 3.20, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 6);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1563, 4, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 4 AND etat = 'use'), 'L''Étranger', 'use', 2.30, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 4);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1565, 8, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 8 AND etat = 'bon'), 'La Chronique des Bridgerton - Tomes 1 & 2', 'bon', 7.20, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 8);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1565, 6, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 6 AND etat = 'tres bon'), 'Le Problème à trois corps', 'tres bon', 6.30, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 6);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1567, 8, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 8 AND etat = 'use'), 'La Chronique des Bridgerton - Tomes 1 & 2', 'use', 4.80, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 8);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1569, 5, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 5 AND etat = 'neuf'), 'Thérèse Raquin', 'neuf', 3.95, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 5);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1569, 8, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 8 AND etat = 'bon'), 'La Chronique des Bridgerton - Tomes 1 & 2', 'bon', 7.20, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 8);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1570, 8, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 8 AND etat = 'tres bon'), 'La Chronique des Bridgerton - Tomes 1 & 2', 'tres bon', 9.60, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 8);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1571, 3, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 3 AND etat = 'bon'), 'Bleach - Tome 01', 'bon', 3.20, 2 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 3);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1572, 2, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 2 AND etat = 'neuf'), 'Jujutsu Kaisen - Tome 22', 'neuf', 7.10, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 2);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1579, 2, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 2 AND etat = 'use'), 'Jujutsu Kaisen - Tome 22', 'use', 2.10, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 2);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1580, 10, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 10 AND etat = 'tres bon'), 'Absolute Batman - Tome 2', 'tres bon', 15.00, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 10);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1580, 7, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 7 AND etat = 'tres bon'), 'L''Arabe du futur - Volume 6', 'tres bon', 14.30, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 7);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1583, 4, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 4 AND etat = 'neuf'), 'L''Étranger', 'neuf', 7.50, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 4);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1583, 6, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 6 AND etat = 'tres bon'), 'Le Problème à trois corps', 'tres bon', 6.30, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 6);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1583, 2, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 2 AND etat = 'neuf'), 'Jujutsu Kaisen - Tome 22', 'neuf', 7.10, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 2);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1585, 2, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 2 AND etat = 'bon'), 'Jujutsu Kaisen - Tome 22', 'bon', 3.20, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 2);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1585, 4, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 4 AND etat = 'bon'), 'L''Étranger', 'bon', 3.40, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 4);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1586, 5, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 5 AND etat = 'bon'), 'Thérèse Raquin', 'bon', 1.80, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 5);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1586, 8, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 8 AND etat = 'bon'), 'La Chronique des Bridgerton - Tomes 1 & 2', 'bon', 7.20, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 8);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1588, 6, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 6 AND etat = 'tres bon'), 'Le Problème à trois corps', 'tres bon', 6.30, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 6);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1589, 8, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 8 AND etat = 'bon'), 'La Chronique des Bridgerton - Tomes 1 & 2', 'bon', 7.20, 2 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 8);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1590, 6, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 6 AND etat = 'tres bon'), 'Le Problème à trois corps', 'tres bon', 6.30, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 6);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1594, 4, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 4 AND etat = 'use'), 'L''Étranger', 'use', 2.30, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 4);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) SELECT 1596, 4, (SELECT MIN(id_exemplaire) FROM exemplaire WHERE id_livre = 4 AND etat = 'bon'), 'L''Étranger', 'bon', 3.40, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 4);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) VALUES
(1001, 1008, 1123, 'J''intègre - Maths MPSI-MP2I : exercices incontournables', 'bon', 13.50, 1),
(1002, 1045, 1127, 'Campbell Biologie (9e édition)', 'bon', 44.60, 1),
(1003, 1045, 1128, 'Campbell Biologie (9e édition)', 'use', 29.70, 1),
(1003, 1006, 1114, 'Bescherelle - La conjugaison pour tous', 'tres bon', 6.00, 1),
(1003, 1004, 1102, 'Une chambre à soi', 'tres bon', 4.30, 1),
(1004, 1037, 1041, 'Chanson douce', 'neuf', 8.50, 1),
(1004, 1045, 1127, 'Campbell Biologie (9e édition)', 'bon', 44.60, 1),
(1004, 1005, 1111, 'Le Robert Junior illustré', 'bon', 11.70, 1),
(1005, 1041, 1093, 'Hitchcock / Truffaut - Édition définitive', 'neuf', 32.00, 1),
(1005, 1035, 1085, 'Sapiens : une brève histoire de l''humanité', 'neuf', 24.90, 1),
(1005, 1003, 1092, 'Histoire de l''art', 'use', 6.00, 1),
(1006, 1041, 1095, 'Hitchcock / Truffaut - Édition définitive', 'bon', 14.40, 1),
(1008, 1034, 1065, 'Avant toi', 'neuf', 8.20, 1),
(1008, 1043, 1052, 'La Vérité sur l''affaire Harry Quebert', 'use', 3.50, 1),
(1009, 1026, 1053, 'Les Rivières pourpres', 'neuf', 9.20, 1),
(1010, 1044, 1106, 'Comme un roman', 'tres bon', 4.50, 1),
(1010, 1047, 1026, 'Le Petit Nicolas', 'tres bon', 4.40, 2),
(1010, 1031, 1004, 'Astérix le Gaulois', 'use', 3.30, 1),
(1012, 1031, 1002, 'Astérix le Gaulois', 'tres bon', 6.60, 1),
(1013, 1005, 1109, 'Le Robert Junior illustré', 'neuf', 25.90, 1),
(1014, 1003, 1091, 'Histoire de l''art', 'bon', 9.00, 1),
(1014, 1017, 1178, 'Pixel et Moustache - Tome 1 : Le chat qui voulait voler', 'neuf', 12.50, 1),
(1015, 1019, 1029, 'Matilda', 'neuf', 8.20, 1),
(1016, 1029, 1033, 'Hunger Games - Tome 1', 'neuf', 8.95, 1),
(1016, 1015, 1175, 'Le Potager du paresseux', 'bon', 4.40, 1),
(1016, 1014, 1171, 'Born to run', 'bon', 4.50, 1),
(1017, 1013, 1163, 'Je sais cuisiner', 'bon', 10.30, 1),
(1018, 1013, 1164, 'Je sais cuisiner', 'use', 6.90, 1),
(1019, 1027, 1143, 'Coder proprement', 'bon', 15.80, 2),
(1020, 1008, 1124, 'J''intègre - Maths MPSI-MP2I : exercices incontournables', 'use', 9.00, 1),
(1020, 1045, 1126, 'Campbell Biologie (9e édition)', 'tres bon', 59.40, 1),
(1021, 1011, 1155, 'Le Charme discret de l''intestin', 'bon', 4.40, 1),
(1022, 1002, 1070, 'Les Fleurs du mal', 'tres bon', 2.90, 1),
(1023, 1029, 1035, 'Hunger Games - Tome 1', 'bon', 4.00, 1),
(1023, 1005, 1109, 'Le Robert Junior illustré', 'neuf', 25.90, 1),
(1023, 1020, 1136, 'Le Capital au XXIe siècle', 'use', 4.20, 1),
(1026, 1019, 1032, 'Matilda', 'use', 2.50, 2),
(1027, 1021, 1045, 'L''Anomalie', 'neuf', 9.20, 1),
(1028, 1038, 1013, 'One Piece - Édition originale - Tome 01', 'neuf', 7.20, 2),
(1029, 1001, 1039, 'La Passe-miroir - Livre 1 : Les Fiancés de l''hiver', 'bon', 4.90, 2),
(1029, 1047, 1028, 'Le Petit Nicolas', 'use', 2.20, 1),
(1030, 1008, 1121, 'J''intègre - Maths MPSI-MP2I : exercices incontournables', 'neuf', 29.90, 1),
(1031, 1016, 1177, 'Les Ombres de la Croix-Rousse', 'neuf', 18.00, 1),
(1031, 1007, 1119, 'ABC du Bac - Mathématiques Terminale spécialité - Annales 2025', 'bon', 4.50, 1),
(1032, 1001, 1039, 'La Passe-miroir - Livre 1 : Les Fiancés de l''hiver', 'bon', 4.90, 1),
(1032, 1041, 1095, 'Hitchcock / Truffaut - Édition définitive', 'bon', 14.40, 1),
(1032, 1028, 1005, 'Blacksad - Tome 1 : Quelque part entre les ombres', 'neuf', 14.99, 2),
(1033, 1006, 1115, 'Bescherelle - La conjugaison pour tous', 'bon', 4.50, 1),
(1033, 1005, 1109, 'Le Robert Junior illustré', 'neuf', 25.90, 1),
(1034, 1006, 1115, 'Bescherelle - La conjugaison pour tous', 'bon', 4.50, 1),
(1034, 1010, 1150, 'Une brève histoire du temps', 'tres bon', 5.40, 1),
(1035, 1034, 1067, 'Avant toi', 'bon', 3.70, 1),
(1035, 1035, 1088, 'Sapiens : une brève histoire de l''humanité', 'use', 7.50, 1),
(1036, 1007, 1117, 'ABC du Bac - Mathématiques Terminale spécialité - Annales 2025', 'neuf', 9.95, 1),
(1037, 1025, 1063, '1984', 'bon', 4.20, 1),
(1037, 1039, 1165, 'Les Quatre Accords toltèques', 'neuf', 8.90, 1),
(1038, 1021, 1047, 'L''Anomalie', 'bon', 4.10, 2),
(1038, 1022, 1148, 'Introduction à l''algorithmique', 'use', 20.70, 1),
(1039, 1025, 1063, '1984', 'bon', 4.20, 1),
(1039, 1029, 1033, 'Hunger Games - Tome 1', 'neuf', 8.95, 1),
(1041, 1033, 1060, 'Dune - Tome 1', 'use', 3.50, 1),
(1043, 1015, 1175, 'Le Potager du paresseux', 'bon', 4.40, 1),
(1043, 1021, 1045, 'L''Anomalie', 'neuf', 9.20, 1),
(1043, 1046, 1080, 'Madame Bovary', 'use', 1.80, 1),
(1044, 1005, 1110, 'Le Robert Junior illustré', 'tres bon', 15.50, 1),
(1044, 1038, 1014, 'One Piece - Édition originale - Tome 01', 'tres bon', 4.30, 1),
(1045, 1044, 1108, 'Comme un roman', 'use', 2.30, 1),
(1045, 1040, 1139, 'Principes de l''économie', 'bon', 26.60, 1),
(1046, 1036, 1081, 'Le Petit Prince', 'neuf', 6.90, 1),
(1047, 1017, 1178, 'Pixel et Moustache - Tome 1 : Le chat qui voulait voler', 'neuf', 12.50, 1),
(1047, 1031, 1003, 'Astérix le Gaulois', 'bon', 4.90, 1),
(1049, 1045, 1126, 'Campbell Biologie (9e édition)', 'tres bon', 59.40, 1),
(1050, 1007, 1117, 'ABC du Bac - Mathématiques Terminale spécialité - Annales 2025', 'neuf', 9.95, 1),
(1051, 1006, 1113, 'Bescherelle - La conjugaison pour tous', 'neuf', 9.95, 1),
(1052, 1029, 1035, 'Hunger Games - Tome 1', 'bon', 4.00, 1),
(1052, 1041, 1094, 'Hitchcock / Truffaut - Édition définitive', 'tres bon', 19.20, 1),
(1054, 1035, 1086, 'Sapiens : une brève histoire de l''humanité', 'tres bon', 14.90, 1),
(1054, 1004, 1104, 'Une chambre à soi', 'use', 2.10, 1),
(1054, 1024, 1097, 'L''Usage du monde', 'neuf', 12.00, 1),
(1055, 1036, 1083, 'Le Petit Prince', 'bon', 3.10, 1),
(1056, 1033, 1060, 'Dune - Tome 1', 'use', 3.50, 1),
(1056, 1042, 1074, 'Cyrano de Bergerac', 'tres bon', 4.00, 1),
(1057, 1027, 1143, 'Coder proprement', 'bon', 15.80, 1),
(1058, 1046, 1077, 'Madame Bovary', 'neuf', 5.90, 1),
(1058, 1037, 1041, 'Chanson douce', 'neuf', 8.50, 1),
(1058, 1014, 1171, 'Born to run', 'bon', 4.50, 2),
(1059, 1021, 1048, 'L''Anomalie', 'use', 2.80, 1),
(1060, 1031, 1001, 'Astérix le Gaulois', 'neuf', 10.99, 1),
(1060, 1044, 1105, 'Comme un roman', 'neuf', 7.50, 2),
(1061, 1043, 1049, 'La Vérité sur l''affaire Harry Quebert', 'neuf', 11.50, 1),
(1061, 1038, 1014, 'One Piece - Édition originale - Tome 01', 'tres bon', 4.30, 1),
(1062, 1001, 1037, 'La Passe-miroir - Livre 1 : Les Fiancés de l''hiver', 'neuf', 10.90, 2),
(1063, 1042, 1074, 'Cyrano de Bergerac', 'tres bon', 4.00, 1),
(1063, 1047, 1025, 'Le Petit Nicolas', 'neuf', 7.40, 1),
(1064, 1013, 1161, 'Je sais cuisiner', 'neuf', 22.90, 2),
(1064, 1041, 1094, 'Hitchcock / Truffaut - Édition définitive', 'tres bon', 19.20, 1),
(1066, 1002, 1071, 'Les Fleurs du mal', 'bon', 2.20, 1),
(1068, 1015, 1175, 'Le Potager du paresseux', 'bon', 4.40, 2),
(1070, 1005, 1109, 'Le Robert Junior illustré', 'neuf', 25.90, 1),
(1070, 1045, 1127, 'Campbell Biologie (9e édition)', 'bon', 44.60, 1),
(1071, 1003, 1091, 'Histoire de l''art', 'bon', 9.00, 1),
(1072, 1005, 1109, 'Le Robert Junior illustré', 'neuf', 25.90, 1),
(1072, 1039, 1165, 'Les Quatre Accords toltèques', 'neuf', 8.90, 1),
(1072, 1008, 1124, 'J''intègre - Maths MPSI-MP2I : exercices incontournables', 'use', 9.00, 1),
(1073, 1035, 1087, 'Sapiens : une brève histoire de l''humanité', 'bon', 11.20, 1),
(1073, 1008, 1122, 'J''intègre - Maths MPSI-MP2I : exercices incontournables', 'tres bon', 17.90, 1),
(1074, 1045, 1127, 'Campbell Biologie (9e édition)', 'bon', 44.60, 1),
(1075, 1027, 1143, 'Coder proprement', 'bon', 15.80, 1),
(1075, 1031, 1001, 'Astérix le Gaulois', 'neuf', 10.99, 1),
(1075, 1005, 1111, 'Le Robert Junior illustré', 'bon', 11.70, 1),
(1076, 1028, 1007, 'Blacksad - Tome 1 : Quelque part entre les ombres', 'bon', 6.70, 1),
(1076, 1035, 1085, 'Sapiens : une brève histoire de l''humanité', 'neuf', 24.90, 1),
(1077, 1009, 1129, 'Lexique des termes juridiques 2025-2026', 'neuf', 16.90, 1),
(1078, 1030, 1018, 'La Chenille qui fait des trous', 'tres bon', 6.00, 1),
(1078, 1031, 1001, 'Astérix le Gaulois', 'neuf', 10.99, 1),
(1080, 1028, 1008, 'Blacksad - Tome 1 : Quelque part entre les ombres', 'use', 4.50, 1),
(1080, 1041, 1094, 'Hitchcock / Truffaut - Édition définitive', 'tres bon', 19.20, 1),
(1081, 1008, 1121, 'J''intègre - Maths MPSI-MP2I : exercices incontournables', 'neuf', 29.90, 1),
(1082, 1030, 1019, 'La Chenille qui fait des trous', 'bon', 4.50, 1),
(1083, 1037, 1042, 'Chanson douce', 'tres bon', 5.10, 1),
(1083, 1038, 1016, 'One Piece - Édition originale - Tome 01', 'use', 2.20, 1),
(1083, 1046, 1080, 'Madame Bovary', 'use', 1.80, 1),
(1084, 1027, 1142, 'Coder proprement', 'tres bon', 21.00, 1),
(1085, 1042, 1076, 'Cyrano de Bergerac', 'use', 2.00, 1),
(1085, 1017, 1178, 'Pixel et Moustache - Tome 1 : Le chat qui voulait voler', 'neuf', 12.50, 1),
(1085, 1047, 1025, 'Le Petit Nicolas', 'neuf', 7.40, 1),
(1086, 1030, 1017, 'La Chenille qui fait des trous', 'neuf', 10.00, 1),
(1086, 1023, 1010, 'Watchmen', 'tres bon', 21.00, 1),
(1086, 1024, 1097, 'L''Usage du monde', 'neuf', 12.00, 1),
(1087, 1023, 1010, 'Watchmen', 'tres bon', 21.00, 2),
(1087, 1032, 1024, 'Max et les Maximonstres', 'use', 4.00, 1),
(1087, 1040, 1138, 'Principes de l''économie', 'tres bon', 35.40, 1),
(1088, 1007, 1117, 'ABC du Bac - Mathématiques Terminale spécialité - Annales 2025', 'neuf', 9.95, 1),
(1090, 1002, 1072, 'Les Fleurs du mal', 'use', 1.50, 1),
(1091, 1046, 1079, 'Madame Bovary', 'bon', 2.70, 1),
(1092, 1043, 1051, 'La Vérité sur l''affaire Harry Quebert', 'bon', 5.20, 1),
(1093, 1037, 1044, 'Chanson douce', 'use', 2.60, 1),
(1093, 1041, 1095, 'Hitchcock / Truffaut - Édition définitive', 'bon', 14.40, 1),
(1093, 1046, 1077, 'Madame Bovary', 'neuf', 5.90, 2),
(1094, 1033, 1059, 'Dune - Tome 1', 'bon', 5.20, 1),
(1096, 1043, 1050, 'La Vérité sur l''affaire Harry Quebert', 'tres bon', 6.90, 1),
(1098, 1021, 1046, 'L''Anomalie', 'tres bon', 5.50, 1),
(1098, 1019, 1030, 'Matilda', 'tres bon', 4.90, 1),
(1099, 1013, 1163, 'Je sais cuisiner', 'bon', 10.30, 1),
(1100, 1016, 1177, 'Les Ombres de la Croix-Rousse', 'neuf', 18.00, 1),
(1101, 1026, 1053, 'Les Rivières pourpres', 'neuf', 9.20, 1),
(1101, 1022, 1147, 'Introduction à l''algorithmique', 'bon', 31.10, 1),
(1101, 1007, 1117, 'ABC du Bac - Mathématiques Terminale spécialité - Annales 2025', 'neuf', 9.95, 1),
(1103, 1015, 1173, 'Le Potager du paresseux', 'neuf', 9.70, 1),
(1104, 1003, 1091, 'Histoire de l''art', 'bon', 9.00, 1);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) VALUES
(1104, 1046, 1079, 'Madame Bovary', 'bon', 2.70, 1),
(1105, 1037, 1042, 'Chanson douce', 'tres bon', 5.10, 1),
(1106, 1025, 1062, '1984', 'tres bon', 5.60, 1),
(1107, 1042, 1073, 'Cyrano de Bergerac', 'neuf', 6.70, 1),
(1108, 1043, 1049, 'La Vérité sur l''affaire Harry Quebert', 'neuf', 11.50, 1),
(1109, 1016, 1177, 'Les Ombres de la Croix-Rousse', 'neuf', 18.00, 1),
(1110, 1004, 1103, 'Une chambre à soi', 'bon', 3.20, 1),
(1110, 1046, 1079, 'Madame Bovary', 'bon', 2.70, 1),
(1110, 1026, 1053, 'Les Rivières pourpres', 'neuf', 9.20, 1),
(1111, 1037, 1043, 'Chanson douce', 'bon', 3.80, 1),
(1112, 1009, 1129, 'Lexique des termes juridiques 2025-2026', 'neuf', 16.90, 1),
(1113, 1039, 1168, 'Les Quatre Accords toltèques', 'use', 2.70, 1),
(1113, 1006, 1113, 'Bescherelle - La conjugaison pour tous', 'neuf', 9.95, 1),
(1116, 1047, 1025, 'Le Petit Nicolas', 'neuf', 7.40, 1),
(1116, 1025, 1063, '1984', 'bon', 4.20, 1),
(1117, 1020, 1135, 'Le Capital au XXIe siècle', 'bon', 6.30, 2),
(1117, 1026, 1054, 'Les Rivières pourpres', 'tres bon', 5.50, 1),
(1118, 1036, 1081, 'Le Petit Prince', 'neuf', 6.90, 1),
(1118, 1042, 1074, 'Cyrano de Bergerac', 'tres bon', 4.00, 1),
(1120, 1029, 1033, 'Hunger Games - Tome 1', 'neuf', 8.95, 1),
(1121, 1019, 1029, 'Matilda', 'neuf', 8.20, 1),
(1121, 1026, 1056, 'Les Rivières pourpres', 'use', 2.80, 1),
(1122, 1029, 1033, 'Hunger Games - Tome 1', 'neuf', 8.95, 1),
(1122, 1045, 1128, 'Campbell Biologie (9e édition)', 'use', 29.70, 1),
(1123, 1027, 1142, 'Coder proprement', 'tres bon', 21.00, 1),
(1123, 1030, 1018, 'La Chenille qui fait des trous', 'tres bon', 6.00, 1),
(1124, 1038, 1013, 'One Piece - Édition originale - Tome 01', 'neuf', 7.20, 1),
(1125, 1023, 1011, 'Watchmen', 'bon', 15.80, 1),
(1126, 1040, 1139, 'Principes de l''économie', 'bon', 26.60, 1),
(1126, 1031, 1004, 'Astérix le Gaulois', 'use', 3.30, 1),
(1127, 1001, 1039, 'La Passe-miroir - Livre 1 : Les Fiancés de l''hiver', 'bon', 4.90, 1),
(1127, 1017, 1178, 'Pixel et Moustache - Tome 1 : Le chat qui voulait voler', 'neuf', 12.50, 1),
(1128, 1023, 1011, 'Watchmen', 'bon', 15.80, 1),
(1128, 1028, 1005, 'Blacksad - Tome 1 : Quelque part entre les ombres', 'neuf', 14.99, 1),
(1129, 1030, 1017, 'La Chenille qui fait des trous', 'neuf', 10.00, 1),
(1130, 1020, 1135, 'Le Capital au XXIe siècle', 'bon', 6.30, 1),
(1130, 1009, 1132, 'Lexique des termes juridiques 2025-2026', 'use', 5.10, 1),
(1130, 1013, 1161, 'Je sais cuisiner', 'neuf', 22.90, 1),
(1131, 1013, 1162, 'Je sais cuisiner', 'tres bon', 13.70, 1),
(1132, 1028, 1006, 'Blacksad - Tome 1 : Quelque part entre les ombres', 'tres bon', 9.00, 1),
(1132, 1013, 1161, 'Je sais cuisiner', 'neuf', 22.90, 1),
(1133, 1014, 1169, 'Born to run', 'neuf', 9.90, 1),
(1133, 1039, 1165, 'Les Quatre Accords toltèques', 'neuf', 8.90, 1),
(1133, 1032, 1024, 'Max et les Maximonstres', 'use', 4.00, 1),
(1134, 1015, 1173, 'Le Potager du paresseux', 'neuf', 9.70, 1),
(1134, 1039, 1167, 'Les Quatre Accords toltèques', 'bon', 4.00, 1),
(1135, 1007, 1119, 'ABC du Bac - Mathématiques Terminale spécialité - Annales 2025', 'bon', 4.50, 1),
(1136, 1014, 1169, 'Born to run', 'neuf', 9.90, 1),
(1136, 1013, 1162, 'Je sais cuisiner', 'tres bon', 13.70, 2),
(1137, 1039, 1165, 'Les Quatre Accords toltèques', 'neuf', 8.90, 1),
(1137, 1015, 1176, 'Le Potager du paresseux', 'use', 2.90, 1),
(1137, 1014, 1169, 'Born to run', 'neuf', 9.90, 1),
(1138, 1046, 1080, 'Madame Bovary', 'use', 1.80, 2),
(1138, 1039, 1166, 'Les Quatre Accords toltèques', 'tres bon', 5.30, 1),
(1138, 1014, 1171, 'Born to run', 'bon', 4.50, 2),
(1139, 1039, 1168, 'Les Quatre Accords toltèques', 'use', 2.70, 1),
(1140, 1014, 1170, 'Born to run', 'tres bon', 5.90, 1),
(1141, 1030, 1018, 'La Chenille qui fait des trous', 'tres bon', 6.00, 1),
(1141, 1029, 1034, 'Hunger Games - Tome 1', 'tres bon', 5.40, 1),
(1141, 1012, 1160, 'Bescherelle - L''anglais pour tous', 'use', 4.80, 2),
(1142, 1015, 1173, 'Le Potager du paresseux', 'neuf', 9.70, 1),
(1143, 1017, 1178, 'Pixel et Moustache - Tome 1 : Le chat qui voulait voler', 'neuf', 12.50, 1),
(1143, 1014, 1169, 'Born to run', 'neuf', 9.90, 1),
(1144, 1013, 1161, 'Je sais cuisiner', 'neuf', 22.90, 1),
(1144, 1039, 1168, 'Les Quatre Accords toltèques', 'use', 2.70, 1),
(1145, 1015, 1174, 'Le Potager du paresseux', 'tres bon', 5.80, 1),
(1146, 1041, 1095, 'Hitchcock / Truffaut - Édition définitive', 'bon', 14.40, 1),
(1146, 1015, 1175, 'Le Potager du paresseux', 'bon', 4.40, 1),
(1146, 1023, 1010, 'Watchmen', 'tres bon', 21.00, 1),
(1147, 1036, 1081, 'Le Petit Prince', 'neuf', 6.90, 1),
(1147, 1039, 1165, 'Les Quatre Accords toltèques', 'neuf', 8.90, 2),
(1147, 1013, 1162, 'Je sais cuisiner', 'tres bon', 13.70, 2),
(1148, 1014, 1169, 'Born to run', 'neuf', 9.90, 1),
(1148, 1011, 1156, 'Le Charme discret de l''intestin', 'use', 2.90, 1),
(1149, 1039, 1167, 'Les Quatre Accords toltèques', 'bon', 4.00, 2),
(1150, 1013, 1161, 'Je sais cuisiner', 'neuf', 22.90, 1),
(1151, 1032, 1021, 'Max et les Maximonstres', 'neuf', 13.20, 1),
(1151, 1015, 1174, 'Le Potager du paresseux', 'tres bon', 5.80, 1),
(1152, 1014, 1169, 'Born to run', 'neuf', 9.90, 2),
(1153, 1034, 1066, 'Avant toi', 'tres bon', 4.90, 1),
(1154, 1013, 1164, 'Je sais cuisiner', 'use', 6.90, 1),
(1154, 1041, 1093, 'Hitchcock / Truffaut - Édition définitive', 'neuf', 32.00, 2),
(1155, 1029, 1033, 'Hunger Games - Tome 1', 'neuf', 8.95, 1),
(1156, 1032, 1023, 'Max et les Maximonstres', 'bon', 5.90, 1),
(1157, 1044, 1105, 'Comme un roman', 'neuf', 7.50, 1),
(1158, 1003, 1090, 'Histoire de l''art', 'tres bon', 12.00, 1),
(1158, 1041, 1094, 'Hitchcock / Truffaut - Édition définitive', 'tres bon', 19.20, 1),
(1159, 1003, 1089, 'Histoire de l''art', 'neuf', 19.95, 1),
(1160, 1003, 1091, 'Histoire de l''art', 'bon', 9.00, 1),
(1160, 1004, 1101, 'Une chambre à soi', 'neuf', 7.10, 1),
(1161, 1035, 1087, 'Sapiens : une brève histoire de l''humanité', 'bon', 11.20, 1),
(1161, 1025, 1063, '1984', 'bon', 4.20, 1),
(1162, 1005, 1111, 'Le Robert Junior illustré', 'bon', 11.70, 1),
(1162, 1024, 1100, 'L''Usage du monde', 'use', 3.60, 1),
(1163, 1041, 1095, 'Hitchcock / Truffaut - Édition définitive', 'bon', 14.40, 1),
(1164, 1035, 1087, 'Sapiens : une brève histoire de l''humanité', 'bon', 11.20, 1),
(1164, 1003, 1089, 'Histoire de l''art', 'neuf', 19.95, 1),
(1166, 1005, 1109, 'Le Robert Junior illustré', 'neuf', 25.90, 1),
(1167, 1004, 1104, 'Une chambre à soi', 'use', 2.10, 1),
(1167, 1003, 1091, 'Histoire de l''art', 'bon', 9.00, 1),
(1168, 1004, 1102, 'Une chambre à soi', 'tres bon', 4.30, 1),
(1168, 1038, 1016, 'One Piece - Édition originale - Tome 01', 'use', 2.20, 1),
(1169, 1041, 1096, 'Hitchcock / Truffaut - Édition définitive', 'use', 9.60, 1),
(1169, 1044, 1105, 'Comme un roman', 'neuf', 7.50, 1),
(1171, 1024, 1098, 'L''Usage du monde', 'tres bon', 7.20, 2),
(1171, 1020, 1134, 'Le Capital au XXIe siècle', 'tres bon', 8.30, 1),
(1172, 1044, 1108, 'Comme un roman', 'use', 2.30, 1),
(1173, 1007, 1119, 'ABC du Bac - Mathématiques Terminale spécialité - Annales 2025', 'bon', 4.50, 1),
(1174, 1009, 1132, 'Lexique des termes juridiques 2025-2026', 'use', 5.10, 1),
(1174, 1004, 1101, 'Une chambre à soi', 'neuf', 7.10, 1),
(1175, 1003, 1090, 'Histoire de l''art', 'tres bon', 12.00, 1),
(1175, 1044, 1105, 'Comme un roman', 'neuf', 7.50, 2),
(1175, 1028, 1007, 'Blacksad - Tome 1 : Quelque part entre les ombres', 'bon', 6.70, 1),
(1176, 1021, 1045, 'L''Anomalie', 'neuf', 9.20, 2),
(1176, 1008, 1122, 'J''intègre - Maths MPSI-MP2I : exercices incontournables', 'tres bon', 17.90, 1),
(1177, 1042, 1073, 'Cyrano de Bergerac', 'neuf', 6.70, 1),
(1177, 1032, 1024, 'Max et les Maximonstres', 'use', 4.00, 1),
(1178, 1016, 1177, 'Les Ombres de la Croix-Rousse', 'neuf', 18.00, 1),
(1178, 1010, 1150, 'Une brève histoire du temps', 'tres bon', 5.40, 1),
(1179, 1033, 1059, 'Dune - Tome 1', 'bon', 5.20, 2),
(1179, 1046, 1078, 'Madame Bovary', 'tres bon', 3.50, 1),
(1179, 1003, 1090, 'Histoire de l''art', 'tres bon', 12.00, 1),
(1180, 1025, 1062, '1984', 'tres bon', 5.60, 1),
(1181, 1042, 1075, 'Cyrano de Bergerac', 'bon', 3.00, 1),
(1182, 1016, 1177, 'Les Ombres de la Croix-Rousse', 'neuf', 18.00, 1),
(1182, 1014, 1169, 'Born to run', 'neuf', 9.90, 1),
(1183, 1047, 1027, 'Le Petit Nicolas', 'bon', 3.30, 1),
(1185, 1042, 1075, 'Cyrano de Bergerac', 'bon', 3.00, 1),
(1185, 1033, 1057, 'Dune - Tome 1', 'neuf', 11.50, 1),
(1185, 1046, 1078, 'Madame Bovary', 'tres bon', 3.50, 1),
(1186, 1016, 1177, 'Les Ombres de la Croix-Rousse', 'neuf', 18.00, 2),
(1187, 1021, 1048, 'L''Anomalie', 'use', 2.80, 1),
(1187, 1042, 1073, 'Cyrano de Bergerac', 'neuf', 6.70, 1),
(1188, 1013, 1161, 'Je sais cuisiner', 'neuf', 22.90, 1),
(1189, 1039, 1168, 'Les Quatre Accords toltèques', 'use', 2.70, 1),
(1191, 1031, 1003, 'Astérix le Gaulois', 'bon', 4.90, 1),
(1191, 1020, 1134, 'Le Capital au XXIe siècle', 'tres bon', 8.30, 1),
(1192, 1019, 1032, 'Matilda', 'use', 2.50, 1),
(1192, 1039, 1167, 'Les Quatre Accords toltèques', 'bon', 4.00, 1),
(1193, 1033, 1058, 'Dune - Tome 1', 'tres bon', 6.90, 1),
(1194, 1029, 1034, 'Hunger Games - Tome 1', 'tres bon', 5.40, 1),
(1194, 1028, 1007, 'Blacksad - Tome 1 : Quelque part entre les ombres', 'bon', 6.70, 1),
(1194, 1032, 1024, 'Max et les Maximonstres', 'use', 4.00, 2),
(1195, 1047, 1026, 'Le Petit Nicolas', 'tres bon', 4.40, 1),
(1195, 1021, 1046, 'L''Anomalie', 'tres bon', 5.50, 1),
(1195, 1032, 1023, 'Max et les Maximonstres', 'bon', 5.90, 1),
(1196, 1010, 1149, 'Une brève histoire du temps', 'neuf', 9.00, 2),
(1196, 1030, 1019, 'La Chenille qui fait des trous', 'bon', 4.50, 2),
(1197, 1047, 1026, 'Le Petit Nicolas', 'tres bon', 4.40, 1),
(1197, 1020, 1134, 'Le Capital au XXIe siècle', 'tres bon', 8.30, 1);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) VALUES
(1198, 1020, 1134, 'Le Capital au XXIe siècle', 'tres bon', 8.30, 1),
(1199, 1047, 1026, 'Le Petit Nicolas', 'tres bon', 4.40, 1),
(1201, 1017, 1178, 'Pixel et Moustache - Tome 1 : Le chat qui voulait voler', 'neuf', 12.50, 1),
(1202, 1011, 1155, 'Le Charme discret de l''intestin', 'bon', 4.40, 1),
(1203, 1040, 1138, 'Principes de l''économie', 'tres bon', 35.40, 1),
(1204, 1046, 1079, 'Madame Bovary', 'bon', 2.70, 1),
(1204, 1016, 1177, 'Les Ombres de la Croix-Rousse', 'neuf', 18.00, 1),
(1205, 1019, 1031, 'Matilda', 'bon', 3.70, 1),
(1205, 1011, 1156, 'Le Charme discret de l''intestin', 'use', 2.90, 1),
(1206, 1003, 1090, 'Histoire de l''art', 'tres bon', 12.00, 2),
(1206, 1032, 1023, 'Max et les Maximonstres', 'bon', 5.90, 1),
(1208, 1031, 1001, 'Astérix le Gaulois', 'neuf', 10.99, 1),
(1208, 1030, 1017, 'La Chenille qui fait des trous', 'neuf', 10.00, 1),
(1208, 1019, 1029, 'Matilda', 'neuf', 8.20, 1),
(1209, 1034, 1067, 'Avant toi', 'bon', 3.70, 1),
(1209, 1038, 1015, 'One Piece - Édition originale - Tome 01', 'bon', 3.20, 1),
(1210, 1019, 1030, 'Matilda', 'tres bon', 4.90, 1),
(1210, 1031, 1002, 'Astérix le Gaulois', 'tres bon', 6.60, 1),
(1210, 1020, 1136, 'Le Capital au XXIe siècle', 'use', 4.20, 1),
(1212, 1038, 1015, 'One Piece - Édition originale - Tome 01', 'bon', 3.20, 1),
(1212, 1035, 1086, 'Sapiens : une brève histoire de l''humanité', 'tres bon', 14.90, 1),
(1212, 1032, 1023, 'Max et les Maximonstres', 'bon', 5.90, 1),
(1213, 1029, 1034, 'Hunger Games - Tome 1', 'tres bon', 5.40, 1),
(1213, 1019, 1029, 'Matilda', 'neuf', 8.20, 1),
(1214, 1031, 1002, 'Astérix le Gaulois', 'tres bon', 6.60, 1),
(1214, 1019, 1030, 'Matilda', 'tres bon', 4.90, 1),
(1214, 1030, 1018, 'La Chenille qui fait des trous', 'tres bon', 6.00, 2),
(1215, 1032, 1024, 'Max et les Maximonstres', 'use', 4.00, 1),
(1216, 1038, 1013, 'One Piece - Édition originale - Tome 01', 'neuf', 7.20, 1),
(1216, 1017, 1178, 'Pixel et Moustache - Tome 1 : Le chat qui voulait voler', 'neuf', 12.50, 1),
(1217, 1039, 1167, 'Les Quatre Accords toltèques', 'bon', 4.00, 1),
(1217, 1013, 1162, 'Je sais cuisiner', 'tres bon', 13.70, 1),
(1218, 1014, 1169, 'Born to run', 'neuf', 9.90, 1),
(1219, 1039, 1166, 'Les Quatre Accords toltèques', 'tres bon', 5.30, 1),
(1220, 1013, 1163, 'Je sais cuisiner', 'bon', 10.30, 1),
(1220, 1047, 1028, 'Le Petit Nicolas', 'use', 2.20, 1),
(1221, 1015, 1175, 'Le Potager du paresseux', 'bon', 4.40, 1),
(1221, 1013, 1161, 'Je sais cuisiner', 'neuf', 22.90, 1),
(1222, 1014, 1171, 'Born to run', 'bon', 4.50, 1),
(1223, 1039, 1165, 'Les Quatre Accords toltèques', 'neuf', 8.90, 1),
(1224, 1014, 1170, 'Born to run', 'tres bon', 5.90, 1),
(1224, 1039, 1167, 'Les Quatre Accords toltèques', 'bon', 4.00, 1),
(1225, 1009, 1130, 'Lexique des termes juridiques 2025-2026', 'tres bon', 10.10, 1),
(1226, 1039, 1167, 'Les Quatre Accords toltèques', 'bon', 4.00, 2),
(1226, 1015, 1174, 'Le Potager du paresseux', 'tres bon', 5.80, 1),
(1226, 1013, 1164, 'Je sais cuisiner', 'use', 6.90, 1),
(1227, 1014, 1170, 'Born to run', 'tres bon', 5.90, 2),
(1228, 1014, 1169, 'Born to run', 'neuf', 9.90, 1),
(1228, 1039, 1167, 'Les Quatre Accords toltèques', 'bon', 4.00, 1),
(1228, 1015, 1174, 'Le Potager du paresseux', 'tres bon', 5.80, 1),
(1229, 1044, 1107, 'Comme un roman', 'bon', 3.40, 1),
(1230, 1015, 1173, 'Le Potager du paresseux', 'neuf', 9.70, 1),
(1230, 1014, 1169, 'Born to run', 'neuf', 9.90, 1),
(1231, 1014, 1170, 'Born to run', 'tres bon', 5.90, 2),
(1231, 1015, 1174, 'Le Potager du paresseux', 'tres bon', 5.80, 1),
(1232, 1012, 1159, 'Bescherelle - L''anglais pour tous', 'bon', 7.20, 1),
(1233, 1010, 1150, 'Une brève histoire du temps', 'tres bon', 5.40, 1),
(1233, 1022, 1145, 'Introduction à l''algorithmique', 'neuf', 69.00, 1),
(1233, 1012, 1160, 'Bescherelle - L''anglais pour tous', 'use', 4.80, 1),
(1234, 1040, 1138, 'Principes de l''économie', 'tres bon', 35.40, 1),
(1235, 1036, 1084, 'Le Petit Prince', 'use', 2.10, 1),
(1236, 1010, 1152, 'Une brève histoire du temps', 'use', 2.70, 1),
(1236, 1020, 1134, 'Le Capital au XXIe siècle', 'tres bon', 8.30, 1),
(1237, 1040, 1139, 'Principes de l''économie', 'bon', 26.60, 1),
(1238, 1009, 1131, 'Lexique des termes juridiques 2025-2026', 'bon', 7.60, 1),
(1238, 1028, 1007, 'Blacksad - Tome 1 : Quelque part entre les ombres', 'bon', 6.70, 1),
(1239, 1011, 1154, 'Le Charme discret de l''intestin', 'tres bon', 5.80, 2),
(1240, 1012, 1159, 'Bescherelle - L''anglais pour tous', 'bon', 7.20, 1),
(1240, 1031, 1003, 'Astérix le Gaulois', 'bon', 4.90, 1),
(1241, 1040, 1138, 'Principes de l''économie', 'tres bon', 35.40, 1),
(1241, 1038, 1015, 'One Piece - Édition originale - Tome 01', 'bon', 3.20, 1),
(1242, 1014, 1170, 'Born to run', 'tres bon', 5.90, 1),
(1242, 1039, 1167, 'Les Quatre Accords toltèques', 'bon', 4.00, 1),
(1242, 1015, 1174, 'Le Potager du paresseux', 'tres bon', 5.80, 1),
(1243, 1013, 1162, 'Je sais cuisiner', 'tres bon', 13.70, 1),
(1243, 1034, 1066, 'Avant toi', 'tres bon', 4.90, 1),
(1243, 1039, 1167, 'Les Quatre Accords toltèques', 'bon', 4.00, 1),
(1244, 1037, 1043, 'Chanson douce', 'bon', 3.80, 1),
(1245, 1013, 1162, 'Je sais cuisiner', 'tres bon', 13.70, 1),
(1246, 1021, 1047, 'L''Anomalie', 'bon', 4.10, 1),
(1246, 1039, 1167, 'Les Quatre Accords toltèques', 'bon', 4.00, 1),
(1247, 1033, 1059, 'Dune - Tome 1', 'bon', 5.20, 1),
(1248, 1013, 1163, 'Je sais cuisiner', 'bon', 10.30, 1),
(1248, 1037, 1042, 'Chanson douce', 'tres bon', 5.10, 1),
(1248, 1014, 1171, 'Born to run', 'bon', 4.50, 2),
(1249, 1013, 1163, 'Je sais cuisiner', 'bon', 10.30, 1),
(1249, 1038, 1016, 'One Piece - Édition originale - Tome 01', 'use', 2.20, 1),
(1249, 1014, 1171, 'Born to run', 'bon', 4.50, 1),
(1250, 1039, 1165, 'Les Quatre Accords toltèques', 'neuf', 8.90, 1),
(1251, 1039, 1165, 'Les Quatre Accords toltèques', 'neuf', 8.90, 1),
(1251, 1013, 1163, 'Je sais cuisiner', 'bon', 10.30, 1),
(1251, 1001, 1038, 'La Passe-miroir - Livre 1 : Les Fiancés de l''hiver', 'tres bon', 6.50, 1),
(1252, 1015, 1173, 'Le Potager du paresseux', 'neuf', 9.70, 1),
(1253, 1019, 1030, 'Matilda', 'tres bon', 4.90, 1),
(1255, 1014, 1169, 'Born to run', 'neuf', 9.90, 1),
(1255, 1040, 1137, 'Principes de l''économie', 'neuf', 59.00, 2),
(1257, 1014, 1169, 'Born to run', 'neuf', 9.90, 2),
(1258, 1015, 1175, 'Le Potager du paresseux', 'bon', 4.40, 1),
(1259, 1014, 1171, 'Born to run', 'bon', 4.50, 2),
(1260, 1044, 1106, 'Comme un roman', 'tres bon', 4.50, 1),
(1260, 1028, 1006, 'Blacksad - Tome 1 : Quelque part entre les ombres', 'tres bon', 9.00, 1),
(1261, 1039, 1166, 'Les Quatre Accords toltèques', 'tres bon', 5.30, 1),
(1261, 1015, 1173, 'Le Potager du paresseux', 'neuf', 9.70, 1),
(1261, 1034, 1065, 'Avant toi', 'neuf', 8.20, 2),
(1262, 1021, 1047, 'L''Anomalie', 'bon', 4.10, 1),
(1262, 1008, 1124, 'J''intègre - Maths MPSI-MP2I : exercices incontournables', 'use', 9.00, 1),
(1262, 1014, 1172, 'Born to run', 'use', 3.00, 1),
(1263, 1036, 1081, 'Le Petit Prince', 'neuf', 6.90, 1),
(1264, 1028, 1005, 'Blacksad - Tome 1 : Quelque part entre les ombres', 'neuf', 14.99, 1),
(1265, 1014, 1172, 'Born to run', 'use', 3.00, 1),
(1265, 1013, 1161, 'Je sais cuisiner', 'neuf', 22.90, 2),
(1265, 1026, 1053, 'Les Rivières pourpres', 'neuf', 9.20, 1),
(1266, 1024, 1099, 'L''Usage du monde', 'bon', 5.40, 1),
(1266, 1039, 1166, 'Les Quatre Accords toltèques', 'tres bon', 5.30, 2),
(1266, 1014, 1170, 'Born to run', 'tres bon', 5.90, 1),
(1267, 1015, 1175, 'Le Potager du paresseux', 'bon', 4.40, 1),
(1267, 1039, 1165, 'Les Quatre Accords toltèques', 'neuf', 8.90, 1),
(1267, 1033, 1060, 'Dune - Tome 1', 'use', 3.50, 1),
(1268, 1001, 1039, 'La Passe-miroir - Livre 1 : Les Fiancés de l''hiver', 'bon', 4.90, 1),
(1268, 1015, 1175, 'Le Potager du paresseux', 'bon', 4.40, 1),
(1268, 1013, 1164, 'Je sais cuisiner', 'use', 6.90, 1),
(1269, 1015, 1175, 'Le Potager du paresseux', 'bon', 4.40, 1),
(1269, 1007, 1120, 'ABC du Bac - Mathématiques Terminale spécialité - Annales 2025', 'use', 3.00, 1),
(1270, 1039, 1168, 'Les Quatre Accords toltèques', 'use', 2.70, 1),
(1270, 1014, 1172, 'Born to run', 'use', 3.00, 1),
(1270, 1013, 1161, 'Je sais cuisiner', 'neuf', 22.90, 2),
(1271, 1015, 1174, 'Le Potager du paresseux', 'tres bon', 5.80, 1),
(1271, 1014, 1170, 'Born to run', 'tres bon', 5.90, 1),
(1271, 1039, 1167, 'Les Quatre Accords toltèques', 'bon', 4.00, 1),
(1272, 1015, 1173, 'Le Potager du paresseux', 'neuf', 9.70, 1),
(1272, 1014, 1169, 'Born to run', 'neuf', 9.90, 1),
(1273, 1007, 1117, 'ABC du Bac - Mathématiques Terminale spécialité - Annales 2025', 'neuf', 9.95, 1),
(1273, 1029, 1033, 'Hunger Games - Tome 1', 'neuf', 8.95, 1),
(1273, 1013, 1163, 'Je sais cuisiner', 'bon', 10.30, 1),
(1274, 1039, 1165, 'Les Quatre Accords toltèques', 'neuf', 8.90, 1),
(1275, 1012, 1158, 'Bescherelle - L''anglais pour tous', 'tres bon', 9.50, 1),
(1275, 1016, 1177, 'Les Ombres de la Croix-Rousse', 'neuf', 18.00, 1),
(1275, 1019, 1029, 'Matilda', 'neuf', 8.20, 1),
(1276, 1011, 1155, 'Le Charme discret de l''intestin', 'bon', 4.40, 1),
(1276, 1023, 1010, 'Watchmen', 'tres bon', 21.00, 1),
(1277, 1029, 1033, 'Hunger Games - Tome 1', 'neuf', 8.95, 1),
(1277, 1031, 1001, 'Astérix le Gaulois', 'neuf', 10.99, 2),
(1277, 1030, 1017, 'La Chenille qui fait des trous', 'neuf', 10.00, 1),
(1278, 1019, 1032, 'Matilda', 'use', 2.50, 1),
(1280, 1017, 1178, 'Pixel et Moustache - Tome 1 : Le chat qui voulait voler', 'neuf', 12.50, 2),
(1283, 1004, 1103, 'Une chambre à soi', 'bon', 3.20, 1),
(1283, 1030, 1018, 'La Chenille qui fait des trous', 'tres bon', 6.00, 1),
(1283, 1001, 1037, 'La Passe-miroir - Livre 1 : Les Fiancés de l''hiver', 'neuf', 10.90, 1),
(1284, 1027, 1143, 'Coder proprement', 'bon', 15.80, 1),
(1285, 1036, 1083, 'Le Petit Prince', 'bon', 3.10, 1);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) VALUES
(1286, 1017, 1178, 'Pixel et Moustache - Tome 1 : Le chat qui voulait voler', 'neuf', 12.50, 1),
(1286, 1036, 1083, 'Le Petit Prince', 'bon', 3.10, 2),
(1287, 1017, 1178, 'Pixel et Moustache - Tome 1 : Le chat qui voulait voler', 'neuf', 12.50, 1),
(1289, 1004, 1102, 'Une chambre à soi', 'tres bon', 4.30, 1),
(1290, 1022, 1147, 'Introduction à l''algorithmique', 'bon', 31.10, 2),
(1290, 1028, 1006, 'Blacksad - Tome 1 : Quelque part entre les ombres', 'tres bon', 9.00, 1),
(1292, 1016, 1177, 'Les Ombres de la Croix-Rousse', 'neuf', 18.00, 1),
(1293, 1030, 1019, 'La Chenille qui fait des trous', 'bon', 4.50, 1),
(1293, 1032, 1021, 'Max et les Maximonstres', 'neuf', 13.20, 1),
(1294, 1031, 1001, 'Astérix le Gaulois', 'neuf', 10.99, 1),
(1294, 1047, 1025, 'Le Petit Nicolas', 'neuf', 7.40, 1),
(1296, 1031, 1001, 'Astérix le Gaulois', 'neuf', 10.99, 1),
(1297, 1032, 1022, 'Max et les Maximonstres', 'tres bon', 7.90, 1),
(1298, 1013, 1162, 'Je sais cuisiner', 'tres bon', 13.70, 1),
(1298, 1001, 1038, 'La Passe-miroir - Livre 1 : Les Fiancés de l''hiver', 'tres bon', 6.50, 1),
(1299, 1037, 1042, 'Chanson douce', 'tres bon', 5.10, 1),
(1299, 1038, 1013, 'One Piece - Édition originale - Tome 01', 'neuf', 7.20, 1),
(1300, 1008, 1122, 'J''intègre - Maths MPSI-MP2I : exercices incontournables', 'tres bon', 17.90, 1),
(1301, 1017, 1178, 'Pixel et Moustache - Tome 1 : Le chat qui voulait voler', 'neuf', 12.50, 1),
(1301, 1027, 1141, 'Coder proprement', 'neuf', 35.00, 1),
(1302, 1038, 1013, 'One Piece - Édition originale - Tome 01', 'neuf', 7.20, 1),
(1302, 1029, 1034, 'Hunger Games - Tome 1', 'tres bon', 5.40, 1),
(1302, 1032, 1023, 'Max et les Maximonstres', 'bon', 5.90, 1),
(1303, 1030, 1017, 'La Chenille qui fait des trous', 'neuf', 10.00, 1),
(1304, 1001, 1039, 'La Passe-miroir - Livre 1 : Les Fiancés de l''hiver', 'bon', 4.90, 1),
(1305, 1031, 1004, 'Astérix le Gaulois', 'use', 3.30, 1),
(1305, 1001, 1039, 'La Passe-miroir - Livre 1 : Les Fiancés de l''hiver', 'bon', 4.90, 1),
(1306, 1029, 1035, 'Hunger Games - Tome 1', 'bon', 4.00, 2),
(1307, 1023, 1009, 'Watchmen', 'neuf', 35.00, 1),
(1307, 1032, 1022, 'Max et les Maximonstres', 'tres bon', 7.90, 1),
(1308, 1017, 1178, 'Pixel et Moustache - Tome 1 : Le chat qui voulait voler', 'neuf', 12.50, 1),
(1308, 1035, 1085, 'Sapiens : une brève histoire de l''humanité', 'neuf', 24.90, 1),
(1309, 1009, 1129, 'Lexique des termes juridiques 2025-2026', 'neuf', 16.90, 1),
(1310, 1003, 1090, 'Histoire de l''art', 'tres bon', 12.00, 1),
(1310, 1040, 1137, 'Principes de l''économie', 'neuf', 59.00, 1),
(1310, 1030, 1019, 'La Chenille qui fait des trous', 'bon', 4.50, 1),
(1311, 1003, 1092, 'Histoire de l''art', 'use', 6.00, 2),
(1312, 1020, 1133, 'Le Capital au XXIe siècle', 'neuf', 13.90, 1),
(1312, 1041, 1095, 'Hitchcock / Truffaut - Édition définitive', 'bon', 14.40, 1),
(1313, 1003, 1090, 'Histoire de l''art', 'tres bon', 12.00, 1),
(1314, 1044, 1107, 'Comme un roman', 'bon', 3.40, 1),
(1315, 1038, 1013, 'One Piece - Édition originale - Tome 01', 'neuf', 7.20, 1),
(1315, 1004, 1101, 'Une chambre à soi', 'neuf', 7.10, 1),
(1315, 1024, 1100, 'L''Usage du monde', 'use', 3.60, 1),
(1316, 1025, 1061, '1984', 'neuf', 9.30, 1),
(1317, 1038, 1014, 'One Piece - Édition originale - Tome 01', 'tres bon', 4.30, 1),
(1318, 1044, 1107, 'Comme un roman', 'bon', 3.40, 1),
(1318, 1041, 1093, 'Hitchcock / Truffaut - Édition définitive', 'neuf', 32.00, 1),
(1318, 1004, 1103, 'Une chambre à soi', 'bon', 3.20, 1),
(1319, 1043, 1050, 'La Vérité sur l''affaire Harry Quebert', 'tres bon', 6.90, 1),
(1319, 1024, 1099, 'L''Usage du monde', 'bon', 5.40, 2),
(1320, 1024, 1098, 'L''Usage du monde', 'tres bon', 7.20, 1),
(1320, 1006, 1114, 'Bescherelle - La conjugaison pour tous', 'tres bon', 6.00, 1),
(1321, 1041, 1095, 'Hitchcock / Truffaut - Édition définitive', 'bon', 14.40, 1),
(1321, 1019, 1032, 'Matilda', 'use', 2.50, 1),
(1322, 1008, 1123, 'J''intègre - Maths MPSI-MP2I : exercices incontournables', 'bon', 13.50, 1),
(1322, 1014, 1170, 'Born to run', 'tres bon', 5.90, 1),
(1322, 1044, 1107, 'Comme un roman', 'bon', 3.40, 1),
(1323, 1024, 1098, 'L''Usage du monde', 'tres bon', 7.20, 1),
(1323, 1044, 1105, 'Comme un roman', 'neuf', 7.50, 1),
(1323, 1004, 1102, 'Une chambre à soi', 'tres bon', 4.30, 1),
(1324, 1003, 1089, 'Histoire de l''art', 'neuf', 19.95, 1),
(1324, 1042, 1076, 'Cyrano de Bergerac', 'use', 2.00, 1),
(1325, 1024, 1100, 'L''Usage du monde', 'use', 3.60, 1),
(1325, 1040, 1138, 'Principes de l''économie', 'tres bon', 35.40, 1),
(1325, 1014, 1171, 'Born to run', 'bon', 4.50, 1),
(1326, 1044, 1105, 'Comme un roman', 'neuf', 7.50, 1),
(1326, 1003, 1089, 'Histoire de l''art', 'neuf', 19.95, 1),
(1327, 1007, 1117, 'ABC du Bac - Mathématiques Terminale spécialité - Annales 2025', 'neuf', 9.95, 1),
(1327, 1027, 1142, 'Coder proprement', 'tres bon', 21.00, 1),
(1329, 1020, 1133, 'Le Capital au XXIe siècle', 'neuf', 13.90, 1),
(1330, 1003, 1090, 'Histoire de l''art', 'tres bon', 12.00, 1),
(1330, 1039, 1168, 'Les Quatre Accords toltèques', 'use', 2.70, 1),
(1331, 1024, 1100, 'L''Usage du monde', 'use', 3.60, 1),
(1331, 1031, 1003, 'Astérix le Gaulois', 'bon', 4.90, 1),
(1331, 1022, 1145, 'Introduction à l''algorithmique', 'neuf', 69.00, 1),
(1332, 1004, 1101, 'Une chambre à soi', 'neuf', 7.10, 1),
(1332, 1024, 1097, 'L''Usage du monde', 'neuf', 12.00, 1),
(1332, 1015, 1176, 'Le Potager du paresseux', 'use', 2.90, 1),
(1333, 1043, 1050, 'La Vérité sur l''affaire Harry Quebert', 'tres bon', 6.90, 1),
(1334, 1004, 1102, 'Une chambre à soi', 'tres bon', 4.30, 2),
(1334, 1044, 1106, 'Comme un roman', 'tres bon', 4.50, 1),
(1335, 1036, 1084, 'Le Petit Prince', 'use', 2.10, 2),
(1336, 1035, 1087, 'Sapiens : une brève histoire de l''humanité', 'bon', 11.20, 2),
(1336, 1012, 1159, 'Bescherelle - L''anglais pour tous', 'bon', 7.20, 1),
(1336, 1032, 1022, 'Max et les Maximonstres', 'tres bon', 7.90, 1),
(1337, 1017, 1178, 'Pixel et Moustache - Tome 1 : Le chat qui voulait voler', 'neuf', 12.50, 1),
(1338, 1035, 1085, 'Sapiens : une brève histoire de l''humanité', 'neuf', 24.90, 1),
(1338, 1012, 1157, 'Bescherelle - L''anglais pour tous', 'neuf', 15.90, 1),
(1339, 1001, 1037, 'La Passe-miroir - Livre 1 : Les Fiancés de l''hiver', 'neuf', 10.90, 1),
(1340, 1003, 1091, 'Histoire de l''art', 'bon', 9.00, 1),
(1340, 1024, 1099, 'L''Usage du monde', 'bon', 5.40, 1),
(1342, 1023, 1009, 'Watchmen', 'neuf', 35.00, 1),
(1342, 1024, 1097, 'L''Usage du monde', 'neuf', 12.00, 1),
(1342, 1004, 1101, 'Une chambre à soi', 'neuf', 7.10, 1),
(1343, 1027, 1142, 'Coder proprement', 'tres bon', 21.00, 1),
(1344, 1044, 1106, 'Comme un roman', 'tres bon', 4.50, 1),
(1345, 1006, 1114, 'Bescherelle - La conjugaison pour tous', 'tres bon', 6.00, 1),
(1346, 1024, 1099, 'L''Usage du monde', 'bon', 5.40, 1),
(1346, 1044, 1108, 'Comme un roman', 'use', 2.30, 2),
(1346, 1023, 1010, 'Watchmen', 'tres bon', 21.00, 1),
(1347, 1041, 1094, 'Hitchcock / Truffaut - Édition définitive', 'tres bon', 19.20, 1),
(1348, 1044, 1106, 'Comme un roman', 'tres bon', 4.50, 1),
(1348, 1046, 1079, 'Madame Bovary', 'bon', 2.70, 1),
(1349, 1041, 1095, 'Hitchcock / Truffaut - Édition définitive', 'bon', 14.40, 1),
(1349, 1035, 1087, 'Sapiens : une brève histoire de l''humanité', 'bon', 11.20, 1),
(1349, 1044, 1107, 'Comme un roman', 'bon', 3.40, 1),
(1351, 1013, 1162, 'Je sais cuisiner', 'tres bon', 13.70, 1),
(1352, 1024, 1098, 'L''Usage du monde', 'tres bon', 7.20, 1),
(1352, 1035, 1086, 'Sapiens : une brève histoire de l''humanité', 'tres bon', 14.90, 1),
(1353, 1028, 1005, 'Blacksad - Tome 1 : Quelque part entre les ombres', 'neuf', 14.99, 1),
(1353, 1040, 1138, 'Principes de l''économie', 'tres bon', 35.40, 1),
(1354, 1045, 1126, 'Campbell Biologie (9e édition)', 'tres bon', 59.40, 2),
(1355, 1020, 1135, 'Le Capital au XXIe siècle', 'bon', 6.30, 1),
(1356, 1012, 1159, 'Bescherelle - L''anglais pour tous', 'bon', 7.20, 1),
(1356, 1011, 1154, 'Le Charme discret de l''intestin', 'tres bon', 5.80, 1),
(1356, 1021, 1048, 'L''Anomalie', 'use', 2.80, 1),
(1357, 1015, 1173, 'Le Potager du paresseux', 'neuf', 9.70, 1),
(1358, 1022, 1145, 'Introduction à l''algorithmique', 'neuf', 69.00, 1),
(1359, 1040, 1138, 'Principes de l''économie', 'tres bon', 35.40, 1),
(1359, 1020, 1133, 'Le Capital au XXIe siècle', 'neuf', 13.90, 1),
(1360, 1022, 1146, 'Introduction à l''algorithmique', 'tres bon', 41.40, 1),
(1360, 1027, 1143, 'Coder proprement', 'bon', 15.80, 1),
(1361, 1027, 1143, 'Coder proprement', 'bon', 15.80, 1),
(1362, 1012, 1159, 'Bescherelle - L''anglais pour tous', 'bon', 7.20, 2),
(1362, 1011, 1155, 'Le Charme discret de l''intestin', 'bon', 4.40, 2),
(1363, 1029, 1033, 'Hunger Games - Tome 1', 'neuf', 8.95, 1),
(1363, 1010, 1151, 'Une brève histoire du temps', 'bon', 4.10, 1),
(1363, 1012, 1160, 'Bescherelle - L''anglais pour tous', 'use', 4.80, 1),
(1364, 1030, 1020, 'La Chenille qui fait des trous', 'use', 3.00, 2),
(1364, 1009, 1131, 'Lexique des termes juridiques 2025-2026', 'bon', 7.60, 2),
(1364, 1022, 1145, 'Introduction à l''algorithmique', 'neuf', 69.00, 1),
(1365, 1011, 1155, 'Le Charme discret de l''intestin', 'bon', 4.40, 1),
(1365, 1023, 1009, 'Watchmen', 'neuf', 35.00, 1),
(1366, 1010, 1149, 'Une brève histoire du temps', 'neuf', 9.00, 1),
(1367, 1044, 1106, 'Comme un roman', 'tres bon', 4.50, 1),
(1367, 1021, 1047, 'L''Anomalie', 'bon', 4.10, 2),
(1367, 1003, 1090, 'Histoire de l''art', 'tres bon', 12.00, 1),
(1368, 1001, 1037, 'La Passe-miroir - Livre 1 : Les Fiancés de l''hiver', 'neuf', 10.90, 1),
(1368, 1004, 1101, 'Une chambre à soi', 'neuf', 7.10, 1),
(1369, 1034, 1067, 'Avant toi', 'bon', 3.70, 1),
(1370, 1044, 1105, 'Comme un roman', 'neuf', 7.50, 1),
(1371, 1041, 1094, 'Hitchcock / Truffaut - Édition définitive', 'tres bon', 19.20, 1),
(1372, 1020, 1135, 'Le Capital au XXIe siècle', 'bon', 6.30, 1),
(1372, 1010, 1149, 'Une brève histoire du temps', 'neuf', 9.00, 1),
(1372, 1024, 1097, 'L''Usage du monde', 'neuf', 12.00, 1),
(1373, 1004, 1101, 'Une chambre à soi', 'neuf', 7.10, 1),
(1374, 1004, 1104, 'Une chambre à soi', 'use', 2.10, 2),
(1376, 1009, 1131, 'Lexique des termes juridiques 2025-2026', 'bon', 7.60, 1),
(1376, 1024, 1098, 'L''Usage du monde', 'tres bon', 7.20, 1);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) VALUES
(1377, 1003, 1091, 'Histoire de l''art', 'bon', 9.00, 1),
(1377, 1004, 1101, 'Une chambre à soi', 'neuf', 7.10, 2),
(1378, 1024, 1099, 'L''Usage du monde', 'bon', 5.40, 2),
(1380, 1004, 1102, 'Une chambre à soi', 'tres bon', 4.30, 1),
(1380, 1007, 1117, 'ABC du Bac - Mathématiques Terminale spécialité - Annales 2025', 'neuf', 9.95, 1),
(1381, 1041, 1093, 'Hitchcock / Truffaut - Édition définitive', 'neuf', 32.00, 2),
(1381, 1004, 1104, 'Une chambre à soi', 'use', 2.10, 1),
(1382, 1041, 1096, 'Hitchcock / Truffaut - Édition définitive', 'use', 9.60, 1),
(1382, 1003, 1092, 'Histoire de l''art', 'use', 6.00, 1),
(1383, 1017, 1178, 'Pixel et Moustache - Tome 1 : Le chat qui voulait voler', 'neuf', 12.50, 1),
(1384, 1044, 1105, 'Comme un roman', 'neuf', 7.50, 1),
(1384, 1003, 1091, 'Histoire de l''art', 'bon', 9.00, 1),
(1385, 1013, 1161, 'Je sais cuisiner', 'neuf', 22.90, 1),
(1386, 1011, 1154, 'Le Charme discret de l''intestin', 'tres bon', 5.80, 2),
(1386, 1004, 1103, 'Une chambre à soi', 'bon', 3.20, 1),
(1387, 1024, 1100, 'L''Usage du monde', 'use', 3.60, 1),
(1388, 1032, 1024, 'Max et les Maximonstres', 'use', 4.00, 1),
(1389, 1003, 1092, 'Histoire de l''art', 'use', 6.00, 1),
(1390, 1024, 1097, 'L''Usage du monde', 'neuf', 12.00, 1),
(1391, 1043, 1051, 'La Vérité sur l''affaire Harry Quebert', 'bon', 5.20, 1),
(1391, 1004, 1102, 'Une chambre à soi', 'tres bon', 4.30, 1),
(1391, 1033, 1059, 'Dune - Tome 1', 'bon', 5.20, 1),
(1392, 1035, 1088, 'Sapiens : une brève histoire de l''humanité', 'use', 7.50, 1),
(1393, 1009, 1130, 'Lexique des termes juridiques 2025-2026', 'tres bon', 10.10, 1),
(1393, 1021, 1045, 'L''Anomalie', 'neuf', 9.20, 1),
(1394, 1041, 1094, 'Hitchcock / Truffaut - Édition définitive', 'tres bon', 19.20, 1),
(1394, 1005, 1110, 'Le Robert Junior illustré', 'tres bon', 15.50, 1),
(1395, 1024, 1099, 'L''Usage du monde', 'bon', 5.40, 1),
(1395, 1039, 1165, 'Les Quatre Accords toltèques', 'neuf', 8.90, 1),
(1396, 1004, 1103, 'Une chambre à soi', 'bon', 3.20, 1),
(1396, 1003, 1092, 'Histoire de l''art', 'use', 6.00, 1),
(1397, 1041, 1096, 'Hitchcock / Truffaut - Édition définitive', 'use', 9.60, 1),
(1397, 1024, 1099, 'L''Usage du monde', 'bon', 5.40, 1),
(1399, 1010, 1151, 'Une brève histoire du temps', 'bon', 4.10, 1),
(1400, 1016, 1177, 'Les Ombres de la Croix-Rousse', 'neuf', 18.00, 1),
(1402, 1004, 1102, 'Une chambre à soi', 'tres bon', 4.30, 1),
(1402, 1036, 1081, 'Le Petit Prince', 'neuf', 6.90, 1),
(1403, 1041, 1093, 'Hitchcock / Truffaut - Édition définitive', 'neuf', 32.00, 1),
(1405, 1041, 1095, 'Hitchcock / Truffaut - Édition définitive', 'bon', 14.40, 1),
(1405, 1044, 1105, 'Comme un roman', 'neuf', 7.50, 1),
(1405, 1003, 1090, 'Histoire de l''art', 'tres bon', 12.00, 1),
(1406, 1004, 1104, 'Une chambre à soi', 'use', 2.10, 1),
(1406, 1035, 1086, 'Sapiens : une brève histoire de l''humanité', 'tres bon', 14.90, 1),
(1406, 1016, 1177, 'Les Ombres de la Croix-Rousse', 'neuf', 18.00, 1),
(1407, 1035, 1087, 'Sapiens : une brève histoire de l''humanité', 'bon', 11.20, 1),
(1408, 1044, 1108, 'Comme un roman', 'use', 2.30, 1),
(1408, 1035, 1087, 'Sapiens : une brève histoire de l''humanité', 'bon', 11.20, 2),
(1409, 1044, 1108, 'Comme un roman', 'use', 2.30, 2),
(1409, 1024, 1097, 'L''Usage du monde', 'neuf', 12.00, 1),
(1410, 1033, 1057, 'Dune - Tome 1', 'neuf', 11.50, 1),
(1410, 1024, 1098, 'L''Usage du monde', 'tres bon', 7.20, 1),
(1411, 1042, 1076, 'Cyrano de Bergerac', 'use', 2.00, 1),
(1412, 1021, 1046, 'L''Anomalie', 'tres bon', 5.50, 1),
(1413, 1025, 1062, '1984', 'tres bon', 5.60, 1),
(1414, 1024, 1100, 'L''Usage du monde', 'use', 3.60, 1),
(1415, 1021, 1045, 'L''Anomalie', 'neuf', 9.20, 1),
(1417, 1021, 1045, 'L''Anomalie', 'neuf', 9.20, 1),
(1419, 1034, 1067, 'Avant toi', 'bon', 3.70, 1),
(1419, 1046, 1078, 'Madame Bovary', 'tres bon', 3.50, 1),
(1420, 1034, 1065, 'Avant toi', 'neuf', 8.20, 1),
(1421, 1028, 1007, 'Blacksad - Tome 1 : Quelque part entre les ombres', 'bon', 6.70, 1),
(1421, 1020, 1135, 'Le Capital au XXIe siècle', 'bon', 6.30, 1),
(1422, 1017, 1178, 'Pixel et Moustache - Tome 1 : Le chat qui voulait voler', 'neuf', 12.50, 1),
(1422, 1008, 1121, 'J''intègre - Maths MPSI-MP2I : exercices incontournables', 'neuf', 29.90, 1),
(1423, 1002, 1069, 'Les Fleurs du mal', 'neuf', 4.90, 1),
(1424, 1011, 1155, 'Le Charme discret de l''intestin', 'bon', 4.40, 1),
(1424, 1026, 1055, 'Les Rivières pourpres', 'bon', 4.10, 1),
(1425, 1036, 1082, 'Le Petit Prince', 'tres bon', 4.10, 1),
(1426, 1025, 1062, '1984', 'tres bon', 5.60, 1),
(1428, 1033, 1057, 'Dune - Tome 1', 'neuf', 11.50, 1),
(1428, 1026, 1054, 'Les Rivières pourpres', 'tres bon', 5.50, 1),
(1429, 1026, 1056, 'Les Rivières pourpres', 'use', 2.80, 1),
(1429, 1016, 1177, 'Les Ombres de la Croix-Rousse', 'neuf', 18.00, 1),
(1430, 1016, 1177, 'Les Ombres de la Croix-Rousse', 'neuf', 18.00, 2),
(1430, 1038, 1016, 'One Piece - Édition originale - Tome 01', 'use', 2.20, 1),
(1431, 1033, 1060, 'Dune - Tome 1', 'use', 3.50, 1),
(1431, 1026, 1056, 'Les Rivières pourpres', 'use', 2.80, 1),
(1432, 1025, 1061, '1984', 'neuf', 9.30, 1),
(1432, 1033, 1059, 'Dune - Tome 1', 'bon', 5.20, 1),
(1433, 1026, 1053, 'Les Rivières pourpres', 'neuf', 9.20, 1),
(1433, 1042, 1075, 'Cyrano de Bergerac', 'bon', 3.00, 1),
(1434, 1029, 1033, 'Hunger Games - Tome 1', 'neuf', 8.95, 1),
(1435, 1042, 1074, 'Cyrano de Bergerac', 'tres bon', 4.00, 1),
(1435, 1006, 1113, 'Bescherelle - La conjugaison pour tous', 'neuf', 9.95, 1),
(1436, 1043, 1050, 'La Vérité sur l''affaire Harry Quebert', 'tres bon', 6.90, 1),
(1437, 1045, 1125, 'Campbell Biologie (9e édition)', 'neuf', 99.00, 1),
(1437, 1036, 1083, 'Le Petit Prince', 'bon', 3.10, 1),
(1437, 1025, 1063, '1984', 'bon', 4.20, 1),
(1438, 1008, 1124, 'J''intègre - Maths MPSI-MP2I : exercices incontournables', 'use', 9.00, 1),
(1438, 1045, 1128, 'Campbell Biologie (9e édition)', 'use', 29.70, 2),
(1439, 1005, 1109, 'Le Robert Junior illustré', 'neuf', 25.90, 1),
(1439, 1007, 1120, 'ABC du Bac - Mathématiques Terminale spécialité - Annales 2025', 'use', 3.00, 1),
(1440, 1006, 1115, 'Bescherelle - La conjugaison pour tous', 'bon', 4.50, 1),
(1441, 1007, 1120, 'ABC du Bac - Mathématiques Terminale spécialité - Annales 2025', 'use', 3.00, 1),
(1441, 1006, 1113, 'Bescherelle - La conjugaison pour tous', 'neuf', 9.95, 1),
(1441, 1045, 1125, 'Campbell Biologie (9e édition)', 'neuf', 99.00, 1),
(1442, 1007, 1118, 'ABC du Bac - Mathématiques Terminale spécialité - Annales 2025', 'tres bon', 6.00, 1),
(1442, 1006, 1114, 'Bescherelle - La conjugaison pour tous', 'tres bon', 6.00, 1),
(1442, 1012, 1157, 'Bescherelle - L''anglais pour tous', 'neuf', 15.90, 1),
(1443, 1020, 1135, 'Le Capital au XXIe siècle', 'bon', 6.30, 1),
(1443, 1005, 1110, 'Le Robert Junior illustré', 'tres bon', 15.50, 1),
(1443, 1027, 1141, 'Coder proprement', 'neuf', 35.00, 1),
(1444, 1007, 1117, 'ABC du Bac - Mathématiques Terminale spécialité - Annales 2025', 'neuf', 9.95, 1),
(1444, 1028, 1008, 'Blacksad - Tome 1 : Quelque part entre les ombres', 'use', 4.50, 1),
(1447, 1006, 1115, 'Bescherelle - La conjugaison pour tous', 'bon', 4.50, 1),
(1448, 1006, 1113, 'Bescherelle - La conjugaison pour tous', 'neuf', 9.95, 1),
(1448, 1005, 1110, 'Le Robert Junior illustré', 'tres bon', 15.50, 1),
(1448, 1045, 1125, 'Campbell Biologie (9e édition)', 'neuf', 99.00, 1),
(1449, 1035, 1086, 'Sapiens : une brève histoire de l''humanité', 'tres bon', 14.90, 1),
(1450, 1020, 1135, 'Le Capital au XXIe siècle', 'bon', 6.30, 1),
(1450, 1006, 1115, 'Bescherelle - La conjugaison pour tous', 'bon', 4.50, 2),
(1451, 1006, 1114, 'Bescherelle - La conjugaison pour tous', 'tres bon', 6.00, 1),
(1452, 1046, 1078, 'Madame Bovary', 'tres bon', 3.50, 2),
(1452, 1007, 1118, 'ABC du Bac - Mathématiques Terminale spécialité - Annales 2025', 'tres bon', 6.00, 1),
(1452, 1027, 1142, 'Coder proprement', 'tres bon', 21.00, 2),
(1453, 1010, 1151, 'Une brève histoire du temps', 'bon', 4.10, 2),
(1453, 1012, 1159, 'Bescherelle - L''anglais pour tous', 'bon', 7.20, 2),
(1453, 1027, 1142, 'Coder proprement', 'tres bon', 21.00, 1),
(1453, 1040, 1138, 'Principes de l''économie', 'tres bon', 35.40, 1),
(1454, 1022, 1145, 'Introduction à l''algorithmique', 'neuf', 69.00, 1),
(1454, 1046, 1079, 'Madame Bovary', 'bon', 2.70, 1),
(1454, 1040, 1137, 'Principes de l''économie', 'neuf', 59.00, 1),
(1454, 1011, 1156, 'Le Charme discret de l''intestin', 'use', 2.90, 1),
(1455, 1010, 1150, 'Une brève histoire du temps', 'tres bon', 5.40, 1),
(1455, 1040, 1140, 'Principes de l''économie', 'use', 17.70, 1),
(1456, 1019, 1031, 'Matilda', 'bon', 3.70, 1),
(1456, 1007, 1117, 'ABC du Bac - Mathématiques Terminale spécialité - Annales 2025', 'neuf', 9.95, 1),
(1456, 1024, 1098, 'L''Usage du monde', 'tres bon', 7.20, 2),
(1456, 1010, 1151, 'Une brève histoire du temps', 'bon', 4.10, 1),
(1456, 1040, 1139, 'Principes de l''économie', 'bon', 26.60, 1),
(1457, 1040, 1140, 'Principes de l''économie', 'use', 17.70, 1),
(1457, 1010, 1151, 'Une brève histoire du temps', 'bon', 4.10, 1),
(1457, 1032, 1022, 'Max et les Maximonstres', 'tres bon', 7.90, 1),
(1458, 1001, 1040, 'La Passe-miroir - Livre 1 : Les Fiancés de l''hiver', 'use', 3.30, 1),
(1458, 1017, 1178, 'Pixel et Moustache - Tome 1 : Le chat qui voulait voler', 'neuf', 12.50, 2),
(1458, 1016, 1177, 'Les Ombres de la Croix-Rousse', 'neuf', 18.00, 1),
(1458, 1011, 1153, 'Le Charme discret de l''intestin', 'neuf', 9.70, 2),
(1458, 1022, 1145, 'Introduction à l''algorithmique', 'neuf', 69.00, 1),
(1459, 1020, 1133, 'Le Capital au XXIe siècle', 'neuf', 13.90, 1),
(1459, 1011, 1154, 'Le Charme discret de l''intestin', 'tres bon', 5.80, 1),
(1460, 1010, 1152, 'Une brève histoire du temps', 'use', 2.70, 1),
(1460, 1022, 1145, 'Introduction à l''algorithmique', 'neuf', 69.00, 1),
(1460, 1008, 1124, 'J''intègre - Maths MPSI-MP2I : exercices incontournables', 'use', 9.00, 1),
(1460, 1012, 1157, 'Bescherelle - L''anglais pour tous', 'neuf', 15.90, 1),
(1461, 1012, 1157, 'Bescherelle - L''anglais pour tous', 'neuf', 15.90, 1),
(1461, 1024, 1097, 'L''Usage du monde', 'neuf', 12.00, 1),
(1462, 1042, 1074, 'Cyrano de Bergerac', 'tres bon', 4.00, 1),
(1462, 1027, 1141, 'Coder proprement', 'neuf', 35.00, 1),
(1462, 1022, 1146, 'Introduction à l''algorithmique', 'tres bon', 41.40, 2),
(1463, 1011, 1156, 'Le Charme discret de l''intestin', 'use', 2.90, 1);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) VALUES
(1463, 1020, 1134, 'Le Capital au XXIe siècle', 'tres bon', 8.30, 1),
(1463, 1022, 1147, 'Introduction à l''algorithmique', 'bon', 31.10, 1),
(1464, 1040, 1137, 'Principes de l''économie', 'neuf', 59.00, 1),
(1464, 1009, 1131, 'Lexique des termes juridiques 2025-2026', 'bon', 7.60, 1),
(1464, 1023, 1009, 'Watchmen', 'neuf', 35.00, 1),
(1464, 1029, 1033, 'Hunger Games - Tome 1', 'neuf', 8.95, 1),
(1464, 1011, 1155, 'Le Charme discret de l''intestin', 'bon', 4.40, 1),
(1465, 1008, 1121, 'J''intègre - Maths MPSI-MP2I : exercices incontournables', 'neuf', 29.90, 1),
(1465, 1046, 1078, 'Madame Bovary', 'tres bon', 3.50, 1),
(1466, 1042, 1073, 'Cyrano de Bergerac', 'neuf', 6.70, 1),
(1466, 1019, 1029, 'Matilda', 'neuf', 8.20, 1),
(1466, 1009, 1130, 'Lexique des termes juridiques 2025-2026', 'tres bon', 10.10, 1),
(1467, 1022, 1146, 'Introduction à l''algorithmique', 'tres bon', 41.40, 1),
(1467, 1003, 1092, 'Histoire de l''art', 'use', 6.00, 1),
(1467, 1007, 1117, 'ABC du Bac - Mathématiques Terminale spécialité - Annales 2025', 'neuf', 9.95, 1),
(1468, 1012, 1157, 'Bescherelle - L''anglais pour tous', 'neuf', 15.90, 1),
(1468, 1011, 1153, 'Le Charme discret de l''intestin', 'neuf', 9.70, 1),
(1468, 1010, 1150, 'Une brève histoire du temps', 'tres bon', 5.40, 1),
(1468, 1027, 1144, 'Coder proprement', 'use', 10.50, 1),
(1468, 1020, 1134, 'Le Capital au XXIe siècle', 'tres bon', 8.30, 1),
(1469, 1017, 1178, 'Pixel et Moustache - Tome 1 : Le chat qui voulait voler', 'neuf', 12.50, 1),
(1469, 1029, 1033, 'Hunger Games - Tome 1', 'neuf', 8.95, 1),
(1469, 1031, 1003, 'Astérix le Gaulois', 'bon', 4.90, 1),
(1469, 1023, 1011, 'Watchmen', 'bon', 15.80, 2),
(1470, 1039, 1166, 'Les Quatre Accords toltèques', 'tres bon', 5.30, 2),
(1470, 1001, 1037, 'La Passe-miroir - Livre 1 : Les Fiancés de l''hiver', 'neuf', 10.90, 1),
(1471, 1024, 1098, 'L''Usage du monde', 'tres bon', 7.20, 1),
(1471, 1027, 1143, 'Coder proprement', 'bon', 15.80, 1),
(1472, 1029, 1033, 'Hunger Games - Tome 1', 'neuf', 8.95, 1),
(1472, 1023, 1009, 'Watchmen', 'neuf', 35.00, 1),
(1473, 1032, 1022, 'Max et les Maximonstres', 'tres bon', 7.90, 1),
(1474, 1009, 1131, 'Lexique des termes juridiques 2025-2026', 'bon', 7.60, 1),
(1474, 1047, 1026, 'Le Petit Nicolas', 'tres bon', 4.40, 1),
(1474, 1017, 1178, 'Pixel et Moustache - Tome 1 : Le chat qui voulait voler', 'neuf', 12.50, 1),
(1475, 1038, 1014, 'One Piece - Édition originale - Tome 01', 'tres bon', 4.30, 1),
(1475, 1023, 1012, 'Watchmen', 'use', 10.50, 1),
(1475, 1041, 1094, 'Hitchcock / Truffaut - Édition définitive', 'tres bon', 19.20, 1),
(1475, 1011, 1154, 'Le Charme discret de l''intestin', 'tres bon', 5.80, 1),
(1476, 1047, 1025, 'Le Petit Nicolas', 'neuf', 7.40, 1),
(1476, 1044, 1107, 'Comme un roman', 'bon', 3.40, 2),
(1477, 1029, 1034, 'Hunger Games - Tome 1', 'tres bon', 5.40, 1),
(1477, 1017, 1178, 'Pixel et Moustache - Tome 1 : Le chat qui voulait voler', 'neuf', 12.50, 2),
(1478, 1019, 1029, 'Matilda', 'neuf', 8.20, 1),
(1479, 1038, 1015, 'One Piece - Édition originale - Tome 01', 'bon', 3.20, 1),
(1479, 1001, 1037, 'La Passe-miroir - Livre 1 : Les Fiancés de l''hiver', 'neuf', 10.90, 1),
(1479, 1047, 1028, 'Le Petit Nicolas', 'use', 2.20, 1),
(1479, 1028, 1007, 'Blacksad - Tome 1 : Quelque part entre les ombres', 'bon', 6.70, 1),
(1480, 1038, 1014, 'One Piece - Édition originale - Tome 01', 'tres bon', 4.30, 1),
(1480, 1006, 1113, 'Bescherelle - La conjugaison pour tous', 'neuf', 9.95, 1),
(1481, 1013, 1161, 'Je sais cuisiner', 'neuf', 22.90, 1),
(1481, 1034, 1067, 'Avant toi', 'bon', 3.70, 1),
(1481, 1029, 1036, 'Hunger Games - Tome 1', 'use', 2.70, 1),
(1481, 1019, 1030, 'Matilda', 'tres bon', 4.90, 1),
(1483, 1047, 1025, 'Le Petit Nicolas', 'neuf', 7.40, 1),
(1483, 1038, 1013, 'One Piece - Édition originale - Tome 01', 'neuf', 7.20, 1),
(1483, 1031, 1002, 'Astérix le Gaulois', 'tres bon', 6.60, 1),
(1483, 1024, 1099, 'L''Usage du monde', 'bon', 5.40, 2),
(1484, 1028, 1007, 'Blacksad - Tome 1 : Quelque part entre les ombres', 'bon', 6.70, 1),
(1484, 1003, 1090, 'Histoire de l''art', 'tres bon', 12.00, 2),
(1485, 1029, 1033, 'Hunger Games - Tome 1', 'neuf', 8.95, 2),
(1486, 1008, 1121, 'J''intègre - Maths MPSI-MP2I : exercices incontournables', 'neuf', 29.90, 1),
(1486, 1047, 1026, 'Le Petit Nicolas', 'tres bon', 4.40, 1),
(1486, 1036, 1081, 'Le Petit Prince', 'neuf', 6.90, 1),
(1486, 1028, 1008, 'Blacksad - Tome 1 : Quelque part entre les ombres', 'use', 4.50, 1),
(1486, 1017, 1178, 'Pixel et Moustache - Tome 1 : Le chat qui voulait voler', 'neuf', 12.50, 1),
(1487, 1032, 1024, 'Max et les Maximonstres', 'use', 4.00, 1),
(1487, 1001, 1037, 'La Passe-miroir - Livre 1 : Les Fiancés de l''hiver', 'neuf', 10.90, 2),
(1487, 1023, 1010, 'Watchmen', 'tres bon', 21.00, 1),
(1488, 1028, 1005, 'Blacksad - Tome 1 : Quelque part entre les ombres', 'neuf', 14.99, 1),
(1488, 1025, 1063, '1984', 'bon', 4.20, 1),
(1488, 1023, 1010, 'Watchmen', 'tres bon', 21.00, 1),
(1489, 1031, 1004, 'Astérix le Gaulois', 'use', 3.30, 1),
(1489, 1047, 1025, 'Le Petit Nicolas', 'neuf', 7.40, 1),
(1490, 1029, 1034, 'Hunger Games - Tome 1', 'tres bon', 5.40, 1),
(1490, 1023, 1012, 'Watchmen', 'use', 10.50, 1),
(1490, 1034, 1065, 'Avant toi', 'neuf', 8.20, 2),
(1490, 1030, 1019, 'La Chenille qui fait des trous', 'bon', 4.50, 1),
(1490, 1032, 1021, 'Max et les Maximonstres', 'neuf', 13.20, 1),
(1491, 1019, 1029, 'Matilda', 'neuf', 8.20, 1),
(1491, 1041, 1093, 'Hitchcock / Truffaut - Édition définitive', 'neuf', 32.00, 2),
(1492, 1022, 1147, 'Introduction à l''algorithmique', 'bon', 31.10, 1),
(1493, 1032, 1022, 'Max et les Maximonstres', 'tres bon', 7.90, 1),
(1493, 1008, 1123, 'J''intègre - Maths MPSI-MP2I : exercices incontournables', 'bon', 13.50, 1),
(1493, 1028, 1007, 'Blacksad - Tome 1 : Quelque part entre les ombres', 'bon', 6.70, 1),
(1493, 1017, 1178, 'Pixel et Moustache - Tome 1 : Le chat qui voulait voler', 'neuf', 12.50, 2),
(1494, 1028, 1007, 'Blacksad - Tome 1 : Quelque part entre les ombres', 'bon', 6.70, 1),
(1494, 1017, 1178, 'Pixel et Moustache - Tome 1 : Le chat qui voulait voler', 'neuf', 12.50, 1),
(1495, 1047, 1025, 'Le Petit Nicolas', 'neuf', 7.40, 1),
(1495, 1035, 1086, 'Sapiens : une brève histoire de l''humanité', 'tres bon', 14.90, 1),
(1497, 1031, 1003, 'Astérix le Gaulois', 'bon', 4.90, 1),
(1497, 1044, 1107, 'Comme un roman', 'bon', 3.40, 1),
(1498, 1028, 1006, 'Blacksad - Tome 1 : Quelque part entre les ombres', 'tres bon', 9.00, 1),
(1498, 1044, 1107, 'Comme un roman', 'bon', 3.40, 1),
(1499, 1023, 1011, 'Watchmen', 'bon', 15.80, 1),
(1499, 1006, 1113, 'Bescherelle - La conjugaison pour tous', 'neuf', 9.95, 1),
(1500, 1040, 1137, 'Principes de l''économie', 'neuf', 59.00, 1),
(1500, 1011, 1155, 'Le Charme discret de l''intestin', 'bon', 4.40, 1),
(1500, 1014, 1171, 'Born to run', 'bon', 4.50, 1),
(1500, 1043, 1049, 'La Vérité sur l''affaire Harry Quebert', 'neuf', 11.50, 1),
(1501, 1030, 1018, 'La Chenille qui fait des trous', 'tres bon', 6.00, 1),
(1501, 1026, 1053, 'Les Rivières pourpres', 'neuf', 9.20, 1),
(1501, 1020, 1134, 'Le Capital au XXIe siècle', 'tres bon', 8.30, 1),
(1502, 1019, 1031, 'Matilda', 'bon', 3.70, 2),
(1503, 1039, 1168, 'Les Quatre Accords toltèques', 'use', 2.70, 1),
(1503, 1038, 1013, 'One Piece - Édition originale - Tome 01', 'neuf', 7.20, 2),
(1504, 1039, 1165, 'Les Quatre Accords toltèques', 'neuf', 8.90, 1),
(1504, 1036, 1083, 'Le Petit Prince', 'bon', 3.10, 1),
(1504, 1037, 1042, 'Chanson douce', 'tres bon', 5.10, 1),
(1504, 1017, 1178, 'Pixel et Moustache - Tome 1 : Le chat qui voulait voler', 'neuf', 12.50, 1),
(1505, 1023, 1009, 'Watchmen', 'neuf', 35.00, 1),
(1506, 1008, 1121, 'J''intègre - Maths MPSI-MP2I : exercices incontournables', 'neuf', 29.90, 1),
(1506, 1040, 1137, 'Principes de l''économie', 'neuf', 59.00, 1),
(1506, 1030, 1019, 'La Chenille qui fait des trous', 'bon', 4.50, 1),
(1507, 1005, 1112, 'Le Robert Junior illustré', 'use', 7.80, 1),
(1507, 1047, 1028, 'Le Petit Nicolas', 'use', 2.20, 1),
(1508, 1006, 1115, 'Bescherelle - La conjugaison pour tous', 'bon', 4.50, 1),
(1508, 1008, 1122, 'J''intègre - Maths MPSI-MP2I : exercices incontournables', 'tres bon', 17.90, 1),
(1509, 1020, 1134, 'Le Capital au XXIe siècle', 'tres bon', 8.30, 1),
(1509, 1005, 1110, 'Le Robert Junior illustré', 'tres bon', 15.50, 1),
(1509, 1007, 1117, 'ABC du Bac - Mathématiques Terminale spécialité - Annales 2025', 'neuf', 9.95, 1),
(1509, 1042, 1073, 'Cyrano de Bergerac', 'neuf', 6.70, 1),
(1509, 1006, 1113, 'Bescherelle - La conjugaison pour tous', 'neuf', 9.95, 1),
(1510, 1033, 1059, 'Dune - Tome 1', 'bon', 5.20, 1),
(1510, 1006, 1113, 'Bescherelle - La conjugaison pour tous', 'neuf', 9.95, 1),
(1511, 1007, 1117, 'ABC du Bac - Mathématiques Terminale spécialité - Annales 2025', 'neuf', 9.95, 1),
(1511, 1045, 1125, 'Campbell Biologie (9e édition)', 'neuf', 99.00, 1),
(1511, 1006, 1114, 'Bescherelle - La conjugaison pour tous', 'tres bon', 6.00, 1),
(1512, 1007, 1119, 'ABC du Bac - Mathématiques Terminale spécialité - Annales 2025', 'bon', 4.50, 1),
(1512, 1024, 1100, 'L''Usage du monde', 'use', 3.60, 1),
(1512, 1008, 1123, 'J''intègre - Maths MPSI-MP2I : exercices incontournables', 'bon', 13.50, 1),
(1513, 1045, 1126, 'Campbell Biologie (9e édition)', 'tres bon', 59.40, 1),
(1513, 1005, 1109, 'Le Robert Junior illustré', 'neuf', 25.90, 1),
(1513, 1006, 1115, 'Bescherelle - La conjugaison pour tous', 'bon', 4.50, 1),
(1513, 1026, 1055, 'Les Rivières pourpres', 'bon', 4.10, 2),
(1513, 1007, 1118, 'ABC du Bac - Mathématiques Terminale spécialité - Annales 2025', 'tres bon', 6.00, 1),
(1514, 1010, 1152, 'Une brève histoire du temps', 'use', 2.70, 1),
(1514, 1031, 1001, 'Astérix le Gaulois', 'neuf', 10.99, 1),
(1515, 1027, 1141, 'Coder proprement', 'neuf', 35.00, 1),
(1515, 1008, 1123, 'J''intègre - Maths MPSI-MP2I : exercices incontournables', 'bon', 13.50, 1),
(1515, 1015, 1173, 'Le Potager du paresseux', 'neuf', 9.70, 2),
(1515, 1034, 1068, 'Avant toi', 'use', 2.50, 1),
(1516, 1007, 1119, 'ABC du Bac - Mathématiques Terminale spécialité - Annales 2025', 'bon', 4.50, 1),
(1516, 1045, 1128, 'Campbell Biologie (9e édition)', 'use', 29.70, 1),
(1516, 1005, 1111, 'Le Robert Junior illustré', 'bon', 11.70, 2),
(1517, 1008, 1121, 'J''intègre - Maths MPSI-MP2I : exercices incontournables', 'neuf', 29.90, 1),
(1517, 1005, 1110, 'Le Robert Junior illustré', 'tres bon', 15.50, 1),
(1518, 1008, 1121, 'J''intègre - Maths MPSI-MP2I : exercices incontournables', 'neuf', 29.90, 1),
(1518, 1045, 1126, 'Campbell Biologie (9e édition)', 'tres bon', 59.40, 1),
(1519, 1034, 1065, 'Avant toi', 'neuf', 8.20, 1),
(1519, 1014, 1170, 'Born to run', 'tres bon', 5.90, 1);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) VALUES
(1519, 1008, 1123, 'J''intègre - Maths MPSI-MP2I : exercices incontournables', 'bon', 13.50, 1),
(1520, 1045, 1127, 'Campbell Biologie (9e édition)', 'bon', 44.60, 1),
(1520, 1007, 1118, 'ABC du Bac - Mathématiques Terminale spécialité - Annales 2025', 'tres bon', 6.00, 1),
(1520, 1026, 1054, 'Les Rivières pourpres', 'tres bon', 5.50, 1),
(1520, 1006, 1115, 'Bescherelle - La conjugaison pour tous', 'bon', 4.50, 2),
(1520, 1005, 1109, 'Le Robert Junior illustré', 'neuf', 25.90, 1),
(1521, 1045, 1127, 'Campbell Biologie (9e édition)', 'bon', 44.60, 1),
(1521, 1016, 1177, 'Les Ombres de la Croix-Rousse', 'neuf', 18.00, 1),
(1522, 1041, 1094, 'Hitchcock / Truffaut - Édition définitive', 'tres bon', 19.20, 1),
(1522, 1008, 1122, 'J''intègre - Maths MPSI-MP2I : exercices incontournables', 'tres bon', 17.90, 2),
(1522, 1022, 1147, 'Introduction à l''algorithmique', 'bon', 31.10, 1),
(1523, 1013, 1161, 'Je sais cuisiner', 'neuf', 22.90, 1),
(1523, 1003, 1092, 'Histoire de l''art', 'use', 6.00, 1),
(1524, 1007, 1119, 'ABC du Bac - Mathématiques Terminale spécialité - Annales 2025', 'bon', 4.50, 1),
(1524, 1022, 1146, 'Introduction à l''algorithmique', 'tres bon', 41.40, 1),
(1525, 1045, 1127, 'Campbell Biologie (9e édition)', 'bon', 44.60, 1),
(1525, 1005, 1111, 'Le Robert Junior illustré', 'bon', 11.70, 1),
(1526, 1008, 1123, 'J''intègre - Maths MPSI-MP2I : exercices incontournables', 'bon', 13.50, 1),
(1526, 1040, 1138, 'Principes de l''économie', 'tres bon', 35.40, 1),
(1526, 1006, 1116, 'Bescherelle - La conjugaison pour tous', 'use', 3.00, 1),
(1527, 1006, 1115, 'Bescherelle - La conjugaison pour tous', 'bon', 4.50, 1),
(1527, 1017, 1178, 'Pixel et Moustache - Tome 1 : Le chat qui voulait voler', 'neuf', 12.50, 1),
(1527, 1005, 1110, 'Le Robert Junior illustré', 'tres bon', 15.50, 1),
(1528, 1007, 1119, 'ABC du Bac - Mathématiques Terminale spécialité - Annales 2025', 'bon', 4.50, 1),
(1528, 1028, 1005, 'Blacksad - Tome 1 : Quelque part entre les ombres', 'neuf', 14.99, 1),
(1528, 1008, 1121, 'J''intègre - Maths MPSI-MP2I : exercices incontournables', 'neuf', 29.90, 1),
(1528, 1013, 1162, 'Je sais cuisiner', 'tres bon', 13.70, 1),
(1529, 1043, 1050, 'La Vérité sur l''affaire Harry Quebert', 'tres bon', 6.90, 1),
(1529, 1006, 1114, 'Bescherelle - La conjugaison pour tous', 'tres bon', 6.00, 2),
(1529, 1042, 1075, 'Cyrano de Bergerac', 'bon', 3.00, 1),
(1529, 1016, 1177, 'Les Ombres de la Croix-Rousse', 'neuf', 18.00, 1),
(1530, 1026, 1054, 'Les Rivières pourpres', 'tres bon', 5.50, 1),
(1530, 1034, 1066, 'Avant toi', 'tres bon', 4.90, 1),
(1530, 1002, 1070, 'Les Fleurs du mal', 'tres bon', 2.90, 1),
(1531, 1046, 1078, 'Madame Bovary', 'tres bon', 3.50, 1),
(1531, 1026, 1055, 'Les Rivières pourpres', 'bon', 4.10, 1),
(1531, 1012, 1160, 'Bescherelle - L''anglais pour tous', 'use', 4.80, 2),
(1531, 1021, 1048, 'L''Anomalie', 'use', 2.80, 1),
(1532, 1029, 1036, 'Hunger Games - Tome 1', 'use', 2.70, 1),
(1532, 1042, 1076, 'Cyrano de Bergerac', 'use', 2.00, 1),
(1532, 1045, 1127, 'Campbell Biologie (9e édition)', 'bon', 44.60, 1),
(1532, 1007, 1117, 'ABC du Bac - Mathématiques Terminale spécialité - Annales 2025', 'neuf', 9.95, 1),
(1532, 1037, 1041, 'Chanson douce', 'neuf', 8.50, 2),
(1533, 1012, 1158, 'Bescherelle - L''anglais pour tous', 'tres bon', 9.50, 1),
(1533, 1046, 1078, 'Madame Bovary', 'tres bon', 3.50, 2),
(1533, 1043, 1052, 'La Vérité sur l''affaire Harry Quebert', 'use', 3.50, 1),
(1533, 1032, 1023, 'Max et les Maximonstres', 'bon', 5.90, 1),
(1534, 1039, 1166, 'Les Quatre Accords toltèques', 'tres bon', 5.30, 1),
(1534, 1002, 1069, 'Les Fleurs du mal', 'neuf', 4.90, 1),
(1535, 1010, 1149, 'Une brève histoire du temps', 'neuf', 9.00, 1),
(1535, 1037, 1042, 'Chanson douce', 'tres bon', 5.10, 1),
(1536, 1026, 1055, 'Les Rivières pourpres', 'bon', 4.10, 1),
(1536, 1004, 1104, 'Une chambre à soi', 'use', 2.10, 1),
(1537, 1039, 1168, 'Les Quatre Accords toltèques', 'use', 2.70, 1),
(1537, 1034, 1066, 'Avant toi', 'tres bon', 4.90, 1),
(1538, 1036, 1081, 'Le Petit Prince', 'neuf', 6.90, 1),
(1538, 1011, 1153, 'Le Charme discret de l''intestin', 'neuf', 9.70, 1),
(1539, 1027, 1142, 'Coder proprement', 'tres bon', 21.00, 1),
(1539, 1024, 1098, 'L''Usage du monde', 'tres bon', 7.20, 1),
(1539, 1033, 1059, 'Dune - Tome 1', 'bon', 5.20, 1),
(1539, 1036, 1081, 'Le Petit Prince', 'neuf', 6.90, 2),
(1540, 1021, 1045, 'L''Anomalie', 'neuf', 9.20, 2),
(1540, 1033, 1060, 'Dune - Tome 1', 'use', 3.50, 1),
(1540, 1042, 1075, 'Cyrano de Bergerac', 'bon', 3.00, 1),
(1540, 1005, 1109, 'Le Robert Junior illustré', 'neuf', 25.90, 1),
(1540, 1012, 1157, 'Bescherelle - L''anglais pour tous', 'neuf', 15.90, 1),
(1541, 1034, 1068, 'Avant toi', 'use', 2.50, 1),
(1541, 1036, 1083, 'Le Petit Prince', 'bon', 3.10, 1),
(1542, 1032, 1023, 'Max et les Maximonstres', 'bon', 5.90, 1),
(1542, 1046, 1078, 'Madame Bovary', 'tres bon', 3.50, 1),
(1542, 1014, 1171, 'Born to run', 'bon', 4.50, 1),
(1543, 1012, 1159, 'Bescherelle - L''anglais pour tous', 'bon', 7.20, 1),
(1543, 1025, 1062, '1984', 'tres bon', 5.60, 1),
(1543, 1006, 1114, 'Bescherelle - La conjugaison pour tous', 'tres bon', 6.00, 1),
(1544, 1037, 1041, 'Chanson douce', 'neuf', 8.50, 1),
(1544, 1036, 1081, 'Le Petit Prince', 'neuf', 6.90, 2),
(1545, 1034, 1065, 'Avant toi', 'neuf', 8.20, 2),
(1546, 1034, 1068, 'Avant toi', 'use', 2.50, 1),
(1546, 1042, 1075, 'Cyrano de Bergerac', 'bon', 3.00, 1),
(1547, 1027, 1141, 'Coder proprement', 'neuf', 35.00, 2),
(1548, 1001, 1040, 'La Passe-miroir - Livre 1 : Les Fiancés de l''hiver', 'use', 3.30, 1),
(1548, 1013, 1162, 'Je sais cuisiner', 'tres bon', 13.70, 1),
(1548, 1026, 1053, 'Les Rivières pourpres', 'neuf', 9.20, 1),
(1548, 1011, 1156, 'Le Charme discret de l''intestin', 'use', 2.90, 1),
(1549, 1029, 1034, 'Hunger Games - Tome 1', 'tres bon', 5.40, 1),
(1549, 1047, 1026, 'Le Petit Nicolas', 'tres bon', 4.40, 1),
(1550, 1017, 1178, 'Pixel et Moustache - Tome 1 : Le chat qui voulait voler', 'neuf', 12.50, 1),
(1550, 1002, 1069, 'Les Fleurs du mal', 'neuf', 4.90, 1),
(1551, 1046, 1079, 'Madame Bovary', 'bon', 2.70, 1),
(1551, 1002, 1069, 'Les Fleurs du mal', 'neuf', 4.90, 1),
(1552, 1039, 1165, 'Les Quatre Accords toltèques', 'neuf', 8.90, 1),
(1552, 1003, 1092, 'Histoire de l''art', 'use', 6.00, 1),
(1552, 1043, 1051, 'La Vérité sur l''affaire Harry Quebert', 'bon', 5.20, 1),
(1553, 1019, 1030, 'Matilda', 'tres bon', 4.90, 1),
(1553, 1042, 1074, 'Cyrano de Bergerac', 'tres bon', 4.00, 1),
(1553, 1029, 1033, 'Hunger Games - Tome 1', 'neuf', 8.95, 1),
(1554, 1002, 1072, 'Les Fleurs du mal', 'use', 1.50, 1),
(1555, 1006, 1113, 'Bescherelle - La conjugaison pour tous', 'neuf', 9.95, 2),
(1555, 1026, 1053, 'Les Rivières pourpres', 'neuf', 9.20, 1),
(1555, 1001, 1038, 'La Passe-miroir - Livre 1 : Les Fiancés de l''hiver', 'tres bon', 6.50, 1),
(1555, 1034, 1068, 'Avant toi', 'use', 2.50, 1),
(1556, 1033, 1058, 'Dune - Tome 1', 'tres bon', 6.90, 1),
(1556, 1017, 1178, 'Pixel et Moustache - Tome 1 : Le chat qui voulait voler', 'neuf', 12.50, 1),
(1556, 1021, 1047, 'L''Anomalie', 'bon', 4.10, 1),
(1556, 1025, 1061, '1984', 'neuf', 9.30, 2),
(1556, 1014, 1170, 'Born to run', 'tres bon', 5.90, 1),
(1557, 1046, 1078, 'Madame Bovary', 'tres bon', 3.50, 1),
(1558, 1038, 1013, 'One Piece - Édition originale - Tome 01', 'neuf', 7.20, 1),
(1558, 1025, 1063, '1984', 'bon', 4.20, 1),
(1559, 1025, 1063, '1984', 'bon', 4.20, 1),
(1559, 1038, 1015, 'One Piece - Édition originale - Tome 01', 'bon', 3.20, 1),
(1559, 1002, 1072, 'Les Fleurs du mal', 'use', 1.50, 1),
(1559, 1016, 1177, 'Les Ombres de la Croix-Rousse', 'neuf', 18.00, 1),
(1560, 1010, 1150, 'Une brève histoire du temps', 'tres bon', 5.40, 1),
(1560, 1021, 1045, 'L''Anomalie', 'neuf', 9.20, 1),
(1561, 1033, 1058, 'Dune - Tome 1', 'tres bon', 6.90, 1),
(1561, 1025, 1062, '1984', 'tres bon', 5.60, 1),
(1562, 1025, 1063, '1984', 'bon', 4.20, 1),
(1562, 1019, 1029, 'Matilda', 'neuf', 8.20, 1),
(1563, 1025, 1062, '1984', 'tres bon', 5.60, 1),
(1564, 1016, 1177, 'Les Ombres de la Croix-Rousse', 'neuf', 18.00, 1),
(1564, 1025, 1062, '1984', 'tres bon', 5.60, 1),
(1565, 1046, 1080, 'Madame Bovary', 'use', 1.80, 1),
(1565, 1021, 1046, 'L''Anomalie', 'tres bon', 5.50, 1),
(1565, 1025, 1062, '1984', 'tres bon', 5.60, 1),
(1566, 1003, 1091, 'Histoire de l''art', 'bon', 9.00, 1),
(1566, 1025, 1063, '1984', 'bon', 4.20, 1),
(1566, 1043, 1051, 'La Vérité sur l''affaire Harry Quebert', 'bon', 5.20, 1),
(1566, 1041, 1096, 'Hitchcock / Truffaut - Édition définitive', 'use', 9.60, 1),
(1566, 1005, 1111, 'Le Robert Junior illustré', 'bon', 11.70, 1),
(1567, 1039, 1166, 'Les Quatre Accords toltèques', 'tres bon', 5.30, 1),
(1567, 1036, 1081, 'Le Petit Prince', 'neuf', 6.90, 1),
(1567, 1008, 1121, 'J''intègre - Maths MPSI-MP2I : exercices incontournables', 'neuf', 29.90, 2),
(1567, 1007, 1118, 'ABC du Bac - Mathématiques Terminale spécialité - Annales 2025', 'tres bon', 6.00, 1),
(1568, 1036, 1081, 'Le Petit Prince', 'neuf', 6.90, 1),
(1568, 1024, 1098, 'L''Usage du monde', 'tres bon', 7.20, 1),
(1568, 1025, 1061, '1984', 'neuf', 9.30, 1),
(1568, 1037, 1044, 'Chanson douce', 'use', 2.60, 1),
(1569, 1039, 1167, 'Les Quatre Accords toltèques', 'bon', 4.00, 1),
(1569, 1046, 1077, 'Madame Bovary', 'neuf', 5.90, 1),
(1569, 1033, 1059, 'Dune - Tome 1', 'bon', 5.20, 1),
(1570, 1037, 1042, 'Chanson douce', 'tres bon', 5.10, 1),
(1570, 1023, 1011, 'Watchmen', 'bon', 15.80, 2),
(1570, 1002, 1071, 'Les Fleurs du mal', 'bon', 2.20, 1),
(1571, 1026, 1053, 'Les Rivières pourpres', 'neuf', 9.20, 1),
(1572, 1042, 1074, 'Cyrano de Bergerac', 'tres bon', 4.00, 1),
(1572, 1015, 1175, 'Le Potager du paresseux', 'bon', 4.40, 1),
(1572, 1011, 1155, 'Le Charme discret de l''intestin', 'bon', 4.40, 1),
(1573, 1033, 1058, 'Dune - Tome 1', 'tres bon', 6.90, 1),
(1573, 1001, 1039, 'La Passe-miroir - Livre 1 : Les Fiancés de l''hiver', 'bon', 4.90, 1);

INSERT INTO commande_ligne (id_commande, id_livre, id_exemplaire, titre, etat, prix_unitaire, quantite) VALUES
(1573, 1026, 1054, 'Les Rivières pourpres', 'tres bon', 5.50, 1),
(1574, 1021, 1047, 'L''Anomalie', 'bon', 4.10, 2),
(1574, 1037, 1042, 'Chanson douce', 'tres bon', 5.10, 1),
(1574, 1042, 1074, 'Cyrano de Bergerac', 'tres bon', 4.00, 2),
(1575, 1002, 1072, 'Les Fleurs du mal', 'use', 1.50, 1),
(1575, 1036, 1083, 'Le Petit Prince', 'bon', 3.10, 1),
(1575, 1039, 1166, 'Les Quatre Accords toltèques', 'tres bon', 5.30, 1),
(1575, 1017, 1178, 'Pixel et Moustache - Tome 1 : Le chat qui voulait voler', 'neuf', 12.50, 1),
(1575, 1012, 1158, 'Bescherelle - L''anglais pour tous', 'tres bon', 9.50, 1),
(1576, 1026, 1055, 'Les Rivières pourpres', 'bon', 4.10, 1),
(1576, 1010, 1152, 'Une brève histoire du temps', 'use', 2.70, 1),
(1576, 1024, 1098, 'L''Usage du monde', 'tres bon', 7.20, 1),
(1577, 1034, 1067, 'Avant toi', 'bon', 3.70, 2),
(1577, 1021, 1045, 'L''Anomalie', 'neuf', 9.20, 1),
(1578, 1002, 1071, 'Les Fleurs du mal', 'bon', 2.20, 1),
(1578, 1009, 1131, 'Lexique des termes juridiques 2025-2026', 'bon', 7.60, 1),
(1578, 1042, 1074, 'Cyrano de Bergerac', 'tres bon', 4.00, 1),
(1579, 1043, 1051, 'La Vérité sur l''affaire Harry Quebert', 'bon', 5.20, 1),
(1580, 1007, 1120, 'ABC du Bac - Mathématiques Terminale spécialité - Annales 2025', 'use', 3.00, 1),
(1580, 1037, 1043, 'Chanson douce', 'bon', 3.80, 1),
(1581, 1021, 1045, 'L''Anomalie', 'neuf', 9.20, 1),
(1581, 1012, 1160, 'Bescherelle - L''anglais pour tous', 'use', 4.80, 1),
(1581, 1043, 1050, 'La Vérité sur l''affaire Harry Quebert', 'tres bon', 6.90, 1),
(1581, 1009, 1129, 'Lexique des termes juridiques 2025-2026', 'neuf', 16.90, 1),
(1581, 1036, 1083, 'Le Petit Prince', 'bon', 3.10, 1),
(1582, 1034, 1065, 'Avant toi', 'neuf', 8.20, 1),
(1582, 1016, 1177, 'Les Ombres de la Croix-Rousse', 'neuf', 18.00, 1),
(1582, 1043, 1049, 'La Vérité sur l''affaire Harry Quebert', 'neuf', 11.50, 1),
(1582, 1046, 1078, 'Madame Bovary', 'tres bon', 3.50, 2),
(1582, 1002, 1071, 'Les Fleurs du mal', 'bon', 2.20, 1),
(1583, 1036, 1083, 'Le Petit Prince', 'bon', 3.10, 1),
(1583, 1017, 1178, 'Pixel et Moustache - Tome 1 : Le chat qui voulait voler', 'neuf', 12.50, 2),
(1584, 1023, 1010, 'Watchmen', 'tres bon', 21.00, 1),
(1584, 1002, 1072, 'Les Fleurs du mal', 'use', 1.50, 1),
(1586, 1013, 1163, 'Je sais cuisiner', 'bon', 10.30, 1),
(1586, 1021, 1047, 'L''Anomalie', 'bon', 4.10, 1),
(1586, 1033, 1058, 'Dune - Tome 1', 'tres bon', 6.90, 1),
(1587, 1007, 1120, 'ABC du Bac - Mathématiques Terminale spécialité - Annales 2025', 'use', 3.00, 1),
(1587, 1036, 1081, 'Le Petit Prince', 'neuf', 6.90, 1),
(1587, 1026, 1056, 'Les Rivières pourpres', 'use', 2.80, 1),
(1588, 1022, 1146, 'Introduction à l''algorithmique', 'tres bon', 41.40, 1),
(1588, 1043, 1051, 'La Vérité sur l''affaire Harry Quebert', 'bon', 5.20, 1),
(1589, 1039, 1167, 'Les Quatre Accords toltèques', 'bon', 4.00, 1),
(1590, 1026, 1054, 'Les Rivières pourpres', 'tres bon', 5.50, 1),
(1591, 1016, 1177, 'Les Ombres de la Croix-Rousse', 'neuf', 18.00, 1),
(1591, 1006, 1115, 'Bescherelle - La conjugaison pour tous', 'bon', 4.50, 1),
(1592, 1046, 1078, 'Madame Bovary', 'tres bon', 3.50, 1),
(1592, 1043, 1049, 'La Vérité sur l''affaire Harry Quebert', 'neuf', 11.50, 1),
(1593, 1026, 1053, 'Les Rivières pourpres', 'neuf', 9.20, 2),
(1593, 1037, 1043, 'Chanson douce', 'bon', 3.80, 1),
(1593, 1025, 1062, '1984', 'tres bon', 5.60, 1),
(1594, 1042, 1074, 'Cyrano de Bergerac', 'tres bon', 4.00, 1),
(1594, 1002, 1072, 'Les Fleurs du mal', 'use', 1.50, 1),
(1594, 1011, 1154, 'Le Charme discret de l''intestin', 'tres bon', 5.80, 1),
(1595, 1015, 1174, 'Le Potager du paresseux', 'tres bon', 5.80, 1),
(1595, 1007, 1117, 'ABC du Bac - Mathématiques Terminale spécialité - Annales 2025', 'neuf', 9.95, 1),
(1595, 1016, 1177, 'Les Ombres de la Croix-Rousse', 'neuf', 18.00, 2),
(1596, 1013, 1163, 'Je sais cuisiner', 'bon', 10.30, 1),
(1597, 1039, 1167, 'Les Quatre Accords toltèques', 'bon', 4.00, 1),
(1597, 1006, 1115, 'Bescherelle - La conjugaison pour tous', 'bon', 4.50, 1),
(1597, 1033, 1059, 'Dune - Tome 1', 'bon', 5.20, 1),
(1598, 1042, 1076, 'Cyrano de Bergerac', 'use', 2.00, 1),
(1598, 1016, 1177, 'Les Ombres de la Croix-Rousse', 'neuf', 18.00, 1),
(1598, 1041, 1095, 'Hitchcock / Truffaut - Édition définitive', 'bon', 14.40, 1),
(1598, 1009, 1131, 'Lexique des termes juridiques 2025-2026', 'bon', 7.60, 1);

INSERT INTO avis (id_avis, id_livre, id_user, note, titre, commentaire, date_creation, statut, date_validation, id_validateur) SELECT 1004, 9, 1014, 5, 'Magnifique', 'Une relecture des années après, et toujours le même plaisir. Les petits défauts d''usure ne gênent pas du tout la lecture.', '2026-09-11 22:52:45' + INTERVAL @decalage DAY, 'visible', '2026-09-13 00:52:45' + INTERVAL @decalage DAY, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 9);

INSERT INTO avis (id_avis, id_livre, id_user, note, titre, commentaire, date_creation, statut, date_validation, id_validateur) SELECT 1005, 10, 1015, 5, 'Un incontournable', 'Une relecture des années après, et toujours le même plaisir. Les petits défauts d''usure ne gênent pas du tout la lecture.', '2026-02-19 13:27:17' + INTERVAL @decalage DAY, 'visible', '2026-02-21 18:27:17' + INTERVAL @decalage DAY, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 10);

INSERT INTO avis (id_avis, id_livre, id_user, note, titre, commentaire, date_creation, statut, date_validation, id_validateur) SELECT 1009, 3, 1020, 5, 'Coup de cœur', 'Une relecture des années après, et toujours le même plaisir. Les petits défauts d''usure ne gênent pas du tout la lecture.', '2026-10-23 17:59:15' + INTERVAL @decalage DAY, 'visible', NULL, NULL FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 3);

INSERT INTO avis (id_avis, id_livre, id_user, note, titre, commentaire, date_creation, statut, date_validation, id_validateur) SELECT 1011, 9, 1023, 4, 'Bien construit', 'Sérieux et bien documenté, parfois un peu dense mais toujours intéressant.', '2026-05-16 22:26:08' + INTERVAL @decalage DAY, 'visible', '2026-05-19 03:26:08' + INTERVAL @decalage DAY, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 9);

INSERT INTO avis (id_avis, id_livre, id_user, note, titre, commentaire, date_creation, statut, date_validation, id_validateur) SELECT 1013, 9, 1025, 5, 'Coup de cœur', 'Exemplaire reçu en parfait état, conforme à la description. Quant au livre lui-même, c''est une merveille que je relirai sûrement.', '2026-10-08 20:41:13' + INTERVAL @decalage DAY, 'visible', '2026-10-10 22:41:13' + INTERVAL @decalage DAY, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 9);

INSERT INTO avis (id_avis, id_livre, id_user, note, titre, commentaire, date_creation, statut, date_validation, id_validateur) SELECT 1018, 11, 1027, 4, 'Captivant', 'Agréable à lire, idéal pour les trajets en transport. Je lirai la suite avec plaisir.', '2026-07-15 18:15:36' + INTERVAL @decalage DAY, 'visible', '2026-07-16 22:15:36' + INTERVAL @decalage DAY, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 11);

INSERT INTO avis (id_avis, id_livre, id_user, note, titre, commentaire, date_creation, statut, date_validation, id_validateur) SELECT 1026, 3, 1031, 5, 'À offrir sans hésiter', 'Un livre qui reste en tête longtemps après l''avoir refermé. Je l''ai déjà prêté à deux amis qui ont adoré aussi.', '2026-07-31 22:29:50' + INTERVAL @decalage DAY, 'visible', '2026-08-02 03:29:50' + INTERVAL @decalage DAY, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 3);

INSERT INTO avis (id_avis, id_livre, id_user, note, titre, commentaire, date_creation, statut, date_validation, id_validateur) SELECT 1030, 4, 1034, 4, 'Captivant', 'Sérieux et bien documenté, parfois un peu dense mais toujours intéressant.', '2026-08-04 13:23:57' + INTERVAL @decalage DAY, 'visible', '2026-08-05 17:23:57' + INTERVAL @decalage DAY, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 4);

INSERT INTO avis (id_avis, id_livre, id_user, note, titre, commentaire, date_creation, statut, date_validation, id_validateur) SELECT 1036, 1, 1037, 5, 'Impossible à lâcher', 'Un livre qui reste en tête longtemps après l''avoir refermé. Je l''ai déjà prêté à deux amis qui ont adoré aussi.', '2025-07-31 14:01:16' + INTERVAL @decalage DAY, 'visible', '2025-08-02 19:01:16' + INTERVAL @decalage DAY, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 1);

INSERT INTO avis (id_avis, id_livre, id_user, note, titre, commentaire, date_creation, statut, date_validation, id_validateur) SELECT 1038, 4, 1038, 5, 'Impossible à lâcher', 'Une relecture des années après, et toujours le même plaisir. Les petits défauts d''usure ne gênent pas du tout la lecture.', '2026-05-21 02:39:45' + INTERVAL @decalage DAY, 'visible', '2026-05-23 03:39:45' + INTERVAL @decalage DAY, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 4);

INSERT INTO avis (id_avis, id_livre, id_user, note, titre, commentaire, date_creation, statut, date_validation, id_validateur) SELECT 1045, 11, 1040, 4, 'Très bonne lecture', 'Bon rapport qualité-prix pour un livre en bon état. Une lecture enrichissante que je recommande.', '2025-06-26 01:01:18' + INTERVAL @decalage DAY, 'visible', '2025-06-28 03:01:18' + INTERVAL @decalage DAY, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 11);

INSERT INTO avis (id_avis, id_livre, id_user, note, titre, commentaire, date_creation, statut, date_validation, id_validateur) SELECT 1047, 10, 1041, 4, 'Belle découverte', 'Une belle histoire, bien racontée. J''aurais aimé que certains personnages secondaires soient plus développés.', '2025-09-28 19:02:17' + INTERVAL @decalage DAY, 'visible', '2025-09-30 20:02:17' + INTERVAL @decalage DAY, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 10);

INSERT INTO avis (id_avis, id_livre, id_user, note, titre, commentaire, date_creation, statut, date_validation, id_validateur) SELECT 1063, 7, 1046, 4, 'Captivant', 'Sérieux et bien documenté, parfois un peu dense mais toujours intéressant.', '2025-12-01 15:07:13' + INTERVAL @decalage DAY, 'visible', '2025-12-02 17:07:13' + INTERVAL @decalage DAY, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 7);

INSERT INTO avis (id_avis, id_livre, id_user, note, titre, commentaire, date_creation, statut, date_validation, id_validateur) SELECT 1068, 3, 1048, 3, 'Correct sans plus', 'Quelques bonnes idées, mais le style ne m''a pas totalement convaincu. À tenter si le sujet vous intéresse.', '2025-03-29 19:18:57' + INTERVAL @decalage DAY, 'visible', '2025-03-30 22:18:57' + INTERVAL @decalage DAY, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 3);

INSERT INTO avis (id_avis, id_livre, id_user, note, titre, commentaire, date_creation, statut, date_validation, id_validateur) SELECT 1069, 2, 1048, 4, 'Je ne regrette pas', 'Livre arrivé rapidement et bien emballé. Le contenu est à la hauteur de sa réputation, même si je m''attendais à plus de surprises.', '2025-04-11 22:18:57' + INTERVAL @decalage DAY, 'visible', '2025-04-12 23:18:57' + INTERVAL @decalage DAY, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 2);

INSERT INTO avis (id_avis, id_livre, id_user, note, titre, commentaire, date_creation, statut, date_validation, id_validateur) SELECT 1073, 5, 1049, 3, 'Un peu long', 'Une lecture en demi-teinte : de beaux passages, mais l''ensemble manque un peu de rythme.', '2025-12-17 12:25:28' + INTERVAL @decalage DAY, 'visible', '2025-12-19 13:25:28' + INTERVAL @decalage DAY, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 5);

INSERT INTO avis (id_avis, id_livre, id_user, note, titre, commentaire, date_creation, statut, date_validation, id_validateur) SELECT 1076, 4, 1049, 4, 'Je ne regrette pas', 'Livre arrivé rapidement et bien emballé. Le contenu est à la hauteur de sa réputation, même si je m''attendais à plus de surprises.', '2025-09-14 23:15:59' + INTERVAL @decalage DAY, 'visible', '2025-09-17 04:15:59' + INTERVAL @decalage DAY, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 4);

INSERT INTO avis (id_avis, id_livre, id_user, note, titre, commentaire, date_creation, statut, date_validation, id_validateur) SELECT 1078, 3, 1050, 3, 'Mitigé', 'Le début m''a beaucoup plu, mais j''ai trouvé la suite répétitive. Ça reste une lecture correcte.', '2025-12-08 12:37:27' + INTERVAL @decalage DAY, 'visible', '2025-12-09 15:37:27' + INTERVAL @decalage DAY, 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 3);

INSERT INTO avis (id_avis, id_livre, id_user, note, titre, commentaire, date_creation, statut, date_validation, id_validateur) VALUES
(1001, 1041, 1010, 4, 'Belle découverte', 'Une belle histoire, bien racontée. J''aurais aimé que certains personnages secondaires soient plus développés.', '2026-08-01 04:06:35' + INTERVAL @decalage DAY, 'visible', '2026-08-03 07:06:35' + INTERVAL @decalage DAY, 1),
(1002, 1044, 1011, 5, 'Un vrai bijou', 'Acheté d''occasion en très bon état pour une bouchée de pain, et quelle découverte ! Je comprends pourquoi tout le monde en parle.', '2026-07-06 14:47:43' + INTERVAL @decalage DAY, 'visible', '2026-07-08 17:47:43' + INTERVAL @decalage DAY, 1),
(1003, 1015, 1013, 4, 'Une lecture agréable', 'Agréable à lire, idéal pour les trajets en transport. Je lirai la suite avec plaisir.', '2026-06-26 17:08:45' + INTERVAL @decalage DAY, 'visible', '2026-06-28 21:08:45' + INTERVAL @decalage DAY, 1),
(1006, 1002, 1016, 5, 'Magnifique', 'Mon fils l''a lu puis je l''ai lu à mon tour : on en parle encore à table. Un vrai moment de partage.', '2026-10-20 08:09:12' + INTERVAL @decalage DAY, 'visible', NULL, NULL),
(1007, 1047, 1017, 4, 'Captivant', 'Bon rapport qualité-prix pour un livre en bon état. Une lecture enrichissante que je recommande.', '2026-09-12 10:27:39' + INTERVAL @decalage DAY, 'visible', '2026-09-13 15:27:39' + INTERVAL @decalage DAY, 1),
(1008, 1034, 1019, 4, 'Bien construit', 'Bon rapport qualité-prix pour un livre en bon état. Une lecture enrichissante que je recommande.', '2026-07-13 00:59:35' + INTERVAL @decalage DAY, 'visible', '2026-07-14 03:59:35' + INTERVAL @decalage DAY, 1),
(1010, 1005, 1021, 4, 'Bien construit', 'Agréable à lire, idéal pour les trajets en transport. Je lirai la suite avec plaisir.', '2026-04-10 03:24:55' + INTERVAL @decalage DAY, 'visible', '2026-04-12 04:24:55' + INTERVAL @decalage DAY, 1),
(1012, 1038, 1025, 4, 'Bien construit', 'Agréable à lire, idéal pour les trajets en transport. Je lirai la suite avec plaisir.', '2025-12-14 21:30:06' + INTERVAL @decalage DAY, 'visible', '2025-12-16 22:30:06' + INTERVAL @decalage DAY, 1),
(1014, 1042, 1025, 4, 'Captivant', 'Bon rapport qualité-prix pour un livre en bon état. Une lecture enrichissante que je recommande.', '2026-03-27 14:43:01' + INTERVAL @decalage DAY, 'visible', '2026-03-29 18:43:01' + INTERVAL @decalage DAY, 1),
(1015, 1005, 1026, 3, 'Mitigé', 'Quelques bonnes idées, mais le style ne m''a pas totalement convaincu. À tenter si le sujet vous intéresse.', '2025-06-09 03:33:16' + INTERVAL @decalage DAY, 'masque', '2025-06-10 06:33:16' + INTERVAL @decalage DAY, 1),
(1016, 1008, 1026, 4, 'Je ne regrette pas', 'Livre arrivé rapidement et bien emballé. Le contenu est à la hauteur de sa réputation, même si je m''attendais à plus de surprises.', '2025-10-02 17:50:56' + INTERVAL @decalage DAY, 'visible', '2025-10-04 20:50:56' + INTERVAL @decalage DAY, 1),
(1017, 1003, 1026, 5, 'Impossible à lâcher', 'Acheté d''occasion en très bon état pour une bouchée de pain, et quelle découverte ! Je comprends pourquoi tout le monde en parle.', '2025-08-08 00:31:10' + INTERVAL @decalage DAY, 'visible', '2025-08-09 03:31:10' + INTERVAL @decalage DAY, 1),
(1019, 1041, 1027, 5, 'Magnifique', 'Tout est réussi : le rythme, l''ambiance, les dialogues. Une lecture que je conseille autant aux habitués qu''aux curieux.', '2026-05-09 06:32:55' + INTERVAL @decalage DAY, 'visible', '2026-05-10 10:32:55' + INTERVAL @decalage DAY, 1),
(1020, 1021, 1028, 4, 'Je ne regrette pas', 'Très bonne lecture dans l''ensemble, avec quelques passages un peu plus lents au milieu. La dernière partie rattrape largement.', '2026-08-24 17:52:21' + INTERVAL @decalage DAY, 'visible', '2026-08-25 21:52:21' + INTERVAL @decalage DAY, 1),
(1021, 1042, 1029, 5, 'Un incontournable', 'Clair, passionnant et jamais ennuyeux. On apprend énormément sans s''en rendre compte.', '2026-02-10 01:49:39' + INTERVAL @decalage DAY, 'visible', '2026-02-11 03:49:39' + INTERVAL @decalage DAY, 1),
(1022, 1029, 1029, 4, 'Je ne regrette pas', 'Agréable à lire, idéal pour les trajets en transport. Je lirai la suite avec plaisir.', '2026-09-27 15:50:34' + INTERVAL @decalage DAY, 'visible', '2026-09-29 19:50:34' + INTERVAL @decalage DAY, 1),
(1023, 1001, 1030, 4, 'Très bonne lecture', 'Sérieux et bien documenté, parfois un peu dense mais toujours intéressant.', '2026-09-18 21:30:10' + INTERVAL @decalage DAY, 'visible', '2026-09-19 22:30:10' + INTERVAL @decalage DAY, 1),
(1024, 1023, 1030, 5, 'Impossible à lâcher', 'Je l''ai dévoré en deux soirées. L''écriture est fluide, on s''attache tout de suite aux personnages et la fin m''a laissé sans voix.', '2026-09-20 18:39:05' + INTERVAL @decalage DAY, 'visible', '2026-09-22 20:39:05' + INTERVAL @decalage DAY, 1),
(1025, 1027, 1030, 4, 'Une lecture agréable', 'Une belle histoire, bien racontée. J''aurais aimé que certains personnages secondaires soient plus développés.', '2026-08-31 10:55:22' + INTERVAL @decalage DAY, 'visible', '2026-09-01 12:55:22' + INTERVAL @decalage DAY, 1),
(1027, 1041, 1032, 3, 'Un peu long', 'Pratique pour réviser, même si certaines explications mériteraient d''être plus claires.', '2026-06-07 19:12:44' + INTERVAL @decalage DAY, 'masque', '2026-06-08 23:12:44' + INTERVAL @decalage DAY, 1),
(1028, 1032, 1032, 5, 'À offrir sans hésiter', 'Tout est réussi : le rythme, l''ambiance, les dialogues. Une lecture que je conseille autant aux habitués qu''aux curieux.', '2026-10-06 13:00:34' + INTERVAL @decalage DAY, 'visible', '2026-10-07 16:00:34' + INTERVAL @decalage DAY, 1),
(1029, 1028, 1033, 5, 'À offrir sans hésiter', 'Clair, passionnant et jamais ennuyeux. On apprend énormément sans s''en rendre compte.', '2026-10-04 22:16:35' + INTERVAL @decalage DAY, 'visible', '2026-10-05 23:16:35' + INTERVAL @decalage DAY, 1),
(1031, 1032, 1035, 5, 'Je recommande les yeux fermés', 'Tout est réussi : le rythme, l''ambiance, les dialogues. Une lecture que je conseille autant aux habitués qu''aux curieux.', '2026-06-23 14:11:50' + INTERVAL @decalage DAY, 'visible', '2026-06-24 16:11:50' + INTERVAL @decalage DAY, 1),
(1032, 1033, 1035, 5, 'Coup de cœur', 'Clair, passionnant et jamais ennuyeux. On apprend énormément sans s''en rendre compte.', '2026-05-19 18:17:00' + INTERVAL @decalage DAY, 'visible', '2026-05-20 20:17:00' + INTERVAL @decalage DAY, 1),
(1033, 1032, 1036, 4, 'Je ne regrette pas', 'Agréable à lire, idéal pour les trajets en transport. Je lirai la suite avec plaisir.', '2026-06-25 17:40:10' + INTERVAL @decalage DAY, 'visible', '2026-06-27 20:40:10' + INTERVAL @decalage DAY, 1),
(1034, 1017, 1036, 5, 'Magnifique', 'Acheté d''occasion en très bon état pour une bouchée de pain, et quelle découverte ! Je comprends pourquoi tout le monde en parle.', '2026-10-17 01:07:26' + INTERVAL @decalage DAY, 'visible', '2026-10-18 03:07:26' + INTERVAL @decalage DAY, 1),
(1035, 1047, 1037, 3, 'Correct sans plus', 'Quelques bonnes idées, mais le style ne m''a pas totalement convaincu. À tenter si le sujet vous intéresse.', '2026-01-08 17:58:36' + INTERVAL @decalage DAY, 'visible', '2026-01-10 19:58:36' + INTERVAL @decalage DAY, 1),
(1037, 1011, 1038, 3, 'Correct sans plus', 'Quelques bonnes idées, mais le style ne m''a pas totalement convaincu. À tenter si le sujet vous intéresse.', '2026-07-10 13:00:35' + INTERVAL @decalage DAY, 'visible', '2026-07-11 16:00:35' + INTERVAL @decalage DAY, 1),
(1039, 1010, 1038, 5, 'Un vrai bijou', 'Acheté d''occasion en très bon état pour une bouchée de pain, et quelle découverte ! Je comprends pourquoi tout le monde en parle.', '2026-05-05 17:04:18' + INTERVAL @decalage DAY, 'visible', '2026-05-06 18:04:18' + INTERVAL @decalage DAY, 1),
(1040, 1036, 1039, 5, 'Un vrai bijou', 'Une relecture des années après, et toujours le même plaisir. Les petits défauts d''usure ne gênent pas du tout la lecture.', '2026-06-26 03:55:34' + INTERVAL @decalage DAY, 'visible', '2026-06-27 04:55:34' + INTERVAL @decalage DAY, 1),
(1041, 1001, 1039, 5, 'Un vrai bijou', 'Je l''ai dévoré en deux soirées. L''écriture est fluide, on s''attache tout de suite aux personnages et la fin m''a laissé sans voix.', '2026-01-11 18:01:45' + INTERVAL @decalage DAY, 'visible', '2026-01-13 22:01:45' + INTERVAL @decalage DAY, 1),
(1042, 1026, 1039, 5, 'Lu d''une traite', 'Acheté d''occasion en très bon état pour une bouchée de pain, et quelle découverte ! Je comprends pourquoi tout le monde en parle.', '2026-07-28 23:24:00' + INTERVAL @decalage DAY, 'visible', '2026-07-30 03:24:00' + INTERVAL @decalage DAY, 1),
(1043, 1013, 1040, 3, 'Correct sans plus', 'Pratique pour réviser, même si certaines explications mériteraient d''être plus claires.', '2026-06-16 14:15:25' + INTERVAL @decalage DAY, 'visible', '2026-06-18 15:15:25' + INTERVAL @decalage DAY, 1),
(1044, 1017, 1040, 5, 'Un incontournable', 'Acheté d''occasion en très bon état pour une bouchée de pain, et quelle découverte ! Je comprends pourquoi tout le monde en parle.', '2025-08-13 19:45:58' + INTERVAL @decalage DAY, 'visible', '2025-08-14 20:45:58' + INTERVAL @decalage DAY, 1),
(1046, 1027, 1040, 5, 'Un incontournable', 'Un livre qui reste en tête longtemps après l''avoir refermé. Je l''ai déjà prêté à deux amis qui ont adoré aussi.', '2025-09-10 17:02:41' + INTERVAL @decalage DAY, 'visible', '2025-09-12 19:02:41' + INTERVAL @decalage DAY, 1),
(1048, 1025, 1041, 4, 'Très bonne lecture', 'Livre arrivé rapidement et bien emballé. Le contenu est à la hauteur de sa réputation, même si je m''attendais à plus de surprises.', '2025-12-25 11:57:12' + INTERVAL @decalage DAY, 'visible', '2025-12-27 13:57:12' + INTERVAL @decalage DAY, 1),
(1049, 1046, 1042, 5, 'Impossible à lâcher', 'Acheté d''occasion en très bon état pour une bouchée de pain, et quelle découverte ! Je comprends pourquoi tout le monde en parle.', '2026-08-28 03:33:39' + INTERVAL @decalage DAY, 'visible', '2026-08-29 06:33:39' + INTERVAL @decalage DAY, 1),
(1050, 1001, 1042, 4, 'Très bonne lecture', 'Agréable à lire, idéal pour les trajets en transport. Je lirai la suite avec plaisir.', '2026-02-11 15:30:01' + INTERVAL @decalage DAY, 'visible', '2026-02-13 16:30:01' + INTERVAL @decalage DAY, 1),
(1051, 1024, 1042, 5, 'À offrir sans hésiter', 'Un livre qui reste en tête longtemps après l''avoir refermé. Je l''ai déjà prêté à deux amis qui ont adoré aussi.', '2025-09-30 19:25:05' + INTERVAL @decalage DAY, 'visible', '2025-10-01 21:25:05' + INTERVAL @decalage DAY, 1),
(1052, 1015, 1043, 5, 'Un incontournable', 'Acheté d''occasion en très bon état pour une bouchée de pain, et quelle découverte ! Je comprends pourquoi tout le monde en parle.', '2025-09-29 18:43:47' + INTERVAL @decalage DAY, 'visible', '2025-09-30 22:43:47' + INTERVAL @decalage DAY, 1),
(1053, 1028, 1043, 4, 'Je ne regrette pas', 'Bon rapport qualité-prix pour un livre en bon état. Une lecture enrichissante que je recommande.', '2025-03-09 01:36:44' + INTERVAL @decalage DAY, 'visible', '2025-03-10 03:36:44' + INTERVAL @decalage DAY, 1),
(1054, 1045, 1043, 5, 'Un incontournable', 'Tout est réussi : le rythme, l''ambiance, les dialogues. Une lecture que je conseille autant aux habitués qu''aux curieux.', '2025-04-08 04:59:49' + INTERVAL @decalage DAY, 'visible', '2025-04-09 07:59:49' + INTERVAL @decalage DAY, 1),
(1055, 1011, 1043, 5, 'Magnifique', 'Acheté d''occasion en très bon état pour une bouchée de pain, et quelle découverte ! Je comprends pourquoi tout le monde en parle.', '2025-07-06 04:23:34' + INTERVAL @decalage DAY, 'visible', '2025-07-07 07:23:34' + INTERVAL @decalage DAY, 1),
(1056, 1041, 1044, 5, 'À offrir sans hésiter', 'Exemplaire reçu en parfait état, conforme à la description. Quant au livre lui-même, c''est une merveille que je relirai sûrement.', '2025-06-11 10:03:38' + INTERVAL @decalage DAY, 'visible', '2025-06-13 15:03:38' + INTERVAL @decalage DAY, 1),
(1057, 1009, 1044, 3, 'Un peu long', 'Le début m''a beaucoup plu, mais j''ai trouvé la suite répétitive. Ça reste une lecture correcte.', '2025-09-10 18:50:09' + INTERVAL @decalage DAY, 'visible', '2025-09-12 22:50:09' + INTERVAL @decalage DAY, 1),
(1058, 1020, 1044, 5, 'À offrir sans hésiter', 'Tout est réussi : le rythme, l''ambiance, les dialogues. Une lecture que je conseille autant aux habitués qu''aux curieux.', '2025-06-12 16:26:11' + INTERVAL @decalage DAY, 'visible', '2025-06-13 21:26:11' + INTERVAL @decalage DAY, 1),
(1059, 1024, 1044, 3, 'Un peu long', 'Le début m''a beaucoup plu, mais j''ai trouvé la suite répétitive. Ça reste une lecture correcte.', '2025-06-24 13:26:11' + INTERVAL @decalage DAY, 'visible', '2025-06-26 16:26:11' + INTERVAL @decalage DAY, 1),
(1060, 1006, 1045, 5, 'À offrir sans hésiter', 'Tout est réussi : le rythme, l''ambiance, les dialogues. Une lecture que je conseille autant aux habitués qu''aux curieux.', '2026-10-02 12:49:09' + INTERVAL @decalage DAY, 'visible', '2026-10-03 15:49:09' + INTERVAL @decalage DAY, 1),
(1061, 1045, 1045, 4, 'Captivant', 'Livre arrivé rapidement et bien emballé. Le contenu est à la hauteur de sa réputation, même si je m''attendais à plus de surprises.', '2026-10-24 11:30:29' + INTERVAL @decalage DAY, 'visible', NULL, NULL),
(1062, 1007, 1046, 3, 'Un peu long', 'Pratique pour réviser, même si certaines explications mériteraient d''être plus claires.', '2025-06-15 19:13:46' + INTERVAL @decalage DAY, 'visible', '2025-06-17 22:13:46' + INTERVAL @decalage DAY, 1),
(1064, 1042, 1047, 5, 'À offrir sans hésiter', 'Clair, passionnant et jamais ennuyeux. On apprend énormément sans s''en rendre compte.', '2026-02-08 04:15:22' + INTERVAL @decalage DAY, 'visible', '2026-02-10 06:15:22' + INTERVAL @decalage DAY, 1),
(1065, 1029, 1047, 5, 'Coup de cœur', 'Clair, passionnant et jamais ennuyeux. On apprend énormément sans s''en rendre compte.', '2026-02-20 02:26:58' + INTERVAL @decalage DAY, 'visible', '2026-02-21 07:26:58' + INTERVAL @decalage DAY, 1),
(1066, 1009, 1047, 4, 'Une lecture agréable', 'Livre arrivé rapidement et bien emballé. Le contenu est à la hauteur de sa réputation, même si je m''attendais à plus de surprises.', '2026-03-06 22:26:58' + INTERVAL @decalage DAY, 'visible', '2026-03-09 01:26:58' + INTERVAL @decalage DAY, 1),
(1067, 1008, 1047, 5, 'Coup de cœur', 'Exemplaire reçu en parfait état, conforme à la description. Quant au livre lui-même, c''est une merveille que je relirai sûrement.', '2025-08-02 13:17:21' + INTERVAL @decalage DAY, 'visible', '2025-08-03 16:17:21' + INTERVAL @decalage DAY, 1),
(1070, 1039, 1048, 5, 'Un vrai bijou', 'Mon fils l''a lu puis je l''ai lu à mon tour : on en parle encore à table. Un vrai moment de partage.', '2025-04-12 21:18:57' + INTERVAL @decalage DAY, 'visible', '2025-04-14 00:18:57' + INTERVAL @decalage DAY, 1),
(1071, 1022, 1048, 4, 'Je ne regrette pas', 'Livre arrivé rapidement et bien emballé. Le contenu est à la hauteur de sa réputation, même si je m''attendais à plus de surprises.', '2026-04-26 22:39:47' + INTERVAL @decalage DAY, 'visible', '2026-04-29 03:39:47' + INTERVAL @decalage DAY, 1),
(1072, 1029, 1048, 5, 'Magnifique', 'Clair, passionnant et jamais ennuyeux. On apprend énormément sans s''en rendre compte.', '2025-03-23 17:40:42' + INTERVAL @decalage DAY, 'visible', '2025-03-25 22:40:42' + INTERVAL @decalage DAY, 1),
(1074, 1010, 1049, 4, 'Une lecture agréable', 'Très bonne lecture dans l''ensemble, avec quelques passages un peu plus lents au milieu. La dernière partie rattrape largement.', '2025-08-09 19:17:36' + INTERVAL @decalage DAY, 'visible', '2025-08-11 23:17:36' + INTERVAL @decalage DAY, 1),
(1075, 1017, 1049, 5, 'À offrir sans hésiter', 'Un livre qui reste en tête longtemps après l''avoir refermé. Je l''ai déjà prêté à deux amis qui ont adoré aussi.', '2026-08-11 20:42:58' + INTERVAL @decalage DAY, 'visible', '2026-08-12 23:42:58' + INTERVAL @decalage DAY, 1),
(1077, 1012, 1050, 3, 'Intéressant mais inégal', 'Pratique pour réviser, même si certaines explications mériteraient d''être plus claires.', '2024-09-27 13:48:09' + INTERVAL @decalage DAY, 'visible', '2024-09-29 18:48:09' + INTERVAL @decalage DAY, 1),
(1079, 1023, 1050, 3, 'Un peu long', 'Le début m''a beaucoup plu, mais j''ai trouvé la suite répétitive. Ça reste une lecture correcte.', '2025-12-01 20:11:15' + INTERVAL @decalage DAY, 'visible', '2025-12-02 22:11:15' + INTERVAL @decalage DAY, 1),
(1080, 1002, 1050, 5, 'Je recommande les yeux fermés', 'Clair, passionnant et jamais ennuyeux. On apprend énormément sans s''en rendre compte.', '2024-10-07 21:39:07' + INTERVAL @decalage DAY, 'visible', '2024-10-10 01:39:07' + INTERVAL @decalage DAY, 1),
(1081, 1041, 1050, 3, 'Intéressant mais inégal', 'Quelques bonnes idées, mais le style ne m''a pas totalement convaincu. À tenter si le sujet vous intéresse.', '2025-11-04 02:45:08' + INTERVAL @decalage DAY, 'visible', '2025-11-06 04:45:08' + INTERVAL @decalage DAY, 1),
(1082, 1033, 1050, 4, 'Bien construit', 'Agréable à lire, idéal pour les trajets en transport. Je lirai la suite avec plaisir.', '2025-01-11 21:48:09' + INTERVAL @decalage DAY, 'visible', '2025-01-13 23:48:09' + INTERVAL @decalage DAY, 1),
(1083, 1013, 1050, 4, 'Une lecture agréable', 'Sérieux et bien documenté, parfois un peu dense mais toujours intéressant.', '2025-05-08 00:30:55' + INTERVAL @decalage DAY, 'visible', '2025-05-09 04:30:55' + INTERVAL @decalage DAY, 1),
(1084, 1025, 1050, 5, 'Un incontournable', 'Exemplaire reçu en parfait état, conforme à la description. Quant au livre lui-même, c''est une merveille que je relirai sûrement.', '2025-01-28 23:45:41' + INTERVAL @decalage DAY, 'visible', '2025-01-31 02:45:41' + INTERVAL @decalage DAY, 1),
(1085, 1034, 1050, 2, 'Je n''ai pas accroché', 'Trop long à mon goût, j''ai fini par survoler les derniers chapitres.', '2024-09-19 03:39:07' + INTERVAL @decalage DAY, 'visible', '2024-09-21 06:39:07' + INTERVAL @decalage DAY, 1);

INSERT INTO avis_signalement (id_avis, id_user, motif, date_signalement, traite) VALUES
(1046, 1044, 'spoiler', '2026-10-24 21:12:00' + INTERVAL @decalage DAY, 0);

INSERT IGNORE INTO point_mouvement (id_user, points, type, libelle, reference, statut, date_mouvement) VALUES
(1003, 10, 'presence', 'Présence à « Troc de livres de la rentrée »', 'inscription:1061', 'valide', '2026-09-19 12:00:00' + INTERVAL @decalage DAY),
(1003, 5, 'badge', 'Badge « Habitué des rencontres » bronze', 'habitue_rencontres:1', 'valide', '2026-09-19 12:00:00' + INTERVAL @decalage DAY),
(1008, 13, 'achat', 'Commande n° 1001', 'commande:1001', 'valide', '2026-03-22 12:26:38' + INTERVAL @decalage DAY),
(1008, 20, 'premier_achat', 'Bonus premier achat', 'commande:1001', 'valide', '2026-03-22 12:26:38' + INTERVAL @decalage DAY),
(1008, 5, 'badge', 'Badge « Lecteur fidèle » bronze', 'lecteur_fidele:1', 'valide', '2026-03-22 12:26:38' + INTERVAL @decalage DAY),
(1008, 42, 'achat', 'Commande n° 1002', 'commande:1002', 'valide', '2026-04-13 18:50:04' + INTERVAL @decalage DAY),
(1008, 38, 'achat', 'Commande n° 1003', 'commande:1003', 'valide', '2026-09-05 22:56:29' + INTERVAL @decalage DAY),
(1008, 10, 'presence', 'Présence à « Troc de livres de la rentrée »', 'inscription:1080', 'valide', '2026-09-19 10:00:00' + INTERVAL @decalage DAY),
(1008, 5, 'badge', 'Badge « Habitué des rencontres » bronze', 'habitue_rencontres:1', 'valide', '2026-09-19 10:00:00' + INTERVAL @decalage DAY),
(1009, 58, 'achat', 'Commande n° 1004', 'commande:1004', 'valide', '2026-08-17 19:59:53' + INTERVAL @decalage DAY),
(1009, 20, 'premier_achat', 'Bonus premier achat', 'commande:1004', 'valide', '2026-08-17 19:59:53' + INTERVAL @decalage DAY),
(1009, 5, 'badge', 'Badge « Lecteur fidèle » bronze', 'lecteur_fidele:1', 'valide', '2026-08-17 19:59:53' + INTERVAL @decalage DAY),
(1010, 56, 'achat', 'Commande n° 1005', 'commande:1005', 'valide', '2026-07-27 20:06:35' + INTERVAL @decalage DAY),
(1010, 20, 'premier_achat', 'Bonus premier achat', 'commande:1005', 'valide', '2026-07-27 20:06:35' + INTERVAL @decalage DAY),
(1010, 5, 'badge', 'Badge « Lecteur fidèle » bronze', 'lecteur_fidele:1', 'valide', '2026-07-27 20:06:35' + INTERVAL @decalage DAY),
(1010, 5, 'avis', 'Avis sur « Hitchcock / Truffaut - Édition définitive »', 'livre:1041', 'valide', '2026-08-01 04:06:35' + INTERVAL @decalage DAY),
(1010, 10, 'premier_avis', 'Bonus premier avis validé', 'premier', 'valide', '2026-08-03 07:06:35' + INTERVAL @decalage DAY),
(1010, 5, 'badge', 'Badge « Plume critique » bronze', 'plume_critique:1', 'valide', '2026-08-03 07:06:35' + INTERVAL @decalage DAY),
(1010, 14, 'achat', 'Commande n° 1006', 'commande:1006', 'valide', '2026-10-21 08:38:24' + INTERVAL @decalage DAY),
(1011, 9, 'achat', 'Commande n° 1007', 'commande:1007', 'valide', '2026-01-28 21:14:18' + INTERVAL @decalage DAY),
(1011, 20, 'premier_achat', 'Bonus premier achat', 'commande:1007', 'valide', '2026-01-28 21:14:18' + INTERVAL @decalage DAY),
(1011, 5, 'badge', 'Badge « Lecteur fidèle » bronze', 'lecteur_fidele:1', 'valide', '2026-01-28 21:14:18' + INTERVAL @decalage DAY),
(1011, 11, 'achat', 'Commande n° 1008', 'commande:1008', 'valide', '2026-03-23 20:51:27' + INTERVAL @decalage DAY),
(1011, 9, 'achat', 'Commande n° 1009', 'commande:1009', 'valide', '2026-05-12 17:43:07' + INTERVAL @decalage DAY),
(1011, 16, 'achat', 'Commande n° 1010', 'commande:1010', 'valide', '2026-06-28 09:47:43' + INTERVAL @decalage DAY),
(1011, 5, 'avis', 'Avis sur « Comme un roman »', 'livre:1044', 'valide', '2026-07-06 14:47:43' + INTERVAL @decalage DAY),
(1011, 10, 'premier_avis', 'Bonus premier avis validé', 'premier', 'valide', '2026-07-08 17:47:43' + INTERVAL @decalage DAY),
(1011, 5, 'badge', 'Badge « Plume critique » bronze', 'plume_critique:1', 'valide', '2026-07-08 17:47:43' + INTERVAL @decalage DAY),
(1011, 4, 'achat', 'Commande n° 1011', 'commande:1011', 'valide', '2026-07-10 16:52:14' + INTERVAL @decalage DAY),
(1011, 15, 'badge', 'Badge « Lecteur fidèle » argent', 'lecteur_fidele:2', 'valide', '2026-07-10 16:52:14' + INTERVAL @decalage DAY),
(1011, 10, 'achat', 'Commande n° 1012', 'commande:1012', 'valide', '2026-08-20 12:03:36' + INTERVAL @decalage DAY),
(1011, 10, 'presence', 'Présence à « Troc de livres de la rentrée »', 'inscription:1066', 'valide', '2026-09-19 11:00:00' + INTERVAL @decalage DAY),
(1011, 5, 'badge', 'Badge « Habitué des rencontres » bronze', 'habitue_rencontres:1', 'valide', '2026-09-19 11:00:00' + INTERVAL @decalage DAY),
(1012, 25, 'achat', 'Commande n° 1013', 'commande:1013', 'valide', '2025-09-18 10:40:25' + INTERVAL @decalage DAY),
(1012, 20, 'premier_achat', 'Bonus premier achat', 'commande:1013', 'valide', '2025-09-18 10:40:25' + INTERVAL @decalage DAY),
(1012, 5, 'badge', 'Badge « Lecteur fidèle » bronze', 'lecteur_fidele:1', 'valide', '2025-09-18 10:40:25' + INTERVAL @decalage DAY),
(1012, 34, 'achat', 'Commande n° 1014', 'commande:1014', 'valide', '2025-10-20 12:58:49' + INTERVAL @decalage DAY),
(1012, 8, 'achat', 'Commande n° 1015', 'commande:1015', 'valide', '2026-04-13 11:43:21' + INTERVAL @decalage DAY),
(1013, 17, 'achat', 'Commande n° 1016', 'commande:1016', 'valide', '2026-06-21 09:08:45' + INTERVAL @decalage DAY),
(1013, 20, 'premier_achat', 'Bonus premier achat', 'commande:1016', 'valide', '2026-06-21 09:08:45' + INTERVAL @decalage DAY),
(1013, 5, 'badge', 'Badge « Lecteur fidèle » bronze', 'lecteur_fidele:1', 'valide', '2026-06-21 09:08:45' + INTERVAL @decalage DAY),
(1013, 5, 'avis', 'Avis sur « Le Potager du paresseux »', 'livre:1015', 'valide', '2026-06-26 17:08:45' + INTERVAL @decalage DAY),
(1013, 10, 'premier_avis', 'Bonus premier avis validé', 'premier', 'valide', '2026-06-28 21:08:45' + INTERVAL @decalage DAY),
(1013, 5, 'badge', 'Badge « Plume critique » bronze', 'plume_critique:1', 'valide', '2026-06-28 21:08:45' + INTERVAL @decalage DAY),
(1013, 10, 'achat', 'Commande n° 1017', 'commande:1017', 'valide', '2026-08-14 19:21:28' + INTERVAL @decalage DAY),
(1013, 6, 'achat', 'Commande n° 1018', 'commande:1018', 'valide', '2026-08-26 20:16:10' + INTERVAL @decalage DAY),
(1014, 44, 'achat', 'Commande n° 1019', 'commande:1019', 'valide', '2026-08-18 19:52:45' + INTERVAL @decalage DAY),
(1014, 20, 'premier_achat', 'Bonus premier achat', 'commande:1019', 'valide', '2026-08-18 19:52:45' + INTERVAL @decalage DAY),
(1014, 5, 'badge', 'Badge « Lecteur fidèle » bronze', 'lecteur_fidele:1', 'valide', '2026-08-18 19:52:45' + INTERVAL @decalage DAY),
(1014, 5, 'avis', 'Avis sur « I Am Not Your Negro »', 'livre:9', 'valide', '2026-09-11 22:52:45' + INTERVAL @decalage DAY),
(1014, 10, 'premier_avis', 'Bonus premier avis validé', 'premier', 'valide', '2026-09-13 00:52:45' + INTERVAL @decalage DAY),
(1014, 5, 'badge', 'Badge « Plume critique » bronze', 'plume_critique:1', 'valide', '2026-09-13 00:52:45' + INTERVAL @decalage DAY),
(1015, 84, 'achat', 'Commande n° 1020', 'commande:1020', 'valide', '2026-01-23 10:27:17' + INTERVAL @decalage DAY),
(1015, 20, 'premier_achat', 'Bonus premier achat', 'commande:1020', 'valide', '2026-01-23 10:27:17' + INTERVAL @decalage DAY),
(1015, 5, 'badge', 'Badge « Lecteur fidèle » bronze', 'lecteur_fidele:1', 'valide', '2026-01-23 10:27:17' + INTERVAL @decalage DAY),
(1015, 5, 'avis', 'Avis sur « Absolute Batman - Tome 2 »', 'livre:10', 'valide', '2026-02-19 13:27:17' + INTERVAL @decalage DAY),
(1015, 10, 'premier_avis', 'Bonus premier avis validé', 'premier', 'valide', '2026-02-21 18:27:17' + INTERVAL @decalage DAY),
(1015, 5, 'badge', 'Badge « Plume critique » bronze', 'plume_critique:1', 'valide', '2026-02-21 18:27:17' + INTERVAL @decalage DAY),
(1015, 10, 'presence', 'Présence à « Troc de livres de la rentrée »', 'inscription:1072', 'valide', '2026-09-19 11:00:00' + INTERVAL @decalage DAY),
(1015, 5, 'badge', 'Badge « Habitué des rencontres » bronze', 'habitue_rencontres:1', 'valide', '2026-09-19 11:00:00' + INTERVAL @decalage DAY),
(1016, 7, 'achat', 'Commande n° 1021', 'commande:1021', 'valide', '2026-09-30 18:40:55' + INTERVAL @decalage DAY),
(1016, 20, 'premier_achat', 'Bonus premier achat', 'commande:1021', 'valide', '2026-09-30 18:40:55' + INTERVAL @decalage DAY),
(1016, 5, 'badge', 'Badge « Lecteur fidèle » bronze', 'lecteur_fidele:1', 'valide', '2026-09-30 18:40:55' + INTERVAL @decalage DAY),
(1016, 7, 'achat', 'Commande n° 1022', 'commande:1022', 'valide', '2026-10-16 17:17:35' + INTERVAL @decalage DAY),
(1016, 5, 'avis', 'Avis sur « Les Fleurs du mal »', 'livre:1002', 'en_attente', '2026-10-20 08:09:12' + INTERVAL @decalage DAY),
(1016, 32, 'achat', 'Commande n° 1023', 'commande:1023', 'valide', '2026-10-20 10:42:39' + INTERVAL @decalage DAY),
(1017, 10, 'achat', 'Commande n° 1024', 'commande:1024', 'valide', '2026-06-09 19:24:10' + INTERVAL @decalage DAY),
(1017, 20, 'premier_achat', 'Bonus premier achat', 'commande:1024', 'valide', '2026-06-09 19:24:10' + INTERVAL @decalage DAY),
(1017, 5, 'badge', 'Badge « Lecteur fidèle » bronze', 'lecteur_fidele:1', 'valide', '2026-06-09 19:24:10' + INTERVAL @decalage DAY),
(1017, 18, 'achat', 'Commande n° 1025', 'commande:1025', 'valide', '2026-06-18 08:50:55' + INTERVAL @decalage DAY),
(1017, 7, 'achat', 'Commande n° 1026', 'commande:1026', 'valide', '2026-08-14 15:22:21' + INTERVAL @decalage DAY),
(1017, 9, 'achat', 'Commande n° 1027', 'commande:1027', 'valide', '2026-08-14 18:13:55' + INTERVAL @decalage DAY),
(1017, 14, 'achat', 'Commande n° 1028', 'commande:1028', 'valide', '2026-08-22 13:22:59' + INTERVAL @decalage DAY),
(1017, 19, 'achat', 'Commande n° 1029', 'commande:1029', 'valide', '2026-08-29 08:27:39' + INTERVAL @decalage DAY),
(1017, 15, 'badge', 'Badge « Lecteur fidèle » argent', 'lecteur_fidele:2', 'valide', '2026-08-29 08:27:39' + INTERVAL @decalage DAY),
(1017, 5, 'avis', 'Avis sur « Le Petit Nicolas »', 'livre:1047', 'valide', '2026-09-12 10:27:39' + INTERVAL @decalage DAY),
(1017, 10, 'premier_avis', 'Bonus premier avis validé', 'premier', 'valide', '2026-09-13 15:27:39' + INTERVAL @decalage DAY),
(1017, 5, 'badge', 'Badge « Plume critique » bronze', 'plume_critique:1', 'valide', '2026-09-13 15:27:39' + INTERVAL @decalage DAY),
(1017, 10, 'presence', 'Présence à « Troc de livres de la rentrée »', 'inscription:1075', 'valide', '2026-09-19 12:00:00' + INTERVAL @decalage DAY),
(1017, 5, 'badge', 'Badge « Habitué des rencontres » bronze', 'habitue_rencontres:1', 'valide', '2026-09-19 12:00:00' + INTERVAL @decalage DAY),
(1018, 41, 'achat', 'Commande n° 1030', 'commande:1030', 'valide', '2026-06-19 15:05:24' + INTERVAL @decalage DAY),
(1018, 20, 'premier_achat', 'Bonus premier achat', 'commande:1030', 'valide', '2026-06-19 15:05:24' + INTERVAL @decalage DAY),
(1018, 5, 'badge', 'Badge « Lecteur fidèle » bronze', 'lecteur_fidele:1', 'valide', '2026-06-19 15:05:24' + INTERVAL @decalage DAY),
(1018, 22, 'achat', 'Commande n° 1031', 'commande:1031', 'valide', '2026-06-25 10:18:18' + INTERVAL @decalage DAY),
(1018, 46, 'achat', 'Commande n° 1032', 'commande:1032', 'valide', '2026-09-21 18:14:40' + INTERVAL @decalage DAY),
(1019, 28, 'achat', 'Commande n° 1033', 'commande:1033', 'valide', '2025-11-01 17:15:39' + INTERVAL @decalage DAY),
(1019, 20, 'premier_achat', 'Bonus premier achat', 'commande:1033', 'valide', '2025-11-01 17:15:39' + INTERVAL @decalage DAY),
(1019, 5, 'badge', 'Badge « Lecteur fidèle » bronze', 'lecteur_fidele:1', 'valide', '2025-11-01 17:15:39' + INTERVAL @decalage DAY),
(1019, 9, 'achat', 'Commande n° 1034', 'commande:1034', 'valide', '2025-12-03 15:53:02' + INTERVAL @decalage DAY),
(1019, 11, 'achat', 'Commande n° 1035', 'commande:1035', 'valide', '2026-06-04 21:59:35' + INTERVAL @decalage DAY),
(1019, 17, 'achat', 'Commande n° 1036', 'commande:1036', 'valide', '2026-06-08 20:40:58' + INTERVAL @decalage DAY),
(1019, 5, 'avis', 'Avis sur « Avant toi »', 'livre:1034', 'valide', '2026-07-13 00:59:35' + INTERVAL @decalage DAY),
(1019, 10, 'premier_avis', 'Bonus premier avis validé', 'premier', 'valide', '2026-07-14 03:59:35' + INTERVAL @decalage DAY),
(1019, 5, 'badge', 'Badge « Plume critique » bronze', 'plume_critique:1', 'valide', '2026-07-14 03:59:35' + INTERVAL @decalage DAY),
(1020, 13, 'achat', 'Commande n° 1037', 'commande:1037', 'valide', '2026-07-26 09:34:02' + INTERVAL @decalage DAY),
(1020, 20, 'premier_achat', 'Bonus premier achat', 'commande:1037', 'valide', '2026-07-26 09:34:02' + INTERVAL @decalage DAY),
(1020, 5, 'badge', 'Badge « Lecteur fidèle » bronze', 'lecteur_fidele:1', 'valide', '2026-07-26 09:34:02' + INTERVAL @decalage DAY),
(1020, 28, 'achat', 'Commande n° 1038', 'commande:1038', 'valide', '2026-08-02 20:59:53' + INTERVAL @decalage DAY),
(1020, 13, 'achat', 'Commande n° 1039', 'commande:1039', 'valide', '2026-08-22 15:41:56' + INTERVAL @decalage DAY),
(1020, 10, 'achat', 'Commande n° 1040', 'commande:1040', 'valide', '2026-08-22 17:35:55' + INTERVAL @decalage DAY),
(1020, 3, 'achat', 'Commande n° 1041', 'commande:1041', 'valide', '2026-09-06 17:30:53' + INTERVAL @decalage DAY),
(1020, 10, 'presence', 'Présence à « Troc de livres de la rentrée »', 'inscription:1063', 'valide', '2026-09-19 10:00:00' + INTERVAL @decalage DAY),
(1020, 5, 'badge', 'Badge « Habitué des rencontres » bronze', 'habitue_rencontres:1', 'valide', '2026-09-19 10:00:00' + INTERVAL @decalage DAY),
(1020, 2, 'achat', 'Commande n° 1042', 'commande:1042', 'valide', '2026-10-08 09:59:15' + INTERVAL @decalage DAY),
(1020, 15, 'badge', 'Badge « Lecteur fidèle » argent', 'lecteur_fidele:2', 'valide', '2026-10-08 09:59:15' + INTERVAL @decalage DAY),
(1020, 15, 'achat', 'Commande n° 1043', 'commande:1043', 'valide', '2026-10-20 16:45:34' + INTERVAL @decalage DAY),
(1020, 5, 'avis', 'Avis sur « Bleach - Tome 01 »', 'livre:3', 'en_attente', '2026-10-23 17:59:15' + INTERVAL @decalage DAY),
(1021, 19, 'achat', 'Commande n° 1044', 'commande:1044', 'valide', '2026-03-29 19:24:55' + INTERVAL @decalage DAY),
(1021, 20, 'premier_achat', 'Bonus premier achat', 'commande:1044', 'valide', '2026-03-29 19:24:55' + INTERVAL @decalage DAY),
(1021, 5, 'badge', 'Badge « Lecteur fidèle » bronze', 'lecteur_fidele:1', 'valide', '2026-03-29 19:24:55' + INTERVAL @decalage DAY),
(1021, 5, 'avis', 'Avis sur « Le Robert Junior illustré »', 'livre:1005', 'valide', '2026-04-10 03:24:55' + INTERVAL @decalage DAY),
(1021, 10, 'premier_avis', 'Bonus premier avis validé', 'premier', 'valide', '2026-04-12 04:24:55' + INTERVAL @decalage DAY),
(1021, 5, 'badge', 'Badge « Plume critique » bronze', 'plume_critique:1', 'valide', '2026-04-12 04:24:55' + INTERVAL @decalage DAY),
(1021, 48, 'achat', 'Commande n° 1045', 'commande:1045', 'valide', '2026-04-24 15:59:22' + INTERVAL @decalage DAY),
(1021, 11, 'achat', 'Commande n° 1046', 'commande:1046', 'valide', '2026-07-16 11:15:02' + INTERVAL @decalage DAY),
(1021, -100, 'depense', 'Bon de 5,00 € (commande n° 1047)', 'commande:1047', 'valide', '2026-07-31 13:44:01' + INTERVAL @decalage DAY),
(1021, 17, 'achat', 'Commande n° 1047', 'commande:1047', 'valide', '2026-07-31 13:44:01' + INTERVAL @decalage DAY),
(1021, 15, 'badge', 'Badge « Lecteur fidèle » argent', 'lecteur_fidele:2', 'valide', '2026-07-31 13:44:01' + INTERVAL @decalage DAY),
(1022, 3, 'achat', 'Commande n° 1048', 'commande:1048', 'valide', '2026-01-20 18:47:53' + INTERVAL @decalage DAY),
(1022, 20, 'premier_achat', 'Bonus premier achat', 'commande:1048', 'valide', '2026-01-20 18:47:53' + INTERVAL @decalage DAY),
(1022, 5, 'badge', 'Badge « Lecteur fidèle » bronze', 'lecteur_fidele:1', 'valide', '2026-01-20 18:47:53' + INTERVAL @decalage DAY),
(1022, 53, 'achat', 'Commande n° 1049', 'commande:1049', 'valide', '2026-02-27 19:17:01' + INTERVAL @decalage DAY),
(1022, 9, 'achat', 'Commande n° 1050', 'commande:1050', 'valide', '2026-04-03 08:43:07' + INTERVAL @decalage DAY),
(1022, 9, 'achat', 'Commande n° 1051', 'commande:1051', 'valide', '2026-08-26 15:26:41' + INTERVAL @decalage DAY),
(1022, 10, 'presence', 'Présence à « Troc de livres de la rentrée »', 'inscription:1064', 'valide', '2026-09-19 10:00:00' + INTERVAL @decalage DAY),
(1022, 5, 'badge', 'Badge « Habitué des rencontres » bronze', 'habitue_rencontres:1', 'valide', '2026-09-19 10:00:00' + INTERVAL @decalage DAY),
(1023, 23, 'achat', 'Commande n° 1052', 'commande:1052', 'valide', '2026-02-17 21:53:58' + INTERVAL @decalage DAY),
(1023, 20, 'premier_achat', 'Bonus premier achat', 'commande:1052', 'valide', '2026-02-17 21:53:58' + INTERVAL @decalage DAY),
(1023, 5, 'badge', 'Badge « Lecteur fidèle » bronze', 'lecteur_fidele:1', 'valide', '2026-02-17 21:53:58' + INTERVAL @decalage DAY),
(1023, 4, 'achat', 'Commande n° 1053', 'commande:1053', 'valide', '2026-04-30 15:26:08' + INTERVAL @decalage DAY),
(1023, 5, 'avis', 'Avis sur « I Am Not Your Negro »', 'livre:9', 'valide', '2026-05-16 22:26:08' + INTERVAL @decalage DAY),
(1023, 10, 'premier_avis', 'Bonus premier avis validé', 'premier', 'valide', '2026-05-19 03:26:08' + INTERVAL @decalage DAY),
(1023, 5, 'badge', 'Badge « Plume critique » bronze', 'plume_critique:1', 'valide', '2026-05-19 03:26:08' + INTERVAL @decalage DAY),
(1023, 10, 'presence', 'Présence à « Troc de livres de la rentrée »', 'inscription:1069', 'valide', '2026-09-19 10:00:00' + INTERVAL @decalage DAY),
(1023, 5, 'badge', 'Badge « Habitué des rencontres » bronze', 'habitue_rencontres:1', 'valide', '2026-09-19 10:00:00' + INTERVAL @decalage DAY),
(1023, 29, 'achat', 'Commande n° 1054', 'commande:1054', 'valide', '2026-10-20 17:27:09' + INTERVAL @decalage DAY),
(1024, 3, 'achat', 'Commande n° 1055', 'commande:1055', 'valide', '2026-08-28 10:37:42' + INTERVAL @decalage DAY),
(1024, 20, 'premier_achat', 'Bonus premier achat', 'commande:1055', 'valide', '2026-08-28 10:37:42' + INTERVAL @decalage DAY),
(1024, 5, 'badge', 'Badge « Lecteur fidèle » bronze', 'lecteur_fidele:1', 'valide', '2026-08-28 10:37:42' + INTERVAL @decalage DAY),
(1024, 7, 'achat', 'Commande n° 1056', 'commande:1056', 'valide', '2026-09-11 15:39:53' + INTERVAL @decalage DAY),
(1024, 23, 'achat', 'Commande n° 1057', 'commande:1057', 'valide', '2026-09-15 22:17:55' + INTERVAL @decalage DAY),
(1024, 23, 'achat', 'Commande n° 1058', 'commande:1058', 'valide', '2026-09-21 22:13:10' + INTERVAL @decalage DAY),
(1024, 13, 'achat', 'Commande n° 1059', 'commande:1059', 'valide', '2026-10-03 14:26:15' + INTERVAL @decalage DAY),
(1024, 15, 'badge', 'Badge « Lecteur fidèle » argent', 'lecteur_fidele:2', 'valide', '2026-10-03 14:26:15' + INTERVAL @decalage DAY),
(1025, 28, 'achat', 'Commande n° 1060', 'commande:1060', 'valide', '2025-10-18 09:33:07' + INTERVAL @decalage DAY),
(1025, 20, 'premier_achat', 'Bonus premier achat', 'commande:1060', 'valide', '2025-10-18 09:33:07' + INTERVAL @decalage DAY),
(1025, 5, 'badge', 'Badge « Lecteur fidèle » bronze', 'lecteur_fidele:1', 'valide', '2025-10-18 09:33:07' + INTERVAL @decalage DAY),
(1025, 15, 'achat', 'Commande n° 1061', 'commande:1061', 'valide', '2025-11-14 14:30:06' + INTERVAL @decalage DAY),
(1025, 24, 'achat', 'Commande n° 1062', 'commande:1062', 'valide', '2025-12-15 08:41:45' + INTERVAL @decalage DAY),
(1025, 5, 'avis', 'Avis sur « One Piece - Édition originale - Tome 01 »', 'livre:1038', 'valide', '2025-12-14 21:30:06' + INTERVAL @decalage DAY);

INSERT IGNORE INTO point_mouvement (id_user, points, type, libelle, reference, statut, date_mouvement) VALUES
(1025, 10, 'premier_avis', 'Bonus premier avis validé', 'premier', 'valide', '2025-12-16 22:30:06' + INTERVAL @decalage DAY),
(1025, 5, 'badge', 'Badge « Plume critique » bronze', 'plume_critique:1', 'valide', '2025-12-16 22:30:06' + INTERVAL @decalage DAY),
(1025, 11, 'achat', 'Commande n° 1063', 'commande:1063', 'valide', '2026-03-22 09:43:01' + INTERVAL @decalage DAY),
(1025, 15, 'badge', 'Badge « Lecteur fidèle » argent', 'lecteur_fidele:2', 'valide', '2026-03-22 09:43:01' + INTERVAL @decalage DAY),
(1025, -100, 'depense', 'Bon de 5,00 € (commande n° 1064)', 'commande:1064', 'valide', '2026-03-24 12:02:01' + INTERVAL @decalage DAY),
(1025, 75, 'achat', 'Commande n° 1064', 'commande:1064', 'valide', '2026-03-24 12:02:01' + INTERVAL @decalage DAY),
(1025, 5, 'avis', 'Avis sur « Cyrano de Bergerac »', 'livre:1042', 'valide', '2026-03-27 14:43:01' + INTERVAL @decalage DAY),
(1025, 45, 'achat', 'Commande n° 1065', 'commande:1065', 'valide', '2026-04-06 10:37:24' + INTERVAL @decalage DAY),
(1025, 2, 'achat', 'Commande n° 1066', 'commande:1066', 'valide', '2026-04-14 08:31:53' + INTERVAL @decalage DAY),
(1025, 6, 'achat', 'Commande n° 1067', 'commande:1067', 'valide', '2026-07-17 12:50:16' + INTERVAL @decalage DAY),
(1025, 8, 'achat', 'Commande n° 1068', 'commande:1068', 'valide', '2026-07-31 15:50:18' + INTERVAL @decalage DAY),
(1025, -8, 'annulation', 'Points retirés : commande n° 1068 annulée', 'commande:1068', 'valide', '2026-08-01 15:50:18' + INTERVAL @decalage DAY),
(1025, 13, 'achat', 'Commande n° 1069', 'commande:1069', 'valide', '2026-09-12 17:41:13' + INTERVAL @decalage DAY),
(1025, 30, 'badge', 'Badge « Un an parmi nous »', 'un_an:1', 'valide', '2026-09-12 17:41:13' + INTERVAL @decalage DAY),
(1025, 20, 'don', 'Don récupéré : « Le Capital au XXIe siècle »', 'don:1002', 'valide', '2026-10-08 19:23:06' + INTERVAL @decalage DAY),
(1025, 5, 'badge', 'Badge « Cœur généreux » bronze', 'coeur_genereux:1', 'valide', '2026-10-08 19:23:06' + INTERVAL @decalage DAY),
(1025, 5, 'avis', 'Avis sur « I Am Not Your Negro »', 'livre:9', 'valide', '2026-10-08 20:41:13' + INTERVAL @decalage DAY),
(1026, 63, 'achat', 'Commande n° 1070', 'commande:1070', 'valide', '2025-05-25 22:33:16' + INTERVAL @decalage DAY),
(1026, 20, 'premier_achat', 'Bonus premier achat', 'commande:1070', 'valide', '2025-05-25 22:33:16' + INTERVAL @decalage DAY),
(1026, 5, 'badge', 'Badge « Lecteur fidèle » bronze', 'lecteur_fidele:1', 'valide', '2025-05-25 22:33:16' + INTERVAL @decalage DAY),
(1026, 5, 'avis', 'Avis sur « Le Robert Junior illustré »', 'livre:1005', 'refuse', '2025-06-09 03:33:16' + INTERVAL @decalage DAY),
(1026, 9, 'achat', 'Commande n° 1071', 'commande:1071', 'valide', '2025-07-29 20:31:10' + INTERVAL @decalage DAY),
(1026, 5, 'avis', 'Avis sur « Histoire de l''art »', 'livre:1003', 'valide', '2025-08-08 00:31:10' + INTERVAL @decalage DAY),
(1026, 10, 'premier_avis', 'Bonus premier avis validé', 'premier', 'valide', '2025-08-09 03:31:10' + INTERVAL @decalage DAY),
(1026, 5, 'badge', 'Badge « Plume critique » bronze', 'plume_critique:1', 'valide', '2025-08-09 03:31:10' + INTERVAL @decalage DAY),
(1026, -100, 'depense', 'Bon de 5,00 € (commande n° 1072)', 'commande:1072', 'valide', '2025-08-24 16:50:56' + INTERVAL @decalage DAY),
(1026, 36, 'achat', 'Commande n° 1072', 'commande:1072', 'valide', '2025-08-24 16:50:56' + INTERVAL @decalage DAY),
(1026, 5, 'avis', 'Avis sur « J''intègre - Maths MPSI-MP2I : exercices incontournables »', 'livre:1008', 'valide', '2025-10-02 17:50:56' + INTERVAL @decalage DAY),
(1026, 34, 'achat', 'Commande n° 1073', 'commande:1073', 'valide', '2026-02-18 10:20:18' + INTERVAL @decalage DAY),
(1026, 42, 'achat', 'Commande n° 1074', 'commande:1074', 'valide', '2026-04-10 20:41:02' + INTERVAL @decalage DAY),
(1026, 15, 'badge', 'Badge « Lecteur fidèle » argent', 'lecteur_fidele:2', 'valide', '2026-04-10 20:41:02' + INTERVAL @decalage DAY),
(1026, 30, 'badge', 'Badge « Un an parmi nous »', 'un_an:1', 'valide', '2026-04-10 20:41:02' + INTERVAL @decalage DAY),
(1026, 36, 'achat', 'Commande n° 1075', 'commande:1075', 'valide', '2026-07-15 10:25:44' + INTERVAL @decalage DAY),
(1026, 10, 'presence', 'Présence à « Troc de livres de la rentrée »', 'inscription:1079', 'valide', '2026-09-19 12:00:00' + INTERVAL @decalage DAY),
(1026, 5, 'badge', 'Badge « Habitué des rencontres » bronze', 'habitue_rencontres:1', 'valide', '2026-09-19 12:00:00' + INTERVAL @decalage DAY),
(1027, 49, 'achat', 'Commande n° 1076', 'commande:1076', 'valide', '2026-03-07 22:37:11' + INTERVAL @decalage DAY),
(1027, 20, 'premier_achat', 'Bonus premier achat', 'commande:1076', 'valide', '2026-03-07 22:37:11' + INTERVAL @decalage DAY),
(1027, 5, 'badge', 'Badge « Lecteur fidèle » bronze', 'lecteur_fidele:1', 'valide', '2026-03-07 22:37:11' + INTERVAL @decalage DAY),
(1027, 16, 'achat', 'Commande n° 1077', 'commande:1077', 'valide', '2026-03-15 19:58:14' + INTERVAL @decalage DAY),
(1027, 16, 'achat', 'Commande n° 1078', 'commande:1078', 'valide', '2026-04-03 15:36:27' + INTERVAL @decalage DAY),
(1027, 10, 'achat', 'Commande n° 1079', 'commande:1079', 'valide', '2026-04-21 09:32:43' + INTERVAL @decalage DAY),
(1027, -100, 'depense', 'Bon de 5,00 € (commande n° 1080)', 'commande:1080', 'valide', '2026-04-27 22:32:55' + INTERVAL @decalage DAY),
(1027, 23, 'achat', 'Commande n° 1080', 'commande:1080', 'valide', '2026-04-27 22:32:55' + INTERVAL @decalage DAY),
(1027, 15, 'badge', 'Badge « Lecteur fidèle » argent', 'lecteur_fidele:2', 'valide', '2026-04-27 22:32:55' + INTERVAL @decalage DAY),
(1027, 5, 'avis', 'Avis sur « Hitchcock / Truffaut - Édition définitive »', 'livre:1041', 'valide', '2026-05-09 06:32:55' + INTERVAL @decalage DAY),
(1027, 10, 'premier_avis', 'Bonus premier avis validé', 'premier', 'valide', '2026-05-10 10:32:55' + INTERVAL @decalage DAY),
(1027, 5, 'badge', 'Badge « Plume critique » bronze', 'plume_critique:1', 'valide', '2026-05-10 10:32:55' + INTERVAL @decalage DAY),
(1027, 34, 'achat', 'Commande n° 1081', 'commande:1081', 'valide', '2026-07-07 15:15:36' + INTERVAL @decalage DAY),
(1027, 5, 'avis', 'Avis sur « Jujutsu Kaisen - Tome 30 »', 'livre:11', 'valide', '2026-07-15 18:15:36' + INTERVAL @decalage DAY),
(1027, 4, 'achat', 'Commande n° 1082', 'commande:1082', 'valide', '2026-08-12 10:27:39' + INTERVAL @decalage DAY),
(1027, 9, 'achat', 'Commande n° 1083', 'commande:1083', 'valide', '2026-08-21 13:30:10' + INTERVAL @decalage DAY),
(1027, 21, 'achat', 'Commande n° 1084', 'commande:1084', 'valide', '2026-08-30 20:37:46' + INTERVAL @decalage DAY),
(1027, 21, 'achat', 'Commande n° 1085', 'commande:1085', 'valide', '2026-10-04 08:23:12' + INTERVAL @decalage DAY),
(1027, 40, 'achat', 'Commande n° 1086', 'commande:1086', 'valide', '2026-10-12 13:56:30' + INTERVAL @decalage DAY),
(1027, 73, 'achat', 'Commande n° 1087', 'commande:1087', 'valide', '2026-10-23 15:08:46' + INTERVAL @decalage DAY),
(1028, 27, 'achat', 'Commande n° 1088', 'commande:1088', 'valide', '2026-03-13 09:07:41' + INTERVAL @decalage DAY),
(1028, 20, 'premier_achat', 'Bonus premier achat', 'commande:1088', 'valide', '2026-03-13 09:07:41' + INTERVAL @decalage DAY),
(1028, 5, 'badge', 'Badge « Lecteur fidèle » bronze', 'lecteur_fidele:1', 'valide', '2026-03-13 09:07:41' + INTERVAL @decalage DAY),
(1028, 25, 'achat', 'Commande n° 1089', 'commande:1089', 'valide', '2026-04-03 20:05:41' + INTERVAL @decalage DAY),
(1028, 7, 'achat', 'Commande n° 1090', 'commande:1090', 'valide', '2026-04-03 22:50:15' + INTERVAL @decalage DAY),
(1028, 2, 'achat', 'Commande n° 1091', 'commande:1091', 'valide', '2026-04-19 22:10:08' + INTERVAL @decalage DAY),
(1028, 5, 'achat', 'Commande n° 1092', 'commande:1092', 'valide', '2026-05-09 13:02:22' + INTERVAL @decalage DAY),
(1028, 28, 'achat', 'Commande n° 1093', 'commande:1093', 'valide', '2026-05-31 08:06:06' + INTERVAL @decalage DAY),
(1028, 15, 'badge', 'Badge « Lecteur fidèle » argent', 'lecteur_fidele:2', 'valide', '2026-05-31 08:06:06' + INTERVAL @decalage DAY),
(1028, 5, 'achat', 'Commande n° 1094', 'commande:1094', 'valide', '2026-06-01 18:29:00' + INTERVAL @decalage DAY),
(1028, 4, 'achat', 'Commande n° 1095', 'commande:1095', 'valide', '2026-06-13 11:52:13' + INTERVAL @decalage DAY),
(1028, 6, 'achat', 'Commande n° 1096', 'commande:1096', 'valide', '2026-06-16 09:33:47' + INTERVAL @decalage DAY),
(1028, 15, 'achat', 'Commande n° 1097', 'commande:1097', 'valide', '2026-06-22 12:16:41' + INTERVAL @decalage DAY),
(1028, 16, 'achat', 'Commande n° 1098', 'commande:1098', 'valide', '2026-07-18 09:52:21' + INTERVAL @decalage DAY),
(1028, 10, 'achat', 'Commande n° 1099', 'commande:1099', 'valide', '2026-08-16 22:00:39' + INTERVAL @decalage DAY),
(1028, 5, 'avis', 'Avis sur « L''Anomalie »', 'livre:1021', 'valide', '2026-08-24 17:52:21' + INTERVAL @decalage DAY),
(1028, 10, 'premier_avis', 'Bonus premier avis validé', 'premier', 'valide', '2026-08-25 21:52:21' + INTERVAL @decalage DAY),
(1028, 5, 'badge', 'Badge « Plume critique » bronze', 'plume_critique:1', 'valide', '2026-08-25 21:52:21' + INTERVAL @decalage DAY),
(1028, -200, 'depense', 'Bon de 10,50 € (commande n° 1100)', 'commande:1100', 'valide', '2026-09-01 22:27:57' + INTERVAL @decalage DAY),
(1028, 30, 'achat', 'Commande n° 1100', 'commande:1100', 'valide', '2026-09-01 22:27:57' + INTERVAL @decalage DAY),
(1028, 20, 'don', 'Don récupéré : « Les Rivières pourpres »', 'don:1001', 'valide', '2026-09-26 17:12:03' + INTERVAL @decalage DAY),
(1028, 5, 'badge', 'Badge « Cœur généreux » bronze', 'coeur_genereux:1', 'valide', '2026-09-26 17:12:03' + INTERVAL @decalage DAY),
(1028, 45, 'achat', 'Commande n° 1101', 'commande:1101', 'valide', '2026-10-09 22:34:29' + INTERVAL @decalage DAY),
(1028, 30, 'badge', 'Badge « Explorateur »', 'explorateur:1', 'valide', '2026-10-09 22:34:29' + INTERVAL @decalage DAY),
(1029, 2, 'achat', 'Commande n° 1102', 'commande:1102', 'valide', '2025-11-05 20:47:52' + INTERVAL @decalage DAY),
(1029, 20, 'premier_achat', 'Bonus premier achat', 'commande:1102', 'valide', '2025-11-05 20:47:52' + INTERVAL @decalage DAY),
(1029, 5, 'badge', 'Badge « Lecteur fidèle » bronze', 'lecteur_fidele:1', 'valide', '2025-11-05 20:47:52' + INTERVAL @decalage DAY),
(1029, 9, 'achat', 'Commande n° 1103', 'commande:1103', 'valide', '2025-11-11 17:16:45' + INTERVAL @decalage DAY),
(1029, 18, 'achat', 'Commande n° 1104', 'commande:1104', 'valide', '2025-11-17 13:58:37' + INTERVAL @decalage DAY),
(1029, 5, 'achat', 'Commande n° 1105', 'commande:1105', 'valide', '2025-12-10 12:29:58' + INTERVAL @decalage DAY),
(1029, 5, 'achat', 'Commande n° 1106', 'commande:1106', 'valide', '2025-12-21 21:49:58' + INTERVAL @decalage DAY),
(1029, 6, 'achat', 'Commande n° 1107', 'commande:1107', 'valide', '2026-02-01 21:49:39' + INTERVAL @decalage DAY),
(1029, 5, 'avis', 'Avis sur « Cyrano de Bergerac »', 'livre:1042', 'valide', '2026-02-10 01:49:39' + INTERVAL @decalage DAY),
(1029, 10, 'premier_avis', 'Bonus premier avis validé', 'premier', 'valide', '2026-02-11 03:49:39' + INTERVAL @decalage DAY),
(1029, 5, 'badge', 'Badge « Plume critique » bronze', 'plume_critique:1', 'valide', '2026-02-11 03:49:39' + INTERVAL @decalage DAY),
(1029, 11, 'achat', 'Commande n° 1108', 'commande:1108', 'valide', '2026-02-28 11:53:38' + INTERVAL @decalage DAY),
(1029, -100, 'depense', 'Bon de 5,00 € (commande n° 1109)', 'commande:1109', 'valide', '2026-03-15 21:41:25' + INTERVAL @decalage DAY),
(1029, 13, 'achat', 'Commande n° 1109', 'commande:1109', 'valide', '2026-03-15 21:41:25' + INTERVAL @decalage DAY),
(1029, 15, 'badge', 'Badge « Lecteur fidèle » argent', 'lecteur_fidele:2', 'valide', '2026-03-15 21:41:25' + INTERVAL @decalage DAY),
(1029, 15, 'achat', 'Commande n° 1110', 'commande:1110', 'valide', '2026-03-21 20:47:01' + INTERVAL @decalage DAY),
(1029, 3, 'achat', 'Commande n° 1111', 'commande:1111', 'valide', '2026-04-15 13:35:06' + INTERVAL @decalage DAY),
(1029, 21, 'achat', 'Commande n° 1112', 'commande:1112', 'valide', '2026-06-12 20:53:26' + INTERVAL @decalage DAY),
(1029, 26, 'achat', 'Commande n° 1113', 'commande:1113', 'valide', '2026-06-15 11:06:51' + INTERVAL @decalage DAY),
(1029, 30, 'badge', 'Badge « Explorateur »', 'explorateur:1', 'valide', '2026-06-15 11:06:51' + INTERVAL @decalage DAY),
(1029, 16, 'achat', 'Commande n° 1114', 'commande:1114', 'valide', '2026-06-30 13:49:39' + INTERVAL @decalage DAY),
(1029, 6, 'achat', 'Commande n° 1115', 'commande:1115', 'valide', '2026-07-06 08:14:53' + INTERVAL @decalage DAY),
(1029, 11, 'achat', 'Commande n° 1116', 'commande:1116', 'valide', '2026-09-01 09:08:00' + INTERVAL @decalage DAY),
(1029, 22, 'achat', 'Commande n° 1117', 'commande:1117', 'valide', '2026-09-04 19:12:29' + INTERVAL @decalage DAY),
(1029, 10, 'achat', 'Commande n° 1118', 'commande:1118', 'valide', '2026-09-15 10:07:20' + INTERVAL @decalage DAY),
(1029, 7, 'achat', 'Commande n° 1119', 'commande:1119', 'valide', '2026-09-17 18:53:55' + INTERVAL @decalage DAY),
(1029, 10, 'presence', 'Présence à « Troc de livres de la rentrée »', 'inscription:1078', 'valide', '2026-09-19 10:00:00' + INTERVAL @decalage DAY),
(1029, 5, 'badge', 'Badge « Habitué des rencontres » bronze', 'habitue_rencontres:1', 'valide', '2026-09-19 10:00:00' + INTERVAL @decalage DAY),
(1029, 12, 'achat', 'Commande n° 1120', 'commande:1120', 'valide', '2026-09-22 09:50:34' + INTERVAL @decalage DAY),
(1029, 5, 'avis', 'Avis sur « Hunger Games - Tome 1 »', 'livre:1029', 'valide', '2026-09-27 15:50:34' + INTERVAL @decalage DAY),
(1030, 11, 'achat', 'Commande n° 1121', 'commande:1121', 'valide', '2026-06-05 18:22:09' + INTERVAL @decalage DAY),
(1030, 20, 'premier_achat', 'Bonus premier achat', 'commande:1121', 'valide', '2026-06-05 18:22:09' + INTERVAL @decalage DAY),
(1030, 5, 'badge', 'Badge « Lecteur fidèle » bronze', 'lecteur_fidele:1', 'valide', '2026-06-05 18:22:09' + INTERVAL @decalage DAY),
(1030, 36, 'achat', 'Commande n° 1122', 'commande:1122', 'valide', '2026-06-25 08:58:38' + INTERVAL @decalage DAY),
(1030, 27, 'achat', 'Commande n° 1123', 'commande:1123', 'valide', '2026-08-02 10:55:22' + INTERVAL @decalage DAY),
(1030, 7, 'achat', 'Commande n° 1124', 'commande:1124', 'valide', '2026-08-16 17:54:56' + INTERVAL @decalage DAY),
(1030, -100, 'depense', 'Bon de 5,00 € (commande n° 1125)', 'commande:1125', 'valide', '2026-08-17 09:39:05' + INTERVAL @decalage DAY),
(1030, 10, 'achat', 'Commande n° 1125', 'commande:1125', 'valide', '2026-08-17 09:39:05' + INTERVAL @decalage DAY),
(1030, 29, 'achat', 'Commande n° 1126', 'commande:1126', 'valide', '2026-08-18 08:27:10' + INTERVAL @decalage DAY),
(1030, 15, 'badge', 'Badge « Lecteur fidèle » argent', 'lecteur_fidele:2', 'valide', '2026-08-18 08:27:10' + INTERVAL @decalage DAY),
(1030, 17, 'achat', 'Commande n° 1127', 'commande:1127', 'valide', '2026-08-24 18:30:10' + INTERVAL @decalage DAY),
(1030, 35, 'achat', 'Commande n° 1128', 'commande:1128', 'valide', '2026-08-29 17:54:26' + INTERVAL @decalage DAY),
(1030, 5, 'avis', 'Avis sur « Coder proprement »', 'livre:1027', 'valide', '2026-08-31 10:55:22' + INTERVAL @decalage DAY),
(1030, 10, 'premier_avis', 'Bonus premier avis validé', 'premier', 'valide', '2026-09-01 12:55:22' + INTERVAL @decalage DAY),
(1030, 5, 'badge', 'Badge « Plume critique » bronze', 'plume_critique:1', 'valide', '2026-09-01 12:55:22' + INTERVAL @decalage DAY),
(1030, 10, 'achat', 'Commande n° 1129', 'commande:1129', 'valide', '2026-09-07 08:04:28' + INTERVAL @decalage DAY),
(1030, 10, 'presence', 'Présence à « Troc de livres de la rentrée »', 'inscription:1076', 'valide', '2026-09-19 11:00:00' + INTERVAL @decalage DAY),
(1030, 5, 'badge', 'Badge « Habitué des rencontres » bronze', 'habitue_rencontres:1', 'valide', '2026-09-19 11:00:00' + INTERVAL @decalage DAY),
(1030, 5, 'avis', 'Avis sur « La Passe-miroir - Livre 1 : Les Fiancés de l''hiver »', 'livre:1001', 'valide', '2026-09-18 21:30:10' + INTERVAL @decalage DAY),
(1030, 5, 'avis', 'Avis sur « Watchmen »', 'livre:1023', 'valide', '2026-09-20 18:39:05' + INTERVAL @decalage DAY),
(1030, 32, 'achat', 'Commande n° 1130', 'commande:1130', 'valide', '2026-10-20 19:50:37' + INTERVAL @decalage DAY),
(1031, 13, 'achat', 'Commande n° 1131', 'commande:1131', 'valide', '2025-07-23 09:30:29' + INTERVAL @decalage DAY),
(1031, 20, 'premier_achat', 'Bonus premier achat', 'commande:1131', 'valide', '2025-07-23 09:30:29' + INTERVAL @decalage DAY),
(1031, 5, 'badge', 'Badge « Lecteur fidèle » bronze', 'lecteur_fidele:1', 'valide', '2025-07-23 09:30:29' + INTERVAL @decalage DAY),
(1031, 30, 'achat', 'Commande n° 1132', 'commande:1132', 'valide', '2025-08-03 08:45:45' + INTERVAL @decalage DAY),
(1031, 22, 'achat', 'Commande n° 1133', 'commande:1133', 'valide', '2025-08-24 15:52:17' + INTERVAL @decalage DAY),
(1031, 13, 'achat', 'Commande n° 1134', 'commande:1134', 'valide', '2025-10-11 12:17:55' + INTERVAL @decalage DAY),
(1031, 4, 'achat', 'Commande n° 1135', 'commande:1135', 'valide', '2025-11-06 22:01:46' + INTERVAL @decalage DAY),
(1031, 35, 'achat', 'Commande n° 1136', 'commande:1136', 'valide', '2025-11-23 11:39:37' + INTERVAL @decalage DAY),
(1031, 15, 'badge', 'Badge « Lecteur fidèle » argent', 'lecteur_fidele:2', 'valide', '2025-11-23 11:39:37' + INTERVAL @decalage DAY),
(1031, 21, 'achat', 'Commande n° 1137', 'commande:1137', 'valide', '2026-03-15 12:13:33' + INTERVAL @decalage DAY),
(1031, 17, 'achat', 'Commande n° 1138', 'commande:1138', 'valide', '2026-06-24 18:35:16' + INTERVAL @decalage DAY),
(1031, 30, 'badge', 'Badge « Un an parmi nous »', 'un_an:1', 'valide', '2026-06-24 18:35:16' + INTERVAL @decalage DAY),
(1031, 7, 'achat', 'Commande n° 1139', 'commande:1139', 'valide', '2026-06-25 17:29:50' + INTERVAL @decalage DAY),
(1031, 5, 'achat', 'Commande n° 1140', 'commande:1140', 'valide', '2026-07-23 18:39:38' + INTERVAL @decalage DAY),
(1031, 5, 'avis', 'Avis sur « Bleach - Tome 01 »', 'livre:3', 'valide', '2026-07-31 22:29:50' + INTERVAL @decalage DAY),
(1031, 10, 'premier_avis', 'Bonus premier avis validé', 'premier', 'valide', '2026-08-02 03:29:50' + INTERVAL @decalage DAY),
(1031, 5, 'badge', 'Badge « Plume critique » bronze', 'plume_critique:1', 'valide', '2026-08-02 03:29:50' + INTERVAL @decalage DAY),
(1031, 21, 'achat', 'Commande n° 1141', 'commande:1141', 'valide', '2026-09-04 17:49:00' + INTERVAL @decalage DAY),
(1031, 31, 'achat', 'Commande n° 1142', 'commande:1142', 'valide', '2026-09-28 10:32:17' + INTERVAL @decalage DAY),
(1031, -200, 'depense', 'Bon de 10,50 € (commande n° 1143)', 'commande:1143', 'valide', '2026-09-29 22:43:06' + INTERVAL @decalage DAY);

INSERT IGNORE INTO point_mouvement (id_user, points, type, libelle, reference, statut, date_mouvement) VALUES
(1031, 16, 'achat', 'Commande n° 1143', 'commande:1143', 'valide', '2026-09-29 22:43:06' + INTERVAL @decalage DAY),
(1031, 29, 'achat', 'Commande n° 1144', 'commande:1144', 'valide', '2026-10-16 17:41:45' + INTERVAL @decalage DAY),
(1032, 5, 'achat', 'Commande n° 1145', 'commande:1145', 'valide', '2026-05-26 16:45:00' + INTERVAL @decalage DAY),
(1032, 20, 'premier_achat', 'Bonus premier achat', 'commande:1145', 'valide', '2026-05-26 16:45:00' + INTERVAL @decalage DAY),
(1032, 5, 'badge', 'Badge « Lecteur fidèle » bronze', 'lecteur_fidele:1', 'valide', '2026-05-26 16:45:00' + INTERVAL @decalage DAY),
(1032, 37, 'achat', 'Commande n° 1146', 'commande:1146', 'valide', '2026-05-27 16:12:44' + INTERVAL @decalage DAY),
(1032, 5, 'avis', 'Avis sur « Hitchcock / Truffaut - Édition définitive »', 'livre:1041', 'refuse', '2026-06-07 19:12:44' + INTERVAL @decalage DAY),
(1032, 46, 'achat', 'Commande n° 1147', 'commande:1147', 'valide', '2026-06-09 09:25:19' + INTERVAL @decalage DAY),
(1032, 12, 'achat', 'Commande n° 1148', 'commande:1148', 'valide', '2026-06-13 12:47:14' + INTERVAL @decalage DAY),
(1032, 15, 'badge', 'Badge « Lecteur fidèle » argent', 'lecteur_fidele:2', 'valide', '2026-06-13 12:47:14' + INTERVAL @decalage DAY),
(1032, 8, 'achat', 'Commande n° 1149', 'commande:1149', 'valide', '2026-07-16 21:46:56' + INTERVAL @decalage DAY),
(1032, 22, 'achat', 'Commande n° 1150', 'commande:1150', 'valide', '2026-09-05 17:52:27' + INTERVAL @decalage DAY),
(1032, -100, 'depense', 'Bon de 5,00 € (commande n° 1151)', 'commande:1151', 'valide', '2026-09-17 13:00:34' + INTERVAL @decalage DAY),
(1032, 14, 'achat', 'Commande n° 1151', 'commande:1151', 'valide', '2026-09-17 13:00:34' + INTERVAL @decalage DAY),
(1032, 10, 'presence', 'Présence à « Troc de livres de la rentrée »', 'inscription:1068', 'valide', '2026-09-19 11:00:00' + INTERVAL @decalage DAY),
(1032, 5, 'badge', 'Badge « Habitué des rencontres » bronze', 'habitue_rencontres:1', 'valide', '2026-09-19 11:00:00' + INTERVAL @decalage DAY),
(1032, 28, 'achat', 'Commande n° 1152', 'commande:1152', 'valide', '2026-09-20 13:39:32' + INTERVAL @decalage DAY),
(1032, 4, 'achat', 'Commande n° 1153', 'commande:1153', 'valide', '2026-09-22 20:01:17' + INTERVAL @decalage DAY),
(1032, -100, 'depense', 'Bon de 5,00 € (commande n° 1154)', 'commande:1154', 'valide', '2026-09-25 17:36:19' + INTERVAL @decalage DAY),
(1032, 60, 'achat', 'Commande n° 1154', 'commande:1154', 'valide', '2026-09-25 17:36:19' + INTERVAL @decalage DAY),
(1032, 5, 'avis', 'Avis sur « Max et les Maximonstres »', 'livre:1032', 'valide', '2026-10-06 13:00:34' + INTERVAL @decalage DAY),
(1032, 10, 'premier_avis', 'Bonus premier avis validé', 'premier', 'valide', '2026-10-07 16:00:34' + INTERVAL @decalage DAY),
(1032, 5, 'badge', 'Badge « Plume critique » bronze', 'plume_critique:1', 'valide', '2026-10-07 16:00:34' + INTERVAL @decalage DAY),
(1032, 8, 'achat', 'Commande n° 1155', 'commande:1155', 'valide', '2026-10-13 10:39:42' + INTERVAL @decalage DAY),
(1033, 10, 'achat', 'Commande n° 1156', 'commande:1156', 'valide', '2025-10-17 15:04:41' + INTERVAL @decalage DAY),
(1033, 20, 'premier_achat', 'Bonus premier achat', 'commande:1156', 'valide', '2025-10-17 15:04:41' + INTERVAL @decalage DAY),
(1033, 5, 'badge', 'Badge « Lecteur fidèle » bronze', 'lecteur_fidele:1', 'valide', '2025-10-17 15:04:41' + INTERVAL @decalage DAY),
(1033, 7, 'achat', 'Commande n° 1157', 'commande:1157', 'valide', '2025-11-06 21:08:41' + INTERVAL @decalage DAY),
(1033, 29, 'achat', 'Commande n° 1158', 'commande:1158', 'valide', '2025-12-02 11:49:46' + INTERVAL @decalage DAY),
(1033, 27, 'achat', 'Commande n° 1159', 'commande:1159', 'valide', '2025-12-23 20:17:37' + INTERVAL @decalage DAY),
(1033, 16, 'achat', 'Commande n° 1160', 'commande:1160', 'valide', '2026-01-20 18:34:31' + INTERVAL @decalage DAY),
(1033, 15, 'badge', 'Badge « Lecteur fidèle » argent', 'lecteur_fidele:2', 'valide', '2026-01-20 18:34:31' + INTERVAL @decalage DAY),
(1033, 15, 'achat', 'Commande n° 1161', 'commande:1161', 'valide', '2026-01-29 21:45:23' + INTERVAL @decalage DAY),
(1033, 15, 'achat', 'Commande n° 1162', 'commande:1162', 'valide', '2026-02-28 10:29:00' + INTERVAL @decalage DAY),
(1033, 14, 'achat', 'Commande n° 1163', 'commande:1163', 'valide', '2026-03-14 08:49:00' + INTERVAL @decalage DAY),
(1033, -100, 'depense', 'Bon de 5,00 € (commande n° 1164)', 'commande:1164', 'valide', '2026-05-04 15:19:56' + INTERVAL @decalage DAY),
(1033, 24, 'achat', 'Commande n° 1164', 'commande:1164', 'valide', '2026-05-04 15:19:56' + INTERVAL @decalage DAY),
(1033, 2, 'achat', 'Commande n° 1165', 'commande:1165', 'valide', '2026-05-22 10:44:39' + INTERVAL @decalage DAY),
(1033, 25, 'achat', 'Commande n° 1166', 'commande:1166', 'valide', '2026-06-08 21:56:03' + INTERVAL @decalage DAY),
(1033, 11, 'achat', 'Commande n° 1167', 'commande:1167', 'valide', '2026-06-11 13:44:40' + INTERVAL @decalage DAY),
(1033, 6, 'achat', 'Commande n° 1168', 'commande:1168', 'valide', '2026-06-13 11:15:49' + INTERVAL @decalage DAY),
(1033, -100, 'depense', 'Bon de 5,00 € (commande n° 1169)', 'commande:1169', 'valide', '2026-06-29 20:05:10' + INTERVAL @decalage DAY),
(1033, 16, 'achat', 'Commande n° 1169', 'commande:1169', 'valide', '2026-06-29 20:05:10' + INTERVAL @decalage DAY),
(1033, 6, 'achat', 'Commande n° 1170', 'commande:1170', 'valide', '2026-07-10 10:06:49' + INTERVAL @decalage DAY),
(1033, 22, 'achat', 'Commande n° 1171', 'commande:1171', 'valide', '2026-08-02 13:23:50' + INTERVAL @decalage DAY),
(1033, 2, 'achat', 'Commande n° 1172', 'commande:1172', 'valide', '2026-08-25 20:35:13' + INTERVAL @decalage DAY),
(1033, 10, 'achat', 'Commande n° 1173', 'commande:1173', 'valide', '2026-08-26 21:38:20' + INTERVAL @decalage DAY),
(1033, 10, 'presence', 'Présence à « Troc de livres de la rentrée »', 'inscription:1062', 'valide', '2026-09-19 10:00:00' + INTERVAL @decalage DAY),
(1033, 5, 'badge', 'Badge « Habitué des rencontres » bronze', 'habitue_rencontres:1', 'valide', '2026-09-19 10:00:00' + INTERVAL @decalage DAY),
(1033, 30, 'badge', 'Badge « Un an parmi nous »', 'un_an:1', 'valide', '2026-09-19 10:00:00' + INTERVAL @decalage DAY),
(1033, 19, 'achat', 'Commande n° 1174', 'commande:1174', 'valide', '2026-09-22 08:08:27' + INTERVAL @decalage DAY),
(1033, -100, 'depense', 'Bon de 5,00 € (commande n° 1175)', 'commande:1175', 'valide', '2026-09-26 12:29:19' + INTERVAL @decalage DAY),
(1033, 27, 'achat', 'Commande n° 1175', 'commande:1175', 'valide', '2026-09-26 12:29:19' + INTERVAL @decalage DAY),
(1033, 30, 'badge', 'Badge « Collectionneur »', 'collectionneur:1', 'valide', '2026-09-26 12:29:19' + INTERVAL @decalage DAY),
(1033, 5, 'avis', 'Avis sur « Blacksad - Tome 1 : Quelque part entre les ombres »', 'livre:1028', 'valide', '2026-10-04 22:16:35' + INTERVAL @decalage DAY),
(1033, 10, 'premier_avis', 'Bonus premier avis validé', 'premier', 'valide', '2026-10-05 23:16:35' + INTERVAL @decalage DAY),
(1033, 5, 'badge', 'Badge « Plume critique » bronze', 'plume_critique:1', 'valide', '2026-10-05 23:16:35' + INTERVAL @decalage DAY),
(1034, 34, 'achat', 'Commande n° 1176', 'commande:1176', 'valide', '2026-03-30 11:35:54' + INTERVAL @decalage DAY),
(1034, 20, 'premier_achat', 'Bonus premier achat', 'commande:1176', 'valide', '2026-03-30 11:35:54' + INTERVAL @decalage DAY),
(1034, 5, 'badge', 'Badge « Lecteur fidèle » bronze', 'lecteur_fidele:1', 'valide', '2026-03-30 11:35:54' + INTERVAL @decalage DAY),
(1034, 10, 'achat', 'Commande n° 1177', 'commande:1177', 'valide', '2026-03-31 10:41:57' + INTERVAL @decalage DAY),
(1034, 29, 'achat', 'Commande n° 1178', 'commande:1178', 'valide', '2026-04-10 09:36:17' + INTERVAL @decalage DAY),
(1034, 25, 'achat', 'Commande n° 1179', 'commande:1179', 'valide', '2026-04-13 16:32:21' + INTERVAL @decalage DAY),
(1034, 15, 'badge', 'Badge « Lecteur fidèle » argent', 'lecteur_fidele:2', 'valide', '2026-04-13 16:32:21' + INTERVAL @decalage DAY),
(1034, 5, 'achat', 'Commande n° 1180', 'commande:1180', 'valide', '2026-04-19 13:08:16' + INTERVAL @decalage DAY),
(1034, 3, 'achat', 'Commande n° 1181', 'commande:1181', 'valide', '2026-05-28 19:57:34' + INTERVAL @decalage DAY),
(1034, -100, 'depense', 'Bon de 5,00 € (commande n° 1182)', 'commande:1182', 'valide', '2026-06-03 20:32:04' + INTERVAL @decalage DAY),
(1034, 22, 'achat', 'Commande n° 1182', 'commande:1182', 'valide', '2026-06-03 20:32:04' + INTERVAL @decalage DAY),
(1034, 30, 'badge', 'Badge « Explorateur »', 'explorateur:1', 'valide', '2026-06-03 20:32:04' + INTERVAL @decalage DAY),
(1034, 27, 'achat', 'Commande n° 1183', 'commande:1183', 'valide', '2026-06-12 16:37:29' + INTERVAL @decalage DAY),
(1034, 14, 'achat', 'Commande n° 1184', 'commande:1184', 'valide', '2026-06-12 21:34:49' + INTERVAL @decalage DAY),
(1034, 18, 'achat', 'Commande n° 1185', 'commande:1185', 'valide', '2026-07-14 22:06:16' + INTERVAL @decalage DAY),
(1034, -100, 'depense', 'Bon de 5,00 € (commande n° 1186)', 'commande:1186', 'valide', '2026-07-23 13:23:57' + INTERVAL @decalage DAY),
(1034, 40, 'achat', 'Commande n° 1186', 'commande:1186', 'valide', '2026-07-23 13:23:57' + INTERVAL @decalage DAY),
(1034, 5, 'avis', 'Avis sur « L''Étranger »', 'livre:4', 'valide', '2026-08-04 13:23:57' + INTERVAL @decalage DAY),
(1034, 10, 'premier_avis', 'Bonus premier avis validé', 'premier', 'valide', '2026-08-05 17:23:57' + INTERVAL @decalage DAY),
(1034, 5, 'badge', 'Badge « Plume critique » bronze', 'plume_critique:1', 'valide', '2026-08-05 17:23:57' + INTERVAL @decalage DAY),
(1034, 9, 'achat', 'Commande n° 1187', 'commande:1187', 'valide', '2026-08-23 08:51:32' + INTERVAL @decalage DAY),
(1034, 30, 'badge', 'Badge « Collectionneur »', 'collectionneur:1', 'valide', '2026-08-23 08:51:32' + INTERVAL @decalage DAY),
(1034, 10, 'presence', 'Présence à « Troc de livres de la rentrée »', 'inscription:1070', 'valide', '2026-09-19 11:00:00' + INTERVAL @decalage DAY),
(1034, 5, 'badge', 'Badge « Habitué des rencontres » bronze', 'habitue_rencontres:1', 'valide', '2026-09-19 11:00:00' + INTERVAL @decalage DAY),
(1034, 38, 'achat', 'Commande n° 1188', 'commande:1188', 'valide', '2026-10-23 10:52:15' + INTERVAL @decalage DAY),
(1035, 9, 'achat', 'Commande n° 1189', 'commande:1189', 'valide', '2026-02-25 15:57:44' + INTERVAL @decalage DAY),
(1035, 20, 'premier_achat', 'Bonus premier achat', 'commande:1189', 'valide', '2026-02-25 15:57:44' + INTERVAL @decalage DAY),
(1035, 5, 'badge', 'Badge « Lecteur fidèle » bronze', 'lecteur_fidele:1', 'valide', '2026-02-25 15:57:44' + INTERVAL @decalage DAY),
(1035, 4, 'achat', 'Commande n° 1190', 'commande:1190', 'valide', '2026-03-03 12:31:15' + INTERVAL @decalage DAY),
(1035, 13, 'achat', 'Commande n° 1191', 'commande:1191', 'valide', '2026-04-26 10:48:36' + INTERVAL @decalage DAY),
(1035, 21, 'achat', 'Commande n° 1192', 'commande:1192', 'valide', '2026-05-10 08:54:42' + INTERVAL @decalage DAY),
(1035, 6, 'achat', 'Commande n° 1193', 'commande:1193', 'valide', '2026-05-10 11:17:00' + INTERVAL @decalage DAY),
(1035, 20, 'achat', 'Commande n° 1194', 'commande:1194', 'valide', '2026-05-14 09:11:50' + INTERVAL @decalage DAY),
(1035, 15, 'badge', 'Badge « Lecteur fidèle » argent', 'lecteur_fidele:2', 'valide', '2026-05-14 09:11:50' + INTERVAL @decalage DAY),
(1035, -100, 'depense', 'Bon de 5,00 € (commande n° 1195)', 'commande:1195', 'valide', '2026-05-15 11:51:08' + INTERVAL @decalage DAY),
(1035, 10, 'achat', 'Commande n° 1195', 'commande:1195', 'valide', '2026-05-15 11:51:08' + INTERVAL @decalage DAY),
(1035, 5, 'avis', 'Avis sur « Dune - Tome 1 »', 'livre:1033', 'valide', '2026-05-19 18:17:00' + INTERVAL @decalage DAY),
(1035, 10, 'premier_avis', 'Bonus premier avis validé', 'premier', 'valide', '2026-05-20 20:17:00' + INTERVAL @decalage DAY),
(1035, 5, 'badge', 'Badge « Plume critique » bronze', 'plume_critique:1', 'valide', '2026-05-20 20:17:00' + INTERVAL @decalage DAY),
(1035, 5, 'avis', 'Avis sur « Max et les Maximonstres »', 'livre:1032', 'valide', '2026-06-23 14:11:50' + INTERVAL @decalage DAY),
(1035, 29, 'achat', 'Commande n° 1196', 'commande:1196', 'valide', '2026-07-12 14:01:26' + INTERVAL @decalage DAY),
(1035, 12, 'achat', 'Commande n° 1197', 'commande:1197', 'valide', '2026-07-17 11:07:24' + INTERVAL @decalage DAY),
(1035, 11, 'achat', 'Commande n° 1198', 'commande:1198', 'valide', '2026-07-25 11:03:13' + INTERVAL @decalage DAY),
(1035, 4, 'achat', 'Commande n° 1199', 'commande:1199', 'valide', '2026-08-02 14:27:43' + INTERVAL @decalage DAY),
(1035, 8, 'achat', 'Commande n° 1200', 'commande:1200', 'valide', '2026-08-12 13:35:06' + INTERVAL @decalage DAY),
(1035, -100, 'depense', 'Bon de 5,00 € (commande n° 1201)', 'commande:1201', 'valide', '2026-09-13 22:04:16' + INTERVAL @decalage DAY),
(1035, 11, 'achat', 'Commande n° 1201', 'commande:1201', 'valide', '2026-09-13 22:04:16' + INTERVAL @decalage DAY),
(1035, 10, 'presence', 'Présence à « Troc de livres de la rentrée »', 'inscription:1067', 'valide', '2026-09-19 12:00:00' + INTERVAL @decalage DAY),
(1035, 5, 'badge', 'Badge « Habitué des rencontres » bronze', 'habitue_rencontres:1', 'valide', '2026-09-19 12:00:00' + INTERVAL @decalage DAY),
(1035, 8, 'achat', 'Commande n° 1202', 'commande:1202', 'valide', '2026-09-21 12:41:01' + INTERVAL @decalage DAY),
(1035, 33, 'achat', 'Commande n° 1203', 'commande:1203', 'valide', '2026-09-21 21:16:54' + INTERVAL @decalage DAY),
(1035, 27, 'achat', 'Commande n° 1204', 'commande:1204', 'valide', '2026-10-04 10:31:55' + INTERVAL @decalage DAY),
(1035, 17, 'achat', 'Commande n° 1205', 'commande:1205', 'valide', '2026-10-16 20:00:18' + INTERVAL @decalage DAY),
(1035, 30, 'badge', 'Badge « Collectionneur »', 'collectionneur:1', 'valide', '2026-10-16 20:00:18' + INTERVAL @decalage DAY),
(1035, 32, 'achat', 'Commande n° 1206', 'commande:1206', 'valide', '2026-10-22 14:28:35' + INTERVAL @decalage DAY),
(1036, 10, 'achat', 'Commande n° 1207', 'commande:1207', 'valide', '2025-08-11 19:40:09' + INTERVAL @decalage DAY),
(1036, 20, 'premier_achat', 'Bonus premier achat', 'commande:1207', 'valide', '2025-08-11 19:40:09' + INTERVAL @decalage DAY),
(1036, 5, 'badge', 'Badge « Lecteur fidèle » bronze', 'lecteur_fidele:1', 'valide', '2025-08-11 19:40:09' + INTERVAL @decalage DAY),
(1036, 29, 'achat', 'Commande n° 1208', 'commande:1208', 'valide', '2026-01-25 13:43:58' + INTERVAL @decalage DAY),
(1036, 11, 'achat', 'Commande n° 1209', 'commande:1209', 'valide', '2026-02-08 12:44:58' + INTERVAL @decalage DAY),
(1036, 15, 'achat', 'Commande n° 1210', 'commande:1210', 'valide', '2026-03-27 17:20:14' + INTERVAL @decalage DAY),
(1036, 15, 'badge', 'Badge « Lecteur fidèle » argent', 'lecteur_fidele:2', 'valide', '2026-03-27 17:20:14' + INTERVAL @decalage DAY),
(1036, 11, 'achat', 'Commande n° 1211', 'commande:1211', 'valide', '2026-04-16 11:44:18' + INTERVAL @decalage DAY),
(1036, -100, 'depense', 'Bon de 5,00 € (commande n° 1212)', 'commande:1212', 'valide', '2026-05-24 08:40:10' + INTERVAL @decalage DAY),
(1036, 19, 'achat', 'Commande n° 1212', 'commande:1212', 'valide', '2026-05-24 08:40:10' + INTERVAL @decalage DAY),
(1036, 20, 'achat', 'Commande n° 1213', 'commande:1213', 'valide', '2026-06-05 09:56:08' + INTERVAL @decalage DAY),
(1036, 5, 'avis', 'Avis sur « Max et les Maximonstres »', 'livre:1032', 'valide', '2026-06-25 17:40:10' + INTERVAL @decalage DAY),
(1036, 10, 'premier_avis', 'Bonus premier avis validé', 'premier', 'valide', '2026-06-27 20:40:10' + INTERVAL @decalage DAY),
(1036, 5, 'badge', 'Badge « Plume critique » bronze', 'plume_critique:1', 'valide', '2026-06-27 20:40:10' + INTERVAL @decalage DAY),
(1036, 30, 'badge', 'Badge « Un an parmi nous »', 'un_an:1', 'valide', '2026-06-27 20:40:10' + INTERVAL @decalage DAY),
(1036, 23, 'achat', 'Commande n° 1214', 'commande:1214', 'valide', '2026-06-30 19:04:25' + INTERVAL @decalage DAY),
(1036, 10, 'achat', 'Commande n° 1215', 'commande:1215', 'valide', '2026-07-14 14:10:11' + INTERVAL @decalage DAY),
(1036, -100, 'depense', 'Bon de 5,00 € (commande n° 1216)', 'commande:1216', 'valide', '2026-09-21 22:07:26' + INTERVAL @decalage DAY),
(1036, 14, 'achat', 'Commande n° 1216', 'commande:1216', 'valide', '2026-09-21 22:07:26' + INTERVAL @decalage DAY),
(1036, 5, 'avis', 'Avis sur « Pixel et Moustache - Tome 1 : Le chat qui voulait voler »', 'livre:1017', 'valide', '2026-10-17 01:07:26' + INTERVAL @decalage DAY),
(1037, 21, 'achat', 'Commande n° 1217', 'commande:1217', 'valide', '2025-07-14 08:01:16' + INTERVAL @decalage DAY),
(1037, 20, 'premier_achat', 'Bonus premier achat', 'commande:1217', 'valide', '2025-07-14 08:01:16' + INTERVAL @decalage DAY),
(1037, 5, 'badge', 'Badge « Lecteur fidèle » bronze', 'lecteur_fidele:1', 'valide', '2025-07-14 08:01:16' + INTERVAL @decalage DAY),
(1037, 5, 'avis', 'Avis sur « Harry Potter et la Coupe de Feu »', 'livre:1', 'valide', '2025-07-31 14:01:16' + INTERVAL @decalage DAY),
(1037, 10, 'premier_avis', 'Bonus premier avis validé', 'premier', 'valide', '2025-08-02 19:01:16' + INTERVAL @decalage DAY),
(1037, 5, 'badge', 'Badge « Plume critique » bronze', 'plume_critique:1', 'valide', '2025-08-02 19:01:16' + INTERVAL @decalage DAY),
(1037, 9, 'achat', 'Commande n° 1218', 'commande:1218', 'valide', '2025-12-24 10:07:16' + INTERVAL @decalage DAY),
(1037, 5, 'achat', 'Commande n° 1219', 'commande:1219', 'valide', '2025-12-25 12:53:31' + INTERVAL @decalage DAY),
(1037, 17, 'achat', 'Commande n° 1220', 'commande:1220', 'valide', '2025-12-25 17:58:36' + INTERVAL @decalage DAY),
(1037, 46, 'achat', 'Commande n° 1221', 'commande:1221', 'valide', '2025-12-28 13:47:33' + INTERVAL @decalage DAY),
(1037, 15, 'badge', 'Badge « Lecteur fidèle » argent', 'lecteur_fidele:2', 'valide', '2025-12-28 13:47:33' + INTERVAL @decalage DAY),
(1037, 5, 'avis', 'Avis sur « Le Petit Nicolas »', 'livre:1047', 'valide', '2026-01-08 17:58:36' + INTERVAL @decalage DAY),
(1037, 4, 'achat', 'Commande n° 1222', 'commande:1222', 'valide', '2026-01-16 11:51:57' + INTERVAL @decalage DAY),
(1037, 8, 'achat', 'Commande n° 1223', 'commande:1223', 'valide', '2026-02-25 19:39:21' + INTERVAL @decalage DAY),
(1037, 9, 'achat', 'Commande n° 1224', 'commande:1224', 'valide', '2026-02-26 18:47:25' + INTERVAL @decalage DAY),
(1037, 10, 'achat', 'Commande n° 1225', 'commande:1225', 'valide', '2026-03-07 21:22:55' + INTERVAL @decalage DAY),
(1037, -100, 'depense', 'Bon de 5,00 € (commande n° 1226)', 'commande:1226', 'valide', '2026-05-12 11:29:45' + INTERVAL @decalage DAY),
(1037, 15, 'achat', 'Commande n° 1226', 'commande:1226', 'valide', '2026-05-12 11:29:45' + INTERVAL @decalage DAY);

INSERT IGNORE INTO point_mouvement (id_user, points, type, libelle, reference, statut, date_mouvement) VALUES
(1037, 11, 'achat', 'Commande n° 1227', 'commande:1227', 'valide', '2026-07-03 13:33:34' + INTERVAL @decalage DAY),
(1037, -100, 'depense', 'Bon de 5,00 € (commande n° 1228)', 'commande:1228', 'valide', '2026-09-07 18:34:05' + INTERVAL @decalage DAY),
(1037, 14, 'achat', 'Commande n° 1228', 'commande:1228', 'valide', '2026-09-07 18:34:05' + INTERVAL @decalage DAY),
(1037, 30, 'badge', 'Badge « Un an parmi nous »', 'un_an:1', 'valide', '2026-09-07 18:34:05' + INTERVAL @decalage DAY),
(1037, 3, 'achat', 'Commande n° 1229', 'commande:1229', 'valide', '2026-09-15 08:13:03' + INTERVAL @decalage DAY),
(1037, 28, 'achat', 'Commande n° 1230', 'commande:1230', 'valide', '2026-09-18 12:10:17' + INTERVAL @decalage DAY),
(1037, 17, 'achat', 'Commande n° 1231', 'commande:1231', 'valide', '2026-10-15 13:07:33' + INTERVAL @decalage DAY),
(1037, 30, 'badge', 'Badge « Collectionneur »', 'collectionneur:1', 'valide', '2026-10-15 13:07:33' + INTERVAL @decalage DAY),
(1038, 7, 'achat', 'Commande n° 1232', 'commande:1232', 'valide', '2026-03-11 16:00:51' + INTERVAL @decalage DAY),
(1038, 20, 'premier_achat', 'Bonus premier achat', 'commande:1232', 'valide', '2026-03-11 16:00:51' + INTERVAL @decalage DAY),
(1038, 5, 'badge', 'Badge « Lecteur fidèle » bronze', 'lecteur_fidele:1', 'valide', '2026-03-11 16:00:51' + INTERVAL @decalage DAY),
(1038, 71, 'achat', 'Commande n° 1233', 'commande:1233', 'valide', '2026-03-29 17:04:18' + INTERVAL @decalage DAY),
(1038, -100, 'depense', 'Bon de 5,00 € (commande n° 1234)', 'commande:1234', 'valide', '2026-04-03 08:58:15' + INTERVAL @decalage DAY),
(1038, 28, 'achat', 'Commande n° 1234', 'commande:1234', 'valide', '2026-04-03 08:58:15' + INTERVAL @decalage DAY),
(1038, 2, 'achat', 'Commande n° 1235', 'commande:1235', 'valide', '2026-04-26 21:11:47' + INTERVAL @decalage DAY),
(1038, 5, 'avis', 'Avis sur « Une brève histoire du temps »', 'livre:1010', 'valide', '2026-05-05 17:04:18' + INTERVAL @decalage DAY),
(1038, 10, 'premier_avis', 'Bonus premier avis validé', 'premier', 'valide', '2026-05-06 18:04:18' + INTERVAL @decalage DAY),
(1038, 5, 'badge', 'Badge « Plume critique » bronze', 'plume_critique:1', 'valide', '2026-05-06 18:04:18' + INTERVAL @decalage DAY),
(1038, 14, 'achat', 'Commande n° 1236', 'commande:1236', 'valide', '2026-05-13 22:39:45' + INTERVAL @decalage DAY),
(1038, 26, 'achat', 'Commande n° 1237', 'commande:1237', 'valide', '2026-05-20 14:27:06' + INTERVAL @decalage DAY),
(1038, 15, 'badge', 'Badge « Lecteur fidèle » argent', 'lecteur_fidele:2', 'valide', '2026-05-20 14:27:06' + INTERVAL @decalage DAY),
(1038, 5, 'avis', 'Avis sur « L''Étranger »', 'livre:4', 'valide', '2026-05-21 02:39:45' + INTERVAL @decalage DAY),
(1038, 14, 'achat', 'Commande n° 1238', 'commande:1238', 'valide', '2026-06-06 19:29:31' + INTERVAL @decalage DAY),
(1038, 11, 'achat', 'Commande n° 1239', 'commande:1239', 'valide', '2026-07-04 10:00:35' + INTERVAL @decalage DAY),
(1038, 12, 'achat', 'Commande n° 1240', 'commande:1240', 'valide', '2026-07-10 21:28:37' + INTERVAL @decalage DAY),
(1038, 5, 'avis', 'Avis sur « Le Charme discret de l''intestin »', 'livre:1011', 'valide', '2026-07-10 13:00:35' + INTERVAL @decalage DAY),
(1038, 36, 'achat', 'Commande n° 1241', 'commande:1241', 'valide', '2026-08-22 16:24:05' + INTERVAL @decalage DAY),
(1039, 15, 'achat', 'Commande n° 1242', 'commande:1242', 'valide', '2025-09-20 22:26:30' + INTERVAL @decalage DAY),
(1039, 20, 'premier_achat', 'Bonus premier achat', 'commande:1242', 'valide', '2025-09-20 22:26:30' + INTERVAL @decalage DAY),
(1039, 5, 'badge', 'Badge « Lecteur fidèle » bronze', 'lecteur_fidele:1', 'valide', '2025-09-20 22:26:30' + INTERVAL @decalage DAY),
(1039, 22, 'achat', 'Commande n° 1243', 'commande:1243', 'valide', '2025-09-21 11:00:53' + INTERVAL @decalage DAY),
(1039, 3, 'achat', 'Commande n° 1244', 'commande:1244', 'valide', '2025-09-25 12:16:03' + INTERVAL @decalage DAY),
(1039, 13, 'achat', 'Commande n° 1245', 'commande:1245', 'valide', '2025-10-31 16:13:35' + INTERVAL @decalage DAY),
(1039, 8, 'achat', 'Commande n° 1246', 'commande:1246', 'valide', '2025-12-01 14:43:07' + INTERVAL @decalage DAY),
(1039, 15, 'badge', 'Badge « Lecteur fidèle » argent', 'lecteur_fidele:2', 'valide', '2025-12-01 14:43:07' + INTERVAL @decalage DAY),
(1039, 5, 'achat', 'Commande n° 1247', 'commande:1247', 'valide', '2025-12-03 20:07:30' + INTERVAL @decalage DAY),
(1039, 24, 'achat', 'Commande n° 1248', 'commande:1248', 'valide', '2025-12-05 21:25:37' + INTERVAL @decalage DAY),
(1039, 17, 'achat', 'Commande n° 1249', 'commande:1249', 'valide', '2025-12-17 10:48:47' + INTERVAL @decalage DAY),
(1039, 8, 'achat', 'Commande n° 1250', 'commande:1250', 'valide', '2025-12-26 08:02:41' + INTERVAL @decalage DAY),
(1039, -100, 'depense', 'Bon de 5,00 € (commande n° 1251)', 'commande:1251', 'valide', '2025-12-31 17:01:45' + INTERVAL @decalage DAY),
(1039, 20, 'achat', 'Commande n° 1251', 'commande:1251', 'valide', '2025-12-31 17:01:45' + INTERVAL @decalage DAY),
(1039, 5, 'avis', 'Avis sur « La Passe-miroir - Livre 1 : Les Fiancés de l''hiver »', 'livre:1001', 'valide', '2026-01-11 18:01:45' + INTERVAL @decalage DAY),
(1039, 10, 'premier_avis', 'Bonus premier avis validé', 'premier', 'valide', '2026-01-13 22:01:45' + INTERVAL @decalage DAY),
(1039, 5, 'badge', 'Badge « Plume critique » bronze', 'plume_critique:1', 'valide', '2026-01-13 22:01:45' + INTERVAL @decalage DAY),
(1039, 9, 'achat', 'Commande n° 1252', 'commande:1252', 'valide', '2026-01-18 12:22:13' + INTERVAL @decalage DAY),
(1039, 4, 'achat', 'Commande n° 1253', 'commande:1253', 'valide', '2026-01-19 16:34:35' + INTERVAL @decalage DAY),
(1039, 9, 'achat', 'Commande n° 1254', 'commande:1254', 'valide', '2026-02-09 19:22:25' + INTERVAL @decalage DAY),
(1039, 117, 'achat', 'Commande n° 1255', 'commande:1255', 'valide', '2026-02-17 12:09:02' + INTERVAL @decalage DAY),
(1039, 6, 'achat', 'Commande n° 1256', 'commande:1256', 'valide', '2026-03-17 16:14:38' + INTERVAL @decalage DAY),
(1039, -100, 'depense', 'Bon de 5,00 € (commande n° 1257)', 'commande:1257', 'valide', '2026-03-18 12:35:47' + INTERVAL @decalage DAY),
(1039, 14, 'achat', 'Commande n° 1257', 'commande:1257', 'valide', '2026-03-18 12:35:47' + INTERVAL @decalage DAY),
(1039, 4, 'achat', 'Commande n° 1258', 'commande:1258', 'valide', '2026-03-23 16:23:25' + INTERVAL @decalage DAY),
(1039, 9, 'achat', 'Commande n° 1259', 'commande:1259', 'valide', '2026-04-07 21:23:40' + INTERVAL @decalage DAY),
(1039, 13, 'achat', 'Commande n° 1260', 'commande:1260', 'valide', '2026-04-15 13:23:23' + INTERVAL @decalage DAY),
(1039, 29, 'achat', 'Commande n° 1261', 'commande:1261', 'valide', '2026-04-25 13:42:34' + INTERVAL @decalage DAY),
(1039, -100, 'depense', 'Bon de 5,00 € (commande n° 1262)', 'commande:1262', 'valide', '2026-05-02 22:07:42' + INTERVAL @decalage DAY),
(1039, 11, 'achat', 'Commande n° 1262', 'commande:1262', 'valide', '2026-05-02 22:07:42' + INTERVAL @decalage DAY),
(1039, 30, 'badge', 'Badge « Explorateur »', 'explorateur:1', 'valide', '2026-05-02 22:07:42' + INTERVAL @decalage DAY),
(1039, 30, 'badge', 'Badge « Collectionneur »', 'collectionneur:1', 'valide', '2026-05-02 22:07:42' + INTERVAL @decalage DAY),
(1039, 6, 'achat', 'Commande n° 1263', 'commande:1263', 'valide', '2026-05-19 22:55:34' + INTERVAL @decalage DAY),
(1039, 14, 'achat', 'Commande n° 1264', 'commande:1264', 'valide', '2026-05-29 09:31:38' + INTERVAL @decalage DAY),
(1039, -200, 'depense', 'Bon de 10,50 € (commande n° 1265)', 'commande:1265', 'valide', '2026-06-24 22:24:00' + INTERVAL @decalage DAY),
(1039, 41, 'achat', 'Commande n° 1265', 'commande:1265', 'valide', '2026-06-24 22:24:00' + INTERVAL @decalage DAY),
(1039, 1, 'bonus_niveau', 'Bonus Dévoreur de pages +3 % (commande n° 1265)', 'commande:1265', 'valide', '2026-06-24 22:24:00' + INTERVAL @decalage DAY),
(1039, 30, 'badge', 'Badge « Lecteur fidèle » or', 'lecteur_fidele:3', 'valide', '2026-06-24 22:24:00' + INTERVAL @decalage DAY),
(1039, 5, 'avis', 'Avis sur « Le Petit Prince »', 'livre:1036', 'valide', '2026-06-26 03:55:34' + INTERVAL @decalage DAY),
(1039, 21, 'achat', 'Commande n° 1266', 'commande:1266', 'valide', '2026-07-01 16:53:29' + INTERVAL @decalage DAY),
(1039, 5, 'avis', 'Avis sur « Les Rivières pourpres »', 'livre:1026', 'valide', '2026-07-28 23:24:00' + INTERVAL @decalage DAY),
(1039, 16, 'achat', 'Commande n° 1267', 'commande:1267', 'valide', '2026-08-24 09:26:33' + INTERVAL @decalage DAY),
(1039, 30, 'badge', 'Badge « Un an parmi nous »', 'un_an:1', 'valide', '2026-08-24 09:26:33' + INTERVAL @decalage DAY),
(1039, 16, 'achat', 'Commande n° 1268', 'commande:1268', 'valide', '2026-08-27 21:33:58' + INTERVAL @decalage DAY),
(1039, 7, 'achat', 'Commande n° 1269', 'commande:1269', 'valide', '2026-09-06 09:52:43' + INTERVAL @decalage DAY),
(1039, -100, 'depense', 'Bon de 5,00 € (commande n° 1270)', 'commande:1270', 'valide', '2026-09-06 10:08:08' + INTERVAL @decalage DAY),
(1039, 41, 'achat', 'Commande n° 1270', 'commande:1270', 'valide', '2026-09-06 10:08:08' + INTERVAL @decalage DAY),
(1039, 1, 'bonus_niveau', 'Bonus Dévoreur de pages +3 % (commande n° 1270)', 'commande:1270', 'valide', '2026-09-06 10:08:08' + INTERVAL @decalage DAY),
(1039, 15, 'achat', 'Commande n° 1271', 'commande:1271', 'valide', '2026-09-07 18:59:44' + INTERVAL @decalage DAY),
(1039, -100, 'depense', 'Bon de 5,00 € (commande n° 1272)', 'commande:1272', 'valide', '2026-09-08 14:01:19' + INTERVAL @decalage DAY),
(1039, 14, 'achat', 'Commande n° 1272', 'commande:1272', 'valide', '2026-09-08 14:01:19' + INTERVAL @decalage DAY),
(1039, 29, 'achat', 'Commande n° 1273', 'commande:1273', 'valide', '2026-09-14 14:28:35' + INTERVAL @decalage DAY),
(1039, 20, 'don', 'Don récupéré : « Hitchcock / Truffaut - Édition définitive »', 'don:1003', 'valide', '2026-09-23 19:21:50' + INTERVAL @decalage DAY),
(1039, 5, 'badge', 'Badge « Cœur généreux » bronze', 'coeur_genereux:1', 'valide', '2026-09-23 19:21:50' + INTERVAL @decalage DAY),
(1039, 8, 'achat', 'Commande n° 1274', 'commande:1274', 'valide', '2026-09-30 08:44:44' + INTERVAL @decalage DAY),
(1039, -100, 'depense', 'Bon de 5,00 € (commande n° 1275)', 'commande:1275', 'valide', '2026-10-01 21:02:21' + INTERVAL @decalage DAY),
(1039, 28, 'achat', 'Commande n° 1275', 'commande:1275', 'valide', '2026-10-01 21:02:21' + INTERVAL @decalage DAY),
(1039, 30, 'achat', 'Commande n° 1276', 'commande:1276', 'valide', '2026-10-23 20:25:37' + INTERVAL @decalage DAY),
(1040, 38, 'achat', 'Commande n° 1277', 'commande:1277', 'valide', '2025-04-24 17:01:00' + INTERVAL @decalage DAY),
(1040, 20, 'premier_achat', 'Bonus premier achat', 'commande:1277', 'valide', '2025-04-24 17:01:00' + INTERVAL @decalage DAY),
(1040, 5, 'badge', 'Badge « Lecteur fidèle » bronze', 'lecteur_fidele:1', 'valide', '2025-04-24 17:01:00' + INTERVAL @decalage DAY),
(1040, 13, 'achat', 'Commande n° 1278', 'commande:1278', 'valide', '2025-05-02 20:51:57' + INTERVAL @decalage DAY),
(1040, 3, 'achat', 'Commande n° 1279', 'commande:1279', 'valide', '2025-06-14 22:01:18' + INTERVAL @decalage DAY),
(1040, 5, 'avis', 'Avis sur « Jujutsu Kaisen - Tome 30 »', 'livre:11', 'valide', '2025-06-26 01:01:18' + INTERVAL @decalage DAY),
(1040, 10, 'premier_avis', 'Bonus premier avis validé', 'premier', 'valide', '2025-06-28 03:01:18' + INTERVAL @decalage DAY),
(1040, 5, 'badge', 'Badge « Plume critique » bronze', 'plume_critique:1', 'valide', '2025-06-28 03:01:18' + INTERVAL @decalage DAY),
(1040, 46, 'achat', 'Commande n° 1280', 'commande:1280', 'valide', '2025-07-13 16:45:58' + INTERVAL @decalage DAY),
(1040, 15, 'badge', 'Badge « Lecteur fidèle » argent', 'lecteur_fidele:2', 'valide', '2025-07-13 16:45:58' + INTERVAL @decalage DAY),
(1040, 3, 'achat', 'Commande n° 1281', 'commande:1281', 'valide', '2025-08-06 11:13:20' + INTERVAL @decalage DAY),
(1040, 5, 'avis', 'Avis sur « Pixel et Moustache - Tome 1 : Le chat qui voulait voler »', 'livre:1017', 'valide', '2025-08-13 19:45:58' + INTERVAL @decalage DAY),
(1040, 11, 'achat', 'Commande n° 1282', 'commande:1282', 'valide', '2025-08-15 12:20:34' + INTERVAL @decalage DAY),
(1040, -100, 'depense', 'Bon de 5,00 € (commande n° 1283)', 'commande:1283', 'valide', '2025-08-25 10:38:25' + INTERVAL @decalage DAY),
(1040, 15, 'achat', 'Commande n° 1283', 'commande:1283', 'valide', '2025-08-25 10:38:25' + INTERVAL @decalage DAY),
(1040, 15, 'achat', 'Commande n° 1284', 'commande:1284', 'valide', '2025-08-25 17:02:41' + INTERVAL @decalage DAY),
(1040, 5, 'avis', 'Avis sur « Coder proprement »', 'livre:1027', 'valide', '2025-09-10 17:02:41' + INTERVAL @decalage DAY),
(1040, 8, 'achat', 'Commande n° 1285', 'commande:1285', 'valide', '2025-10-05 22:08:39' + INTERVAL @decalage DAY),
(1040, -100, 'depense', 'Bon de 5,00 € (commande n° 1286)', 'commande:1286', 'valide', '2025-10-07 12:41:40' + INTERVAL @decalage DAY),
(1040, 13, 'achat', 'Commande n° 1286', 'commande:1286', 'valide', '2025-10-07 12:41:40' + INTERVAL @decalage DAY),
(1040, 14, 'achat', 'Commande n° 1287', 'commande:1287', 'valide', '2025-11-08 13:46:35' + INTERVAL @decalage DAY),
(1040, 11, 'achat', 'Commande n° 1288', 'commande:1288', 'valide', '2025-12-03 12:38:31' + INTERVAL @decalage DAY),
(1040, 4, 'achat', 'Commande n° 1289', 'commande:1289', 'valide', '2025-12-17 10:07:41' + INTERVAL @decalage DAY),
(1040, 85, 'achat', 'Commande n° 1290', 'commande:1290', 'valide', '2025-12-24 10:13:23' + INTERVAL @decalage DAY),
(1040, 1, 'achat', 'Commande n° 1291', 'commande:1291', 'valide', '2026-01-15 14:40:03' + INTERVAL @decalage DAY),
(1040, -100, 'depense', 'Bon de 5,00 € (commande n° 1292)', 'commande:1292', 'valide', '2026-01-20 19:28:01' + INTERVAL @decalage DAY),
(1040, 13, 'achat', 'Commande n° 1292', 'commande:1292', 'valide', '2026-01-20 19:28:01' + INTERVAL @decalage DAY),
(1040, 17, 'achat', 'Commande n° 1293', 'commande:1293', 'valide', '2026-05-04 22:46:29' + INTERVAL @decalage DAY),
(1040, 30, 'badge', 'Badge « Un an parmi nous »', 'un_an:1', 'valide', '2026-05-04 22:46:29' + INTERVAL @decalage DAY),
(1040, -100, 'depense', 'Bon de 5,00 € (commande n° 1294)', 'commande:1294', 'valide', '2026-05-07 09:40:56' + INTERVAL @decalage DAY),
(1040, 26, 'achat', 'Commande n° 1294', 'commande:1294', 'valide', '2026-05-07 09:40:56' + INTERVAL @decalage DAY),
(1040, 30, 'badge', 'Badge « Collectionneur »', 'collectionneur:1', 'valide', '2026-05-07 09:40:56' + INTERVAL @decalage DAY),
(1040, 7, 'achat', 'Commande n° 1295', 'commande:1295', 'valide', '2026-05-08 19:03:37' + INTERVAL @decalage DAY),
(1040, 10, 'achat', 'Commande n° 1296', 'commande:1296', 'valide', '2026-05-14 20:49:51' + INTERVAL @decalage DAY),
(1040, 7, 'achat', 'Commande n° 1297', 'commande:1297', 'valide', '2026-05-21 16:35:18' + INTERVAL @decalage DAY),
(1040, 20, 'achat', 'Commande n° 1298', 'commande:1298', 'valide', '2026-06-04 14:15:25' + INTERVAL @decalage DAY),
(1040, 23, 'achat', 'Commande n° 1299', 'commande:1299', 'valide', '2026-06-08 16:56:04' + INTERVAL @decalage DAY),
(1040, 5, 'avis', 'Avis sur « Je sais cuisiner »', 'livre:1013', 'valide', '2026-06-16 14:15:25' + INTERVAL @decalage DAY),
(1040, -100, 'depense', 'Bon de 5,00 € (commande n° 1300)', 'commande:1300', 'valide', '2026-07-12 19:34:54' + INTERVAL @decalage DAY),
(1040, 12, 'achat', 'Commande n° 1300', 'commande:1300', 'valide', '2026-07-12 19:34:54' + INTERVAL @decalage DAY),
(1040, 30, 'badge', 'Badge « Explorateur »', 'explorateur:1', 'valide', '2026-07-12 19:34:54' + INTERVAL @decalage DAY),
(1040, 45, 'achat', 'Commande n° 1301', 'commande:1301', 'valide', '2026-07-18 08:15:23' + INTERVAL @decalage DAY),
(1040, 1, 'bonus_niveau', 'Bonus Dévoreur de pages +3 % (commande n° 1301)', 'commande:1301', 'valide', '2026-07-18 08:15:23' + INTERVAL @decalage DAY),
(1040, -100, 'depense', 'Bon de 5,00 € (commande n° 1302)', 'commande:1302', 'valide', '2026-07-29 17:04:21' + INTERVAL @decalage DAY),
(1040, 13, 'achat', 'Commande n° 1302', 'commande:1302', 'valide', '2026-07-29 17:04:21' + INTERVAL @decalage DAY),
(1040, 30, 'badge', 'Badge « Lecteur fidèle » or', 'lecteur_fidele:3', 'valide', '2026-07-29 17:04:21' + INTERVAL @decalage DAY),
(1040, 10, 'achat', 'Commande n° 1303', 'commande:1303', 'valide', '2026-08-12 16:01:09' + INTERVAL @decalage DAY),
(1040, 4, 'achat', 'Commande n° 1304', 'commande:1304', 'valide', '2026-08-29 10:02:01' + INTERVAL @decalage DAY),
(1040, 8, 'achat', 'Commande n° 1305', 'commande:1305', 'valide', '2026-09-11 21:43:27' + INTERVAL @decalage DAY),
(1040, 8, 'achat', 'Commande n° 1306', 'commande:1306', 'valide', '2026-09-18 12:12:35' + INTERVAL @decalage DAY),
(1040, 44, 'achat', 'Commande n° 1307', 'commande:1307', 'valide', '2026-09-18 20:20:46' + INTERVAL @decalage DAY),
(1040, 1, 'bonus_niveau', 'Bonus Dévoreur de pages +3 % (commande n° 1307)', 'commande:1307', 'valide', '2026-09-18 20:20:46' + INTERVAL @decalage DAY),
(1040, -100, 'depense', 'Bon de 5,00 € (commande n° 1308)', 'commande:1308', 'valide', '2026-09-20 14:05:45' + INTERVAL @decalage DAY),
(1040, 73, 'achat', 'Commande n° 1308', 'commande:1308', 'valide', '2026-09-20 14:05:45' + INTERVAL @decalage DAY),
(1040, 2, 'bonus_niveau', 'Bonus Dévoreur de pages +3 % (commande n° 1308)', 'commande:1308', 'valide', '2026-09-20 14:05:45' + INTERVAL @decalage DAY),
(1040, 21, 'achat', 'Commande n° 1309', 'commande:1309', 'valide', '2026-10-16 13:04:02' + INTERVAL @decalage DAY),
(1040, -21, 'annulation', 'Points retirés : commande n° 1309 annulée', 'commande:1309', 'valide', '2026-10-17 13:04:02' + INTERVAL @decalage DAY),
(1040, -100, 'depense', 'Bon de 5,00 € (commande n° 1310)', 'commande:1310', 'valide', '2026-10-17 12:23:12' + INTERVAL @decalage DAY),
(1040, 62, 'achat', 'Commande n° 1310', 'commande:1310', 'valide', '2026-10-17 12:23:12' + INTERVAL @decalage DAY),
(1040, 1, 'bonus_niveau', 'Bonus Dévoreur de pages +3 % (commande n° 1310)', 'commande:1310', 'valide', '2026-10-17 12:23:12' + INTERVAL @decalage DAY),
(1041, 12, 'achat', 'Commande n° 1311', 'commande:1311', 'valide', '2025-08-29 16:54:35' + INTERVAL @decalage DAY),
(1041, 20, 'premier_achat', 'Bonus premier achat', 'commande:1311', 'valide', '2025-08-29 16:54:35' + INTERVAL @decalage DAY),
(1041, 5, 'badge', 'Badge « Lecteur fidèle » bronze', 'lecteur_fidele:1', 'valide', '2025-08-29 16:54:35' + INTERVAL @decalage DAY),
(1041, 41, 'achat', 'Commande n° 1312', 'commande:1312', 'valide', '2025-09-19 16:02:17' + INTERVAL @decalage DAY),
(1041, 12, 'achat', 'Commande n° 1313', 'commande:1313', 'valide', '2025-09-20 15:35:19' + INTERVAL @decalage DAY);

INSERT IGNORE INTO point_mouvement (id_user, points, type, libelle, reference, statut, date_mouvement) VALUES
(1041, 5, 'avis', 'Avis sur « Absolute Batman - Tome 2 »', 'livre:10', 'valide', '2025-09-28 19:02:17' + INTERVAL @decalage DAY),
(1041, 10, 'premier_avis', 'Bonus premier avis validé', 'premier', 'valide', '2025-09-30 20:02:17' + INTERVAL @decalage DAY),
(1041, 5, 'badge', 'Badge « Plume critique » bronze', 'plume_critique:1', 'valide', '2025-09-30 20:02:17' + INTERVAL @decalage DAY),
(1041, 8, 'achat', 'Commande n° 1314', 'commande:1314', 'valide', '2025-10-04 16:04:55' + INTERVAL @decalage DAY),
(1041, 17, 'achat', 'Commande n° 1315', 'commande:1315', 'valide', '2025-11-03 18:46:48' + INTERVAL @decalage DAY),
(1041, 15, 'badge', 'Badge « Lecteur fidèle » argent', 'lecteur_fidele:2', 'valide', '2025-11-03 18:46:48' + INTERVAL @decalage DAY),
(1041, 9, 'achat', 'Commande n° 1316', 'commande:1316', 'valide', '2025-11-22 11:57:12' + INTERVAL @decalage DAY),
(1041, 5, 'avis', 'Avis sur « 1984 »', 'livre:1025', 'valide', '2025-12-25 11:57:12' + INTERVAL @decalage DAY),
(1041, 4, 'achat', 'Commande n° 1317', 'commande:1317', 'valide', '2025-12-29 11:26:17' + INTERVAL @decalage DAY),
(1041, -100, 'depense', 'Bon de 5,00 € (commande n° 1318)', 'commande:1318', 'valide', '2026-01-11 10:40:31' + INTERVAL @decalage DAY),
(1041, 31, 'achat', 'Commande n° 1318', 'commande:1318', 'valide', '2026-01-11 10:40:31' + INTERVAL @decalage DAY),
(1041, 17, 'achat', 'Commande n° 1319', 'commande:1319', 'valide', '2026-01-14 20:02:30' + INTERVAL @decalage DAY),
(1041, 13, 'achat', 'Commande n° 1320', 'commande:1320', 'valide', '2026-03-04 17:57:31' + INTERVAL @decalage DAY),
(1041, -100, 'depense', 'Bon de 5,00 € (commande n° 1321)', 'commande:1321', 'valide', '2026-05-15 15:56:53' + INTERVAL @decalage DAY),
(1041, 11, 'achat', 'Commande n° 1321', 'commande:1321', 'valide', '2026-05-15 15:56:53' + INTERVAL @decalage DAY),
(1041, 22, 'achat', 'Commande n° 1322', 'commande:1322', 'valide', '2026-05-18 09:29:18' + INTERVAL @decalage DAY),
(1041, 30, 'badge', 'Badge « Explorateur »', 'explorateur:1', 'valide', '2026-05-18 09:29:18' + INTERVAL @decalage DAY),
(1041, 19, 'achat', 'Commande n° 1323', 'commande:1323', 'valide', '2026-05-20 08:09:02' + INTERVAL @decalage DAY),
(1041, 26, 'achat', 'Commande n° 1324', 'commande:1324', 'valide', '2026-06-19 09:40:43' + INTERVAL @decalage DAY),
(1041, -100, 'depense', 'Bon de 5,00 € (commande n° 1325)', 'commande:1325', 'valide', '2026-06-22 17:20:02' + INTERVAL @decalage DAY),
(1041, 36, 'achat', 'Commande n° 1325', 'commande:1325', 'valide', '2026-06-22 17:20:02' + INTERVAL @decalage DAY),
(1041, 27, 'achat', 'Commande n° 1326', 'commande:1326', 'valide', '2026-07-03 11:06:11' + INTERVAL @decalage DAY),
(1041, 29, 'achat', 'Commande n° 1327', 'commande:1327', 'valide', '2026-07-15 11:42:09' + INTERVAL @decalage DAY),
(1041, 11, 'achat', 'Commande n° 1328', 'commande:1328', 'valide', '2026-08-12 16:43:37' + INTERVAL @decalage DAY),
(1041, 30, 'badge', 'Badge « Un an parmi nous »', 'un_an:1', 'valide', '2026-08-12 16:43:37' + INTERVAL @decalage DAY),
(1041, 13, 'achat', 'Commande n° 1329', 'commande:1329', 'valide', '2026-08-28 17:19:57' + INTERVAL @decalage DAY),
(1041, 14, 'achat', 'Commande n° 1330', 'commande:1330', 'valide', '2026-08-31 08:15:14' + INTERVAL @decalage DAY),
(1041, 69, 'achat', 'Commande n° 1331', 'commande:1331', 'valide', '2026-09-16 20:28:13' + INTERVAL @decalage DAY),
(1041, 2, 'bonus_niveau', 'Bonus Dévoreur de pages +3 % (commande n° 1331)', 'commande:1331', 'valide', '2026-09-16 20:28:13' + INTERVAL @decalage DAY),
(1041, 30, 'badge', 'Badge « Collectionneur »', 'collectionneur:1', 'valide', '2026-09-16 20:28:13' + INTERVAL @decalage DAY),
(1042, 22, 'achat', 'Commande n° 1332', 'commande:1332', 'valide', '2025-09-15 12:25:05' + INTERVAL @decalage DAY),
(1042, 20, 'premier_achat', 'Bonus premier achat', 'commande:1332', 'valide', '2025-09-15 12:25:05' + INTERVAL @decalage DAY),
(1042, 5, 'badge', 'Badge « Lecteur fidèle » bronze', 'lecteur_fidele:1', 'valide', '2025-09-15 12:25:05' + INTERVAL @decalage DAY),
(1042, 5, 'avis', 'Avis sur « L''Usage du monde »', 'livre:1024', 'valide', '2025-09-30 19:25:05' + INTERVAL @decalage DAY),
(1042, 10, 'premier_avis', 'Bonus premier avis validé', 'premier', 'valide', '2025-10-01 21:25:05' + INTERVAL @decalage DAY),
(1042, 5, 'badge', 'Badge « Plume critique » bronze', 'plume_critique:1', 'valide', '2025-10-01 21:25:05' + INTERVAL @decalage DAY),
(1042, 6, 'achat', 'Commande n° 1333', 'commande:1333', 'valide', '2025-10-03 11:45:53' + INTERVAL @decalage DAY),
(1042, 20, 'achat', 'Commande n° 1334', 'commande:1334', 'valide', '2025-11-29 13:39:12' + INTERVAL @decalage DAY),
(1042, 20, 'achat', 'Commande n° 1335', 'commande:1335', 'valide', '2025-12-08 12:00:47' + INTERVAL @decalage DAY),
(1042, 15, 'badge', 'Badge « Lecteur fidèle » argent', 'lecteur_fidele:2', 'valide', '2025-12-08 12:00:47' + INTERVAL @decalage DAY),
(1042, -100, 'depense', 'Bon de 5,00 € (commande n° 1336)', 'commande:1336', 'valide', '2026-01-02 13:41:32' + INTERVAL @decalage DAY),
(1042, 30, 'achat', 'Commande n° 1336', 'commande:1336', 'valide', '2026-01-02 13:41:32' + INTERVAL @decalage DAY),
(1042, 12, 'achat', 'Commande n° 1337', 'commande:1337', 'valide', '2026-01-04 08:59:25' + INTERVAL @decalage DAY),
(1042, 38, 'achat', 'Commande n° 1338', 'commande:1338', 'valide', '2026-01-07 22:27:55' + INTERVAL @decalage DAY),
(1042, -100, 'depense', 'Bon de 5,00 € (commande n° 1339)', 'commande:1339', 'valide', '2026-01-09 13:30:01' + INTERVAL @decalage DAY),
(1042, 13, 'achat', 'Commande n° 1339', 'commande:1339', 'valide', '2026-01-09 13:30:01' + INTERVAL @decalage DAY),
(1042, 28, 'achat', 'Commande n° 1340', 'commande:1340', 'valide', '2026-01-16 21:20:58' + INTERVAL @decalage DAY),
(1042, 6, 'achat', 'Commande n° 1341', 'commande:1341', 'valide', '2026-01-27 16:56:30' + INTERVAL @decalage DAY),
(1042, 5, 'avis', 'Avis sur « La Passe-miroir - Livre 1 : Les Fiancés de l''hiver »', 'livre:1001', 'valide', '2026-02-11 15:30:01' + INTERVAL @decalage DAY),
(1042, 48, 'achat', 'Commande n° 1342', 'commande:1342', 'valide', '2026-03-21 12:08:31' + INTERVAL @decalage DAY),
(1042, 21, 'achat', 'Commande n° 1343', 'commande:1343', 'valide', '2026-04-03 18:32:09' + INTERVAL @decalage DAY),
(1042, 7, 'achat', 'Commande n° 1344', 'commande:1344', 'valide', '2026-05-13 22:42:46' + INTERVAL @decalage DAY),
(1042, 6, 'achat', 'Commande n° 1345', 'commande:1345', 'valide', '2026-06-28 17:31:38' + INTERVAL @decalage DAY),
(1042, 30, 'badge', 'Badge « Explorateur »', 'explorateur:1', 'valide', '2026-06-28 17:31:38' + INTERVAL @decalage DAY),
(1042, -100, 'depense', 'Bon de 5,00 € (commande n° 1346)', 'commande:1346', 'valide', '2026-08-06 08:41:40' + INTERVAL @decalage DAY),
(1042, 24, 'achat', 'Commande n° 1346', 'commande:1346', 'valide', '2026-08-06 08:41:40' + INTERVAL @decalage DAY),
(1042, 19, 'achat', 'Commande n° 1347', 'commande:1347', 'valide', '2026-08-08 21:56:42' + INTERVAL @decalage DAY),
(1042, 7, 'achat', 'Commande n° 1348', 'commande:1348', 'valide', '2026-08-15 21:33:39' + INTERVAL @decalage DAY),
(1042, 5, 'avis', 'Avis sur « Madame Bovary »', 'livre:1046', 'valide', '2026-08-28 03:33:39' + INTERVAL @decalage DAY),
(1042, -100, 'depense', 'Bon de 5,00 € (commande n° 1349)', 'commande:1349', 'valide', '2026-09-04 08:21:11' + INTERVAL @decalage DAY),
(1042, 24, 'achat', 'Commande n° 1349', 'commande:1349', 'valide', '2026-09-04 08:21:11' + INTERVAL @decalage DAY),
(1042, 10, 'presence', 'Présence à « Troc de livres de la rentrée »', 'inscription:1074', 'valide', '2026-09-19 12:00:00' + INTERVAL @decalage DAY),
(1042, 5, 'badge', 'Badge « Habitué des rencontres » bronze', 'habitue_rencontres:1', 'valide', '2026-09-19 12:00:00' + INTERVAL @decalage DAY),
(1042, 30, 'badge', 'Badge « Un an parmi nous »', 'un_an:1', 'valide', '2026-09-19 12:00:00' + INTERVAL @decalage DAY),
(1042, 11, 'achat', 'Commande n° 1350', 'commande:1350', 'valide', '2026-09-20 20:29:45' + INTERVAL @decalage DAY),
(1042, 36, 'achat', 'Commande n° 1351', 'commande:1351', 'valide', '2026-10-04 18:49:18' + INTERVAL @decalage DAY),
(1042, 1, 'bonus_niveau', 'Bonus Dévoreur de pages +3 % (commande n° 1351)', 'commande:1351', 'valide', '2026-10-04 18:49:18' + INTERVAL @decalage DAY),
(1042, 26, 'achat', 'Commande n° 1352', 'commande:1352', 'valide', '2026-10-15 10:27:07' + INTERVAL @decalage DAY),
(1042, 30, 'badge', 'Badge « Collectionneur »', 'collectionneur:1', 'valide', '2026-10-15 10:27:07' + INTERVAL @decalage DAY),
(1043, 45, 'achat', 'Commande n° 1353', 'commande:1353', 'valide', '2025-02-06 16:36:44' + INTERVAL @decalage DAY),
(1043, 20, 'premier_achat', 'Bonus premier achat', 'commande:1353', 'valide', '2025-02-06 16:36:44' + INTERVAL @decalage DAY),
(1043, 5, 'badge', 'Badge « Lecteur fidèle » bronze', 'lecteur_fidele:1', 'valide', '2025-02-06 16:36:44' + INTERVAL @decalage DAY),
(1043, 5, 'avis', 'Avis sur « Blacksad - Tome 1 : Quelque part entre les ombres »', 'livre:1028', 'valide', '2025-03-09 01:36:44' + INTERVAL @decalage DAY),
(1043, 10, 'premier_avis', 'Bonus premier avis validé', 'premier', 'valide', '2025-03-10 03:36:44' + INTERVAL @decalage DAY),
(1043, 5, 'badge', 'Badge « Plume critique » bronze', 'plume_critique:1', 'valide', '2025-03-10 03:36:44' + INTERVAL @decalage DAY),
(1043, 106, 'achat', 'Commande n° 1354', 'commande:1354', 'valide', '2025-03-20 17:59:49' + INTERVAL @decalage DAY),
(1043, 5, 'avis', 'Avis sur « Campbell Biologie (9e édition) »', 'livre:1045', 'valide', '2025-04-08 04:59:49' + INTERVAL @decalage DAY),
(1043, 6, 'achat', 'Commande n° 1355', 'commande:1355', 'valide', '2025-06-15 13:05:11' + INTERVAL @decalage DAY),
(1043, -100, 'depense', 'Bon de 5,00 € (commande n° 1356)', 'commande:1356', 'valide', '2025-06-28 19:23:34' + INTERVAL @decalage DAY),
(1043, 10, 'achat', 'Commande n° 1356', 'commande:1356', 'valide', '2025-06-28 19:23:34' + INTERVAL @decalage DAY),
(1043, 5, 'avis', 'Avis sur « Le Charme discret de l''intestin »', 'livre:1011', 'valide', '2025-07-06 04:23:34' + INTERVAL @decalage DAY),
(1043, 9, 'achat', 'Commande n° 1357', 'commande:1357', 'valide', '2025-09-21 15:43:47' + INTERVAL @decalage DAY),
(1043, 5, 'avis', 'Avis sur « Le Potager du paresseux »', 'livre:1015', 'valide', '2025-09-29 18:43:47' + INTERVAL @decalage DAY),
(1043, 62, 'achat', 'Commande n° 1358', 'commande:1358', 'valide', '2025-10-24 17:22:07' + INTERVAL @decalage DAY),
(1043, 15, 'badge', 'Badge « Lecteur fidèle » argent', 'lecteur_fidele:2', 'valide', '2025-10-24 17:22:07' + INTERVAL @decalage DAY),
(1043, -200, 'depense', 'Bon de 10,50 € (commande n° 1359)', 'commande:1359', 'valide', '2025-10-30 19:19:45' + INTERVAL @decalage DAY),
(1043, 36, 'achat', 'Commande n° 1359', 'commande:1359', 'valide', '2025-10-30 19:19:45' + INTERVAL @decalage DAY),
(1043, 51, 'achat', 'Commande n° 1360', 'commande:1360', 'valide', '2025-11-19 09:47:25' + INTERVAL @decalage DAY),
(1043, 15, 'achat', 'Commande n° 1361', 'commande:1361', 'valide', '2026-01-12 08:33:52' + INTERVAL @decalage DAY),
(1043, 46, 'achat', 'Commande n° 1362', 'commande:1362', 'valide', '2026-02-22 21:36:21' + INTERVAL @decalage DAY),
(1043, 1, 'bonus_niveau', 'Bonus Dévoreur de pages +3 % (commande n° 1362)', 'commande:1362', 'valide', '2026-02-22 21:36:21' + INTERVAL @decalage DAY),
(1043, 30, 'badge', 'Badge « Un an parmi nous »', 'un_an:1', 'valide', '2026-02-22 21:36:21' + INTERVAL @decalage DAY),
(1043, -100, 'depense', 'Bon de 5,00 € (commande n° 1363)', 'commande:1363', 'valide', '2026-03-24 14:33:21' + INTERVAL @decalage DAY),
(1043, 12, 'achat', 'Commande n° 1363', 'commande:1363', 'valide', '2026-03-24 14:33:21' + INTERVAL @decalage DAY),
(1043, -100, 'depense', 'Bon de 5,00 € (commande n° 1364)', 'commande:1364', 'valide', '2026-04-30 11:03:43' + INTERVAL @decalage DAY),
(1043, 76, 'achat', 'Commande n° 1364', 'commande:1364', 'valide', '2026-04-30 11:03:43' + INTERVAL @decalage DAY),
(1043, 2, 'bonus_niveau', 'Bonus Dévoreur de pages +3 % (commande n° 1364)', 'commande:1364', 'valide', '2026-04-30 11:03:43' + INTERVAL @decalage DAY),
(1043, 41, 'achat', 'Commande n° 1365', 'commande:1365', 'valide', '2026-08-02 10:09:12' + INTERVAL @decalage DAY),
(1043, 1, 'bonus_niveau', 'Bonus Dévoreur de pages +3 % (commande n° 1365)', 'commande:1365', 'valide', '2026-08-02 10:09:12' + INTERVAL @decalage DAY),
(1043, -42, 'annulation', 'Points retirés : commande n° 1365 annulée', 'commande:1365', 'valide', '2026-08-03 10:09:12' + INTERVAL @decalage DAY),
(1043, 10, 'presence', 'Présence à « Troc de livres de la rentrée »', 'inscription:1060', 'valide', '2026-09-19 11:00:00' + INTERVAL @decalage DAY),
(1043, 5, 'badge', 'Badge « Habitué des rencontres » bronze', 'habitue_rencontres:1', 'valide', '2026-09-19 11:00:00' + INTERVAL @decalage DAY),
(1043, 9, 'achat', 'Commande n° 1366', 'commande:1366', 'valide', '2026-10-20 10:02:02' + INTERVAL @decalage DAY),
(1044, 24, 'achat', 'Commande n° 1367', 'commande:1367', 'valide', '2025-03-13 19:58:52' + INTERVAL @decalage DAY),
(1044, 20, 'premier_achat', 'Bonus premier achat', 'commande:1367', 'valide', '2025-03-13 19:58:52' + INTERVAL @decalage DAY),
(1044, 5, 'badge', 'Badge « Lecteur fidèle » bronze', 'lecteur_fidele:1', 'valide', '2025-03-13 19:58:52' + INTERVAL @decalage DAY),
(1044, 18, 'achat', 'Commande n° 1368', 'commande:1368', 'valide', '2025-03-23 11:06:30' + INTERVAL @decalage DAY),
(1044, 8, 'achat', 'Commande n° 1369', 'commande:1369', 'valide', '2025-04-11 11:25:30' + INTERVAL @decalage DAY),
(1044, 7, 'achat', 'Commande n° 1370', 'commande:1370', 'valide', '2025-04-24 12:09:14' + INTERVAL @decalage DAY),
(1044, 19, 'achat', 'Commande n° 1371', 'commande:1371', 'valide', '2025-05-15 08:03:38' + INTERVAL @decalage DAY),
(1044, 15, 'badge', 'Badge « Lecteur fidèle » argent', 'lecteur_fidele:2', 'valide', '2025-05-15 08:03:38' + INTERVAL @decalage DAY),
(1044, -100, 'depense', 'Bon de 5,00 € (commande n° 1372)', 'commande:1372', 'valide', '2025-05-23 08:26:11' + INTERVAL @decalage DAY),
(1044, 22, 'achat', 'Commande n° 1372', 'commande:1372', 'valide', '2025-05-23 08:26:11' + INTERVAL @decalage DAY),
(1044, 7, 'achat', 'Commande n° 1373', 'commande:1373', 'valide', '2025-06-12 19:42:50' + INTERVAL @decalage DAY),
(1044, 5, 'avis', 'Avis sur « Hitchcock / Truffaut - Édition définitive »', 'livre:1041', 'valide', '2025-06-11 10:03:38' + INTERVAL @decalage DAY),
(1044, 10, 'premier_avis', 'Bonus premier avis validé', 'premier', 'valide', '2025-06-13 15:03:38' + INTERVAL @decalage DAY),
(1044, 5, 'badge', 'Badge « Plume critique » bronze', 'plume_critique:1', 'valide', '2025-06-13 15:03:38' + INTERVAL @decalage DAY),
(1044, 5, 'avis', 'Avis sur « Le Capital au XXIe siècle »', 'livre:1020', 'valide', '2025-06-12 16:26:11' + INTERVAL @decalage DAY),
(1044, 5, 'avis', 'Avis sur « L''Usage du monde »', 'livre:1024', 'valide', '2025-06-24 13:26:11' + INTERVAL @decalage DAY),
(1044, 4, 'achat', 'Commande n° 1374', 'commande:1374', 'valide', '2025-08-08 20:59:06' + INTERVAL @decalage DAY),
(1044, 7, 'achat', 'Commande n° 1375', 'commande:1375', 'valide', '2025-08-14 09:03:06' + INTERVAL @decalage DAY),
(1044, 14, 'achat', 'Commande n° 1376', 'commande:1376', 'valide', '2025-08-15 14:50:09' + INTERVAL @decalage DAY),
(1044, 23, 'achat', 'Commande n° 1377', 'commande:1377', 'valide', '2025-08-19 13:48:53' + INTERVAL @decalage DAY),
(1044, 10, 'achat', 'Commande n° 1378', 'commande:1378', 'valide', '2025-09-07 09:36:10' + INTERVAL @decalage DAY),
(1044, 3, 'achat', 'Commande n° 1379', 'commande:1379', 'valide', '2025-09-11 19:47:05' + INTERVAL @decalage DAY),
(1044, 5, 'avis', 'Avis sur « Lexique des termes juridiques 2025-2026 »', 'livre:1009', 'valide', '2025-09-10 18:50:09' + INTERVAL @decalage DAY),
(1044, 14, 'achat', 'Commande n° 1380', 'commande:1380', 'valide', '2025-09-17 22:08:08' + INTERVAL @decalage DAY),
(1044, 63, 'achat', 'Commande n° 1381', 'commande:1381', 'valide', '2025-10-21 17:30:28' + INTERVAL @decalage DAY),
(1044, 20, 'achat', 'Commande n° 1382', 'commande:1382', 'valide', '2025-12-13 08:14:30' + INTERVAL @decalage DAY),
(1044, 30, 'badge', 'Badge « Collectionneur »', 'collectionneur:1', 'valide', '2025-12-13 08:14:30' + INTERVAL @decalage DAY),
(1044, 12, 'achat', 'Commande n° 1383', 'commande:1383', 'valide', '2025-12-13 21:53:38' + INTERVAL @decalage DAY),
(1044, 21, 'achat', 'Commande n° 1384', 'commande:1384', 'valide', '2025-12-23 20:39:46' + INTERVAL @decalage DAY),
(1044, 22, 'achat', 'Commande n° 1385', 'commande:1385', 'valide', '2025-12-26 22:31:00' + INTERVAL @decalage DAY),
(1044, 30, 'badge', 'Badge « Explorateur »', 'explorateur:1', 'valide', '2025-12-26 22:31:00' + INTERVAL @decalage DAY),
(1044, 14, 'achat', 'Commande n° 1386', 'commande:1386', 'valide', '2026-02-09 18:45:05' + INTERVAL @decalage DAY),
(1044, 3, 'achat', 'Commande n° 1387', 'commande:1387', 'valide', '2026-02-11 18:08:42' + INTERVAL @decalage DAY),
(1044, 4, 'achat', 'Commande n° 1388', 'commande:1388', 'valide', '2026-02-22 19:29:06' + INTERVAL @decalage DAY),
(1044, 6, 'achat', 'Commande n° 1389', 'commande:1389', 'valide', '2026-02-27 12:51:31' + INTERVAL @decalage DAY),
(1044, 12, 'achat', 'Commande n° 1390', 'commande:1390', 'valide', '2026-02-27 22:59:11' + INTERVAL @decalage DAY),
(1044, 14, 'achat', 'Commande n° 1391', 'commande:1391', 'valide', '2026-03-02 17:46:49' + INTERVAL @decalage DAY),
(1044, 7, 'achat', 'Commande n° 1392', 'commande:1392', 'valide', '2026-03-18 10:07:52' + INTERVAL @decalage DAY),
(1044, 30, 'badge', 'Badge « Lecteur fidèle » or', 'lecteur_fidele:3', 'valide', '2026-03-18 10:07:52' + INTERVAL @decalage DAY),
(1044, 30, 'badge', 'Badge « Un an parmi nous »', 'un_an:1', 'valide', '2026-03-18 10:07:52' + INTERVAL @decalage DAY),
(1044, -100, 'depense', 'Bon de 5,00 € (commande n° 1393)', 'commande:1393', 'valide', '2026-03-22 16:39:44' + INTERVAL @decalage DAY),
(1044, 14, 'achat', 'Commande n° 1393', 'commande:1393', 'valide', '2026-03-22 16:39:44' + INTERVAL @decalage DAY),
(1044, 32, 'achat', 'Commande n° 1394', 'commande:1394', 'valide', '2026-03-24 21:33:16' + INTERVAL @decalage DAY),
(1044, 14, 'achat', 'Commande n° 1395', 'commande:1395', 'valide', '2026-03-28 09:16:55' + INTERVAL @decalage DAY),
(1044, 9, 'achat', 'Commande n° 1396', 'commande:1396', 'valide', '2026-03-31 19:10:31' + INTERVAL @decalage DAY),
(1044, 22, 'achat', 'Commande n° 1397', 'commande:1397', 'valide', '2026-04-01 08:14:04' + INTERVAL @decalage DAY),
(1044, 4, 'achat', 'Commande n° 1398', 'commande:1398', 'valide', '2026-04-15 14:37:36' + INTERVAL @decalage DAY);

INSERT IGNORE INTO point_mouvement (id_user, points, type, libelle, reference, statut, date_mouvement) VALUES
(1044, 4, 'achat', 'Commande n° 1399', 'commande:1399', 'valide', '2026-05-16 15:21:16' + INTERVAL @decalage DAY),
(1044, 18, 'achat', 'Commande n° 1400', 'commande:1400', 'valide', '2026-05-24 21:59:02' + INTERVAL @decalage DAY),
(1044, 4, 'achat', 'Commande n° 1401', 'commande:1401', 'valide', '2026-06-19 18:16:50' + INTERVAL @decalage DAY),
(1044, -200, 'depense', 'Bon de 10,50 € (commande n° 1402)', 'commande:1402', 'valide', '2026-07-02 17:45:26' + INTERVAL @decalage DAY),
(1044, 15, 'achat', 'Commande n° 1402', 'commande:1402', 'valide', '2026-07-02 17:45:26' + INTERVAL @decalage DAY),
(1044, -200, 'depense', 'Bon de 10,50 € (commande n° 1403)', 'commande:1403', 'valide', '2026-07-08 18:44:31' + INTERVAL @decalage DAY),
(1044, 19, 'achat', 'Commande n° 1403', 'commande:1403', 'valide', '2026-07-08 18:44:31' + INTERVAL @decalage DAY),
(1044, 3, 'achat', 'Commande n° 1404', 'commande:1404', 'valide', '2026-07-28 17:00:35' + INTERVAL @decalage DAY),
(1044, 32, 'achat', 'Commande n° 1405', 'commande:1405', 'valide', '2026-08-07 16:59:08' + INTERVAL @decalage DAY),
(1044, -100, 'depense', 'Bon de 5,00 € (commande n° 1406)', 'commande:1406', 'valide', '2026-08-12 13:22:59' + INTERVAL @decalage DAY),
(1044, 28, 'achat', 'Commande n° 1406', 'commande:1406', 'valide', '2026-08-12 13:22:59' + INTERVAL @decalage DAY),
(1044, 20, 'don', 'Don récupéré : « Hunger Games - Tome 1 »', 'don:1004', 'valide', '2026-09-10 18:37:22' + INTERVAL @decalage DAY),
(1044, 5, 'badge', 'Badge « Cœur généreux » bronze', 'coeur_genereux:1', 'valide', '2026-09-10 18:37:22' + INTERVAL @decalage DAY),
(1044, 10, 'presence', 'Présence à « Troc de livres de la rentrée »', 'inscription:1065', 'valide', '2026-09-19 10:00:00' + INTERVAL @decalage DAY),
(1044, 5, 'badge', 'Badge « Habitué des rencontres » bronze', 'habitue_rencontres:1', 'valide', '2026-09-19 10:00:00' + INTERVAL @decalage DAY),
(1044, 11, 'achat', 'Commande n° 1407', 'commande:1407', 'valide', '2026-10-07 17:56:24' + INTERVAL @decalage DAY),
(1044, 30, 'achat', 'Commande n° 1408', 'commande:1408', 'valide', '2026-10-08 22:20:44' + INTERVAL @decalage DAY),
(1044, 16, 'achat', 'Commande n° 1409', 'commande:1409', 'valide', '2026-10-17 19:01:37' + INTERVAL @decalage DAY),
(1045, 25, 'achat', 'Commande n° 1410', 'commande:1410', 'valide', '2025-07-08 13:36:46' + INTERVAL @decalage DAY),
(1045, 20, 'premier_achat', 'Bonus premier achat', 'commande:1410', 'valide', '2025-07-08 13:36:46' + INTERVAL @decalage DAY),
(1045, 5, 'badge', 'Badge « Lecteur fidèle » bronze', 'lecteur_fidele:1', 'valide', '2025-07-08 13:36:46' + INTERVAL @decalage DAY),
(1045, 8, 'achat', 'Commande n° 1411', 'commande:1411', 'valide', '2025-07-21 14:51:13' + INTERVAL @decalage DAY),
(1045, 5, 'achat', 'Commande n° 1412', 'commande:1412', 'valide', '2025-07-22 13:47:35' + INTERVAL @decalage DAY),
(1045, 5, 'achat', 'Commande n° 1413', 'commande:1413', 'valide', '2025-08-01 13:35:37' + INTERVAL @decalage DAY),
(1045, 9, 'achat', 'Commande n° 1414', 'commande:1414', 'valide', '2025-08-19 13:24:53' + INTERVAL @decalage DAY),
(1045, 15, 'badge', 'Badge « Lecteur fidèle » argent', 'lecteur_fidele:2', 'valide', '2025-08-19 13:24:53' + INTERVAL @decalage DAY),
(1045, 13, 'achat', 'Commande n° 1415', 'commande:1415', 'valide', '2025-08-30 16:14:01' + INTERVAL @decalage DAY),
(1045, 7, 'achat', 'Commande n° 1416', 'commande:1416', 'valide', '2025-09-29 09:54:40' + INTERVAL @decalage DAY),
(1045, 9, 'achat', 'Commande n° 1417', 'commande:1417', 'valide', '2025-10-12 17:48:59' + INTERVAL @decalage DAY),
(1045, 7, 'achat', 'Commande n° 1418', 'commande:1418', 'valide', '2025-10-27 14:00:18' + INTERVAL @decalage DAY),
(1045, 23, 'achat', 'Commande n° 1419', 'commande:1419', 'valide', '2025-11-03 18:58:02' + INTERVAL @decalage DAY),
(1045, 8, 'achat', 'Commande n° 1420', 'commande:1420', 'valide', '2025-11-26 21:09:46' + INTERVAL @decalage DAY),
(1045, 15, 'achat', 'Commande n° 1421', 'commande:1421', 'valide', '2026-01-19 14:04:58' + INTERVAL @decalage DAY),
(1045, 42, 'achat', 'Commande n° 1422', 'commande:1422', 'valide', '2026-01-27 08:44:48' + INTERVAL @decalage DAY),
(1045, 4, 'achat', 'Commande n° 1423', 'commande:1423', 'valide', '2026-03-05 14:42:59' + INTERVAL @decalage DAY),
(1045, -100, 'depense', 'Bon de 5,00 € (commande n° 1424)', 'commande:1424', 'valide', '2026-03-16 14:01:46' + INTERVAL @decalage DAY),
(1045, 10, 'achat', 'Commande n° 1424', 'commande:1424', 'valide', '2026-03-16 14:01:46' + INTERVAL @decalage DAY),
(1045, 4, 'achat', 'Commande n° 1425', 'commande:1425', 'valide', '2026-03-17 20:38:45' + INTERVAL @decalage DAY),
(1045, 5, 'achat', 'Commande n° 1426', 'commande:1426', 'valide', '2026-03-23 13:22:02' + INTERVAL @decalage DAY),
(1045, 11, 'achat', 'Commande n° 1427', 'commande:1427', 'valide', '2026-05-23 14:05:02' + INTERVAL @decalage DAY),
(1045, -100, 'depense', 'Bon de 5,00 € (commande n° 1428)', 'commande:1428', 'valide', '2026-05-26 20:25:00' + INTERVAL @decalage DAY),
(1045, 12, 'achat', 'Commande n° 1428', 'commande:1428', 'valide', '2026-05-26 20:25:00' + INTERVAL @decalage DAY),
(1045, 30, 'badge', 'Badge « Collectionneur »', 'collectionneur:1', 'valide', '2026-05-26 20:25:00' + INTERVAL @decalage DAY),
(1045, 20, 'achat', 'Commande n° 1429', 'commande:1429', 'valide', '2026-05-31 12:13:00' + INTERVAL @decalage DAY),
(1045, 36, 'achat', 'Commande n° 1430', 'commande:1430', 'valide', '2026-07-10 21:48:43' + INTERVAL @decalage DAY),
(1045, 30, 'badge', 'Badge « Un an parmi nous »', 'un_an:1', 'valide', '2026-07-10 21:48:43' + INTERVAL @decalage DAY),
(1045, 10, 'achat', 'Commande n° 1431', 'commande:1431', 'valide', '2026-07-18 09:00:06' + INTERVAL @decalage DAY),
(1045, 14, 'achat', 'Commande n° 1432', 'commande:1432', 'valide', '2026-07-21 08:00:56' + INTERVAL @decalage DAY),
(1045, -100, 'depense', 'Bon de 5,00 € (commande n° 1433)', 'commande:1433', 'valide', '2026-07-28 20:05:12' + INTERVAL @decalage DAY),
(1045, 14, 'achat', 'Commande n° 1433', 'commande:1433', 'valide', '2026-07-28 20:05:12' + INTERVAL @decalage DAY),
(1045, 19, 'achat', 'Commande n° 1434', 'commande:1434', 'valide', '2026-08-07 15:17:45' + INTERVAL @decalage DAY),
(1045, 30, 'badge', 'Badge « Lecteur fidèle » or', 'lecteur_fidele:3', 'valide', '2026-08-07 15:17:45' + INTERVAL @decalage DAY),
(1045, -100, 'depense', 'Bon de 5,00 € (commande n° 1435)', 'commande:1435', 'valide', '2026-08-24 12:49:09' + INTERVAL @decalage DAY),
(1045, 16, 'achat', 'Commande n° 1435', 'commande:1435', 'valide', '2026-08-24 12:49:09' + INTERVAL @decalage DAY),
(1045, 22, 'achat', 'Commande n° 1436', 'commande:1436', 'valide', '2026-09-10 19:48:46' + INTERVAL @decalage DAY),
(1045, 10, 'presence', 'Présence à « Troc de livres de la rentrée »', 'inscription:1073', 'valide', '2026-09-19 10:00:00' + INTERVAL @decalage DAY),
(1045, 5, 'badge', 'Badge « Habitué des rencontres » bronze', 'habitue_rencontres:1', 'valide', '2026-09-19 10:00:00' + INTERVAL @decalage DAY),
(1045, 5, 'avis', 'Avis sur « Bescherelle - La conjugaison pour tous »', 'livre:1006', 'valide', '2026-10-02 12:49:09' + INTERVAL @decalage DAY),
(1045, 10, 'premier_avis', 'Bonus premier avis validé', 'premier', 'valide', '2026-10-03 15:49:09' + INTERVAL @decalage DAY),
(1045, 5, 'badge', 'Badge « Plume critique » bronze', 'plume_critique:1', 'valide', '2026-10-03 15:49:09' + INTERVAL @decalage DAY),
(1045, 95, 'achat', 'Commande n° 1437', 'commande:1437', 'valide', '2026-10-12 18:40:00' + INTERVAL @decalage DAY),
(1045, 2, 'bonus_niveau', 'Bonus Dévoreur de pages +3 % (commande n° 1437)', 'commande:1437', 'valide', '2026-10-12 18:40:00' + INTERVAL @decalage DAY),
(1045, 5, 'avis', 'Avis sur « Campbell Biologie (9e édition) »', 'livre:1045', 'en_attente', '2026-10-24 11:30:29' + INTERVAL @decalage DAY),
(1046, 61, 'achat', 'Commande n° 1438', 'commande:1438', 'valide', '2025-05-02 13:07:13' + INTERVAL @decalage DAY),
(1046, 20, 'premier_achat', 'Bonus premier achat', 'commande:1438', 'valide', '2025-05-02 13:07:13' + INTERVAL @decalage DAY),
(1046, 5, 'badge', 'Badge « Lecteur fidèle » bronze', 'lecteur_fidele:1', 'valide', '2025-05-02 13:07:13' + INTERVAL @decalage DAY),
(1046, 28, 'achat', 'Commande n° 1439', 'commande:1439', 'valide', '2025-05-08 17:13:46' + INTERVAL @decalage DAY),
(1046, 4, 'achat', 'Commande n° 1440', 'commande:1440', 'valide', '2025-05-10 17:52:31' + INTERVAL @decalage DAY),
(1046, 100, 'achat', 'Commande n° 1441', 'commande:1441', 'valide', '2025-06-14 21:02:25' + INTERVAL @decalage DAY),
(1046, 5, 'avis', 'Avis sur « ABC du Bac - Mathématiques Terminale spécialité - Annales 2025 »', 'livre:1007', 'valide', '2025-06-15 19:13:46' + INTERVAL @decalage DAY),
(1046, 10, 'premier_avis', 'Bonus premier avis validé', 'premier', 'valide', '2025-06-17 22:13:46' + INTERVAL @decalage DAY),
(1046, 5, 'badge', 'Badge « Plume critique » bronze', 'plume_critique:1', 'valide', '2025-06-17 22:13:46' + INTERVAL @decalage DAY),
(1046, -200, 'depense', 'Bon de 10,50 € (commande n° 1442)', 'commande:1442', 'valide', '2025-07-31 11:27:34' + INTERVAL @decalage DAY),
(1046, 17, 'achat', 'Commande n° 1442', 'commande:1442', 'valide', '2025-07-31 11:27:34' + INTERVAL @decalage DAY),
(1046, 15, 'badge', 'Badge « Lecteur fidèle » argent', 'lecteur_fidele:2', 'valide', '2025-07-31 11:27:34' + INTERVAL @decalage DAY),
(1046, 51, 'achat', 'Commande n° 1443', 'commande:1443', 'valide', '2025-08-17 22:56:26' + INTERVAL @decalage DAY),
(1046, -100, 'depense', 'Bon de 5,00 € (commande n° 1444)', 'commande:1444', 'valide', '2025-09-06 14:18:35' + INTERVAL @decalage DAY),
(1046, 12, 'achat', 'Commande n° 1444', 'commande:1444', 'valide', '2025-09-06 14:18:35' + INTERVAL @decalage DAY),
(1046, 23, 'achat', 'Commande n° 1445', 'commande:1445', 'valide', '2025-11-08 09:07:13' + INTERVAL @decalage DAY),
(1046, 2, 'achat', 'Commande n° 1446', 'commande:1446', 'valide', '2025-11-28 12:09:58' + INTERVAL @decalage DAY),
(1046, 5, 'avis', 'Avis sur « L''Arabe du futur - Volume 6 »', 'livre:7', 'valide', '2025-12-01 15:07:13' + INTERVAL @decalage DAY),
(1046, 4, 'achat', 'Commande n° 1447', 'commande:1447', 'valide', '2025-12-28 14:31:45' + INTERVAL @decalage DAY),
(1046, 112, 'achat', 'Commande n° 1448', 'commande:1448', 'valide', '2026-01-08 10:01:40' + INTERVAL @decalage DAY),
(1046, 22, 'achat', 'Commande n° 1449', 'commande:1449', 'valide', '2026-05-05 18:45:59' + INTERVAL @decalage DAY),
(1046, 30, 'badge', 'Badge « Un an parmi nous »', 'un_an:1', 'valide', '2026-05-05 18:45:59' + INTERVAL @decalage DAY),
(1046, 15, 'achat', 'Commande n° 1450', 'commande:1450', 'valide', '2026-05-25 20:08:45' + INTERVAL @decalage DAY),
(1046, 6, 'achat', 'Commande n° 1451', 'commande:1451', 'valide', '2026-08-13 08:44:20' + INTERVAL @decalage DAY),
(1046, 49, 'achat', 'Commande n° 1452', 'commande:1452', 'valide', '2026-09-14 10:04:50' + INTERVAL @decalage DAY),
(1046, 1, 'bonus_niveau', 'Bonus Dévoreur de pages +3 % (commande n° 1452)', 'commande:1452', 'valide', '2026-09-14 10:04:50' + INTERVAL @decalage DAY),
(1046, 10, 'presence', 'Présence à « Troc de livres de la rentrée »', 'inscription:1071', 'valide', '2026-09-19 10:00:00' + INTERVAL @decalage DAY),
(1046, 5, 'badge', 'Badge « Habitué des rencontres » bronze', 'habitue_rencontres:1', 'valide', '2026-09-19 10:00:00' + INTERVAL @decalage DAY),
(1047, 71, 'achat', 'Commande n° 1453', 'commande:1453', 'valide', '2025-04-20 10:15:15' + INTERVAL @decalage DAY),
(1047, 20, 'premier_achat', 'Bonus premier achat', 'commande:1453', 'valide', '2025-04-20 10:15:15' + INTERVAL @decalage DAY),
(1047, 5, 'badge', 'Badge « Lecteur fidèle » bronze', 'lecteur_fidele:1', 'valide', '2025-04-20 10:15:15' + INTERVAL @decalage DAY),
(1047, 129, 'achat', 'Commande n° 1454', 'commande:1454', 'valide', '2025-05-01 20:52:26' + INTERVAL @decalage DAY),
(1047, 15, 'badge', 'Badge « Lecteur fidèle » argent', 'lecteur_fidele:2', 'valide', '2025-05-01 20:52:26' + INTERVAL @decalage DAY),
(1047, -200, 'depense', 'Bon de 10,50 € (commande n° 1455)', 'commande:1455', 'valide', '2025-05-28 10:08:47' + INTERVAL @decalage DAY),
(1047, 18, 'achat', 'Commande n° 1455', 'commande:1455', 'valide', '2025-05-28 10:08:47' + INTERVAL @decalage DAY),
(1047, 52, 'achat', 'Commande n° 1456', 'commande:1456', 'valide', '2025-06-10 11:08:04' + INTERVAL @decalage DAY),
(1047, 29, 'achat', 'Commande n° 1457', 'commande:1457', 'valide', '2025-06-22 11:41:34' + INTERVAL @decalage DAY),
(1047, -100, 'depense', 'Bon de 5,00 € (commande n° 1458)', 'commande:1458', 'valide', '2025-06-29 15:43:22' + INTERVAL @decalage DAY),
(1047, 116, 'achat', 'Commande n° 1458', 'commande:1458', 'valide', '2025-06-29 15:43:22' + INTERVAL @decalage DAY),
(1047, 19, 'achat', 'Commande n° 1459', 'commande:1459', 'valide', '2025-07-12 21:33:16' + INTERVAL @decalage DAY),
(1047, -100, 'depense', 'Bon de 5,00 € (commande n° 1460)', 'commande:1460', 'valide', '2025-07-28 08:17:21' + INTERVAL @decalage DAY),
(1047, 85, 'achat', 'Commande n° 1460', 'commande:1460', 'valide', '2025-07-28 08:17:21' + INTERVAL @decalage DAY),
(1047, 2, 'bonus_niveau', 'Bonus Dévoreur de pages +3 % (commande n° 1460)', 'commande:1460', 'valide', '2025-07-28 08:17:21' + INTERVAL @decalage DAY),
(1047, 5, 'avis', 'Avis sur « J''intègre - Maths MPSI-MP2I : exercices incontournables »', 'livre:1008', 'valide', '2025-08-02 13:17:21' + INTERVAL @decalage DAY),
(1047, 10, 'premier_avis', 'Bonus premier avis validé', 'premier', 'valide', '2025-08-03 16:17:21' + INTERVAL @decalage DAY),
(1047, 5, 'badge', 'Badge « Plume critique » bronze', 'plume_critique:1', 'valide', '2025-08-03 16:17:21' + INTERVAL @decalage DAY),
(1047, -100, 'depense', 'Bon de 5,00 € (commande n° 1461)', 'commande:1461', 'valide', '2025-09-10 16:30:03' + INTERVAL @decalage DAY),
(1047, 25, 'achat', 'Commande n° 1461', 'commande:1461', 'valide', '2025-09-10 16:30:03' + INTERVAL @decalage DAY),
(1047, -100, 'depense', 'Bon de 5,00 € (commande n° 1462)', 'commande:1462', 'valide', '2025-12-30 21:15:22' + INTERVAL @decalage DAY),
(1047, 114, 'achat', 'Commande n° 1462', 'commande:1462', 'valide', '2025-12-30 21:15:22' + INTERVAL @decalage DAY),
(1047, 3, 'bonus_niveau', 'Bonus Dévoreur de pages +3 % (commande n° 1462)', 'commande:1462', 'valide', '2025-12-30 21:15:22' + INTERVAL @decalage DAY),
(1047, 30, 'badge', 'Badge « Collectionneur »', 'collectionneur:1', 'valide', '2025-12-30 21:15:22' + INTERVAL @decalage DAY),
(1047, 44, 'achat', 'Commande n° 1463', 'commande:1463', 'valide', '2026-01-30 15:27:51' + INTERVAL @decalage DAY),
(1047, 1, 'bonus_niveau', 'Bonus Dévoreur de pages +3 % (commande n° 1463)', 'commande:1463', 'valide', '2026-01-30 15:27:51' + INTERVAL @decalage DAY),
(1047, 30, 'badge', 'Badge « Lecteur fidèle » or', 'lecteur_fidele:3', 'valide', '2026-01-30 15:27:51' + INTERVAL @decalage DAY),
(1047, 103, 'achat', 'Commande n° 1464', 'commande:1464', 'valide', '2026-01-30 20:26:58' + INTERVAL @decalage DAY),
(1047, 3, 'bonus_niveau', 'Bonus Dévoreur de pages +3 % (commande n° 1464)', 'commande:1464', 'valide', '2026-01-30 20:26:58' + INTERVAL @decalage DAY),
(1047, 31, 'achat', 'Commande n° 1465', 'commande:1465', 'valide', '2026-02-01 08:20:20' + INTERVAL @decalage DAY),
(1047, 1, 'bonus_niveau', 'Bonus Rat de bibliothèque +5 % (commande n° 1465)', 'commande:1465', 'valide', '2026-02-01 08:20:20' + INTERVAL @decalage DAY),
(1047, 5, 'avis', 'Avis sur « Cyrano de Bergerac »', 'livre:1042', 'valide', '2026-02-08 04:15:22' + INTERVAL @decalage DAY),
(1047, 5, 'avis', 'Avis sur « Hunger Games - Tome 1 »', 'livre:1029', 'valide', '2026-02-20 02:26:58' + INTERVAL @decalage DAY),
(1047, -200, 'depense', 'Bon de 10,50 € (commande n° 1466)', 'commande:1466', 'valide', '2026-03-07 19:42:27' + INTERVAL @decalage DAY),
(1047, 14, 'achat', 'Commande n° 1466', 'commande:1466', 'valide', '2026-03-07 19:42:27' + INTERVAL @decalage DAY),
(1047, 5, 'avis', 'Avis sur « Lexique des termes juridiques 2025-2026 »', 'livre:1009', 'valide', '2026-03-06 22:26:58' + INTERVAL @decalage DAY),
(1047, 51, 'achat', 'Commande n° 1467', 'commande:1467', 'valide', '2026-08-04 10:57:24' + INTERVAL @decalage DAY),
(1047, 2, 'bonus_niveau', 'Bonus Rat de bibliothèque +5 % (commande n° 1467)', 'commande:1467', 'valide', '2026-08-04 10:57:24' + INTERVAL @decalage DAY),
(1047, 30, 'badge', 'Badge « Un an parmi nous »', 'un_an:1', 'valide', '2026-08-04 10:57:24' + INTERVAL @decalage DAY),
(1047, 47, 'achat', 'Commande n° 1468', 'commande:1468', 'valide', '2026-09-22 17:30:50' + INTERVAL @decalage DAY),
(1047, 2, 'bonus_niveau', 'Bonus Rat de bibliothèque +5 % (commande n° 1468)', 'commande:1468', 'valide', '2026-09-22 17:30:50' + INTERVAL @decalage DAY),
(1048, 58, 'achat', 'Commande n° 1469', 'commande:1469', 'valide', '2025-03-07 17:40:42' + INTERVAL @decalage DAY),
(1048, 20, 'premier_achat', 'Bonus premier achat', 'commande:1469', 'valide', '2025-03-07 17:40:42' + INTERVAL @decalage DAY),
(1048, 5, 'badge', 'Badge « Lecteur fidèle » bronze', 'lecteur_fidele:1', 'valide', '2025-03-07 17:40:42' + INTERVAL @decalage DAY),
(1048, 31, 'achat', 'Commande n° 1470', 'commande:1470', 'valide', '2025-03-11 15:18:57' + INTERVAL @decalage DAY),
(1048, 15, 'badge', 'Badge « Lecteur fidèle » argent', 'lecteur_fidele:2', 'valide', '2025-03-11 15:18:57' + INTERVAL @decalage DAY),
(1048, 44, 'achat', 'Commande n° 1471', 'commande:1471', 'valide', '2025-03-16 21:52:36' + INTERVAL @decalage DAY),
(1048, 5, 'avis', 'Avis sur « Hunger Games - Tome 1 »', 'livre:1029', 'valide', '2025-03-23 17:40:42' + INTERVAL @decalage DAY),
(1048, 10, 'premier_avis', 'Bonus premier avis validé', 'premier', 'valide', '2025-03-25 22:40:42' + INTERVAL @decalage DAY),
(1048, 5, 'badge', 'Badge « Plume critique » bronze', 'plume_critique:1', 'valide', '2025-03-25 22:40:42' + INTERVAL @decalage DAY),
(1048, 5, 'avis', 'Avis sur « Bleach - Tome 01 »', 'livre:3', 'valide', '2025-03-29 19:18:57' + INTERVAL @decalage DAY),
(1048, 62, 'achat', 'Commande n° 1472', 'commande:1472', 'valide', '2025-04-04 18:44:03' + INTERVAL @decalage DAY),
(1048, 5, 'avis', 'Avis sur « Jujutsu Kaisen - Tome 22 »', 'livre:2', 'valide', '2025-04-11 22:18:57' + INTERVAL @decalage DAY),
(1048, 5, 'avis', 'Avis sur « Les Quatre Accords toltèques »', 'livre:1039', 'valide', '2025-04-12 21:18:57' + INTERVAL @decalage DAY),
(1048, -200, 'depense', 'Bon de 10,50 € (commande n° 1473)', 'commande:1473', 'valide', '2025-06-02 10:35:59' + INTERVAL @decalage DAY),
(1048, 15, 'achat', 'Commande n° 1473', 'commande:1473', 'valide', '2025-06-02 10:35:59' + INTERVAL @decalage DAY),
(1048, 24, 'achat', 'Commande n° 1474', 'commande:1474', 'valide', '2025-06-18 20:07:15' + INTERVAL @decalage DAY),
(1048, -100, 'depense', 'Bon de 5,00 € (commande n° 1475)', 'commande:1475', 'valide', '2025-08-15 22:50:07' + INTERVAL @decalage DAY),
(1048, 36, 'achat', 'Commande n° 1475', 'commande:1475', 'valide', '2025-08-15 22:50:07' + INTERVAL @decalage DAY);

INSERT IGNORE INTO point_mouvement (id_user, points, type, libelle, reference, statut, date_mouvement) VALUES
(1048, 14, 'achat', 'Commande n° 1476', 'commande:1476', 'valide', '2025-08-22 18:10:07' + INTERVAL @decalage DAY),
(1048, 30, 'badge', 'Badge « Collectionneur »', 'collectionneur:1', 'valide', '2025-08-22 18:10:07' + INTERVAL @decalage DAY),
(1048, 28, 'achat', 'Commande n° 1477', 'commande:1477', 'valide', '2025-09-14 19:10:59' + INTERVAL @decalage DAY),
(1048, -100, 'depense', 'Bon de 5,00 € (commande n° 1478)', 'commande:1478', 'valide', '2025-09-18 12:52:46' + INTERVAL @decalage DAY),
(1048, 19, 'achat', 'Commande n° 1478', 'commande:1478', 'valide', '2025-09-18 12:52:46' + INTERVAL @decalage DAY),
(1048, 26, 'achat', 'Commande n° 1479', 'commande:1479', 'valide', '2025-09-20 12:27:59' + INTERVAL @decalage DAY),
(1048, 14, 'achat', 'Commande n° 1480', 'commande:1480', 'valide', '2025-10-06 22:04:46' + INTERVAL @decalage DAY),
(1048, 30, 'badge', 'Badge « Lecteur fidèle » or', 'lecteur_fidele:3', 'valide', '2025-10-06 22:04:46' + INTERVAL @decalage DAY),
(1048, 30, 'badge', 'Badge « Explorateur »', 'explorateur:1', 'valide', '2025-10-06 22:04:46' + INTERVAL @decalage DAY),
(1048, 34, 'achat', 'Commande n° 1481', 'commande:1481', 'valide', '2025-10-18 22:59:37' + INTERVAL @decalage DAY),
(1048, 1, 'bonus_niveau', 'Bonus Dévoreur de pages +3 % (commande n° 1481)', 'commande:1481', 'valide', '2025-10-18 22:59:37' + INTERVAL @decalage DAY),
(1048, 10, 'achat', 'Commande n° 1482', 'commande:1482', 'valide', '2025-10-24 20:25:52' + INTERVAL @decalage DAY),
(1048, 50, 'achat', 'Commande n° 1483', 'commande:1483', 'valide', '2025-10-29 10:24:24' + INTERVAL @decalage DAY),
(1048, 1, 'bonus_niveau', 'Bonus Dévoreur de pages +3 % (commande n° 1483)', 'commande:1483', 'valide', '2025-10-29 10:24:24' + INTERVAL @decalage DAY),
(1048, 31, 'achat', 'Commande n° 1484', 'commande:1484', 'valide', '2025-11-02 08:48:00' + INTERVAL @decalage DAY),
(1048, -200, 'depense', 'Bon de 10,50 € (commande n° 1485)', 'commande:1485', 'valide', '2025-12-19 08:27:33' + INTERVAL @decalage DAY),
(1048, 18, 'achat', 'Commande n° 1485', 'commande:1485', 'valide', '2025-12-19 08:27:33' + INTERVAL @decalage DAY),
(1048, 52, 'achat', 'Commande n° 1486', 'commande:1486', 'valide', '2025-12-20 13:49:34' + INTERVAL @decalage DAY),
(1048, 1, 'bonus_niveau', 'Bonus Dévoreur de pages +3 % (commande n° 1486)', 'commande:1486', 'valide', '2025-12-20 13:49:34' + INTERVAL @decalage DAY),
(1048, -100, 'depense', 'Bon de 5,00 € (commande n° 1487)', 'commande:1487', 'valide', '2025-12-23 19:17:40' + INTERVAL @decalage DAY),
(1048, 40, 'achat', 'Commande n° 1487', 'commande:1487', 'valide', '2025-12-23 19:17:40' + INTERVAL @decalage DAY),
(1048, 1, 'bonus_niveau', 'Bonus Dévoreur de pages +3 % (commande n° 1487)', 'commande:1487', 'valide', '2025-12-23 19:17:40' + INTERVAL @decalage DAY),
(1048, 61, 'achat', 'Commande n° 1488', 'commande:1488', 'valide', '2026-01-28 10:23:41' + INTERVAL @decalage DAY),
(1048, 1, 'bonus_niveau', 'Bonus Dévoreur de pages +3 % (commande n° 1488)', 'commande:1488', 'valide', '2026-01-28 10:23:41' + INTERVAL @decalage DAY),
(1048, -100, 'depense', 'Bon de 5,00 € (commande n° 1489)', 'commande:1489', 'valide', '2026-02-21 10:39:49' + INTERVAL @decalage DAY),
(1048, 20, 'achat', 'Commande n° 1489', 'commande:1489', 'valide', '2026-02-21 10:39:49' + INTERVAL @decalage DAY),
(1048, 30, 'badge', 'Badge « Un an parmi nous »', 'un_an:1', 'valide', '2026-02-21 10:39:49' + INTERVAL @decalage DAY),
(1048, 45, 'achat', 'Commande n° 1490', 'commande:1490', 'valide', '2026-03-18 14:44:04' + INTERVAL @decalage DAY),
(1048, 1, 'bonus_niveau', 'Bonus Dévoreur de pages +3 % (commande n° 1490)', 'commande:1490', 'valide', '2026-03-18 14:44:04' + INTERVAL @decalage DAY),
(1048, 64, 'achat', 'Commande n° 1491', 'commande:1491', 'valide', '2026-03-21 13:38:58' + INTERVAL @decalage DAY),
(1048, 3, 'bonus_niveau', 'Bonus Rat de bibliothèque +5 % (commande n° 1491)', 'commande:1491', 'valide', '2026-03-21 13:38:58' + INTERVAL @decalage DAY),
(1048, -200, 'depense', 'Bon de 10,50 € (commande n° 1492)', 'commande:1492', 'valide', '2026-04-03 17:39:47' + INTERVAL @decalage DAY),
(1048, 23, 'achat', 'Commande n° 1492', 'commande:1492', 'valide', '2026-04-03 17:39:47' + INTERVAL @decalage DAY),
(1048, 1, 'bonus_niveau', 'Bonus Rat de bibliothèque +5 % (commande n° 1492)', 'commande:1492', 'valide', '2026-04-03 17:39:47' + INTERVAL @decalage DAY),
(1048, 47, 'achat', 'Commande n° 1493', 'commande:1493', 'valide', '2026-04-09 17:16:13' + INTERVAL @decalage DAY),
(1048, 2, 'bonus_niveau', 'Bonus Rat de bibliothèque +5 % (commande n° 1493)', 'commande:1493', 'valide', '2026-04-09 17:16:13' + INTERVAL @decalage DAY),
(1048, 5, 'avis', 'Avis sur « Introduction à l''algorithmique »', 'livre:1022', 'valide', '2026-04-26 22:39:47' + INTERVAL @decalage DAY),
(1048, 19, 'achat', 'Commande n° 1494', 'commande:1494', 'valide', '2026-05-01 09:44:16' + INTERVAL @decalage DAY),
(1048, 43, 'achat', 'Commande n° 1495', 'commande:1495', 'valide', '2026-05-03 17:41:16' + INTERVAL @decalage DAY),
(1048, 2, 'bonus_niveau', 'Bonus Rat de bibliothèque +5 % (commande n° 1495)', 'commande:1495', 'valide', '2026-05-03 17:41:16' + INTERVAL @decalage DAY),
(1048, -100, 'depense', 'Bon de 5,00 € (commande n° 1496)', 'commande:1496', 'valide', '2026-05-06 09:33:46' + INTERVAL @decalage DAY),
(1048, 22, 'achat', 'Commande n° 1496', 'commande:1496', 'valide', '2026-05-06 09:33:46' + INTERVAL @decalage DAY),
(1048, 1, 'bonus_niveau', 'Bonus Rat de bibliothèque +5 % (commande n° 1496)', 'commande:1496', 'valide', '2026-05-06 09:33:46' + INTERVAL @decalage DAY),
(1048, 22, 'achat', 'Commande n° 1497', 'commande:1497', 'valide', '2026-05-20 21:54:36' + INTERVAL @decalage DAY),
(1048, 1, 'bonus_niveau', 'Bonus Rat de bibliothèque +5 % (commande n° 1497)', 'commande:1497', 'valide', '2026-05-20 21:54:36' + INTERVAL @decalage DAY),
(1048, 15, 'achat', 'Commande n° 1498', 'commande:1498', 'valide', '2026-05-23 17:31:31' + INTERVAL @decalage DAY),
(1048, -100, 'depense', 'Bon de 5,00 € (commande n° 1499)', 'commande:1499', 'valide', '2026-06-08 14:46:16' + INTERVAL @decalage DAY),
(1048, 49, 'achat', 'Commande n° 1499', 'commande:1499', 'valide', '2026-06-08 14:46:16' + INTERVAL @decalage DAY),
(1048, 2, 'bonus_niveau', 'Bonus Rat de bibliothèque +5 % (commande n° 1499)', 'commande:1499', 'valide', '2026-06-08 14:46:16' + INTERVAL @decalage DAY),
(1048, 74, 'achat', 'Commande n° 1500', 'commande:1500', 'valide', '2026-06-12 20:04:46' + INTERVAL @decalage DAY),
(1048, 3, 'bonus_niveau', 'Bonus Rat de bibliothèque +5 % (commande n° 1500)', 'commande:1500', 'valide', '2026-06-12 20:04:46' + INTERVAL @decalage DAY),
(1048, 26, 'achat', 'Commande n° 1501', 'commande:1501', 'valide', '2026-06-21 11:43:14' + INTERVAL @decalage DAY),
(1048, 1, 'bonus_niveau', 'Bonus Rat de bibliothèque +5 % (commande n° 1501)', 'commande:1501', 'valide', '2026-06-21 11:43:14' + INTERVAL @decalage DAY),
(1048, -100, 'depense', 'Bon de 5,00 € (commande n° 1502)', 'commande:1502', 'valide', '2026-07-07 19:45:42' + INTERVAL @decalage DAY),
(1048, 30, 'achat', 'Commande n° 1502', 'commande:1502', 'valide', '2026-07-07 19:45:42' + INTERVAL @decalage DAY),
(1048, 1, 'bonus_niveau', 'Bonus Rat de bibliothèque +5 % (commande n° 1502)', 'commande:1502', 'valide', '2026-07-07 19:45:42' + INTERVAL @decalage DAY),
(1048, 24, 'achat', 'Commande n° 1503', 'commande:1503', 'valide', '2026-09-07 17:24:21' + INTERVAL @decalage DAY),
(1048, 1, 'bonus_niveau', 'Bonus Rat de bibliothèque +5 % (commande n° 1503)', 'commande:1503', 'valide', '2026-09-07 17:24:21' + INTERVAL @decalage DAY),
(1048, -100, 'depense', 'Bon de 5,00 € (commande n° 1504)', 'commande:1504', 'valide', '2026-09-09 10:35:32' + INTERVAL @decalage DAY),
(1048, 24, 'achat', 'Commande n° 1504', 'commande:1504', 'valide', '2026-09-09 10:35:32' + INTERVAL @decalage DAY),
(1048, 1, 'bonus_niveau', 'Bonus Rat de bibliothèque +5 % (commande n° 1504)', 'commande:1504', 'valide', '2026-09-09 10:35:32' + INTERVAL @decalage DAY),
(1048, 36, 'achat', 'Commande n° 1505', 'commande:1505', 'valide', '2026-09-14 16:16:38' + INTERVAL @decalage DAY),
(1048, 1, 'bonus_niveau', 'Bonus Rat de bibliothèque +5 % (commande n° 1505)', 'commande:1505', 'valide', '2026-09-14 16:16:38' + INTERVAL @decalage DAY),
(1049, 84, 'achat', 'Commande n° 1506', 'commande:1506', 'valide', '2025-01-04 14:53:23' + INTERVAL @decalage DAY),
(1049, 20, 'premier_achat', 'Bonus premier achat', 'commande:1506', 'valide', '2025-01-04 14:53:23' + INTERVAL @decalage DAY),
(1049, 5, 'badge', 'Badge « Lecteur fidèle » bronze', 'lecteur_fidele:1', 'valide', '2025-01-04 14:53:23' + INTERVAL @decalage DAY),
(1049, 10, 'achat', 'Commande n° 1507', 'commande:1507', 'valide', '2025-02-12 16:11:43' + INTERVAL @decalage DAY),
(1049, 22, 'achat', 'Commande n° 1508', 'commande:1508', 'valide', '2025-03-12 15:52:31' + INTERVAL @decalage DAY),
(1049, 45, 'achat', 'Commande n° 1509', 'commande:1509', 'valide', '2025-04-14 16:25:59' + INTERVAL @decalage DAY),
(1049, 15, 'badge', 'Badge « Lecteur fidèle » argent', 'lecteur_fidele:2', 'valide', '2025-04-14 16:25:59' + INTERVAL @decalage DAY),
(1049, -100, 'depense', 'Bon de 5,00 € (commande n° 1510)', 'commande:1510', 'valide', '2025-04-25 22:30:12' + INTERVAL @decalage DAY),
(1049, 10, 'achat', 'Commande n° 1510', 'commande:1510', 'valide', '2025-04-25 22:30:12' + INTERVAL @decalage DAY),
(1049, -100, 'depense', 'Bon de 5,00 € (commande n° 1511)', 'commande:1511', 'valide', '2025-05-03 20:56:58' + INTERVAL @decalage DAY),
(1049, 98, 'achat', 'Commande n° 1511', 'commande:1511', 'valide', '2025-05-03 20:56:58' + INTERVAL @decalage DAY),
(1049, 21, 'achat', 'Commande n° 1512', 'commande:1512', 'valide', '2025-05-30 10:03:01' + INTERVAL @decalage DAY),
(1049, 93, 'achat', 'Commande n° 1513', 'commande:1513', 'valide', '2025-07-17 17:01:59' + INTERVAL @decalage DAY),
(1049, 13, 'achat', 'Commande n° 1514', 'commande:1514', 'valide', '2025-07-21 16:17:36' + INTERVAL @decalage DAY),
(1049, 65, 'achat', 'Commande n° 1515', 'commande:1515', 'valide', '2025-08-05 21:15:59' + INTERVAL @decalage DAY),
(1049, 1, 'bonus_niveau', 'Bonus Dévoreur de pages +3 % (commande n° 1515)', 'commande:1515', 'valide', '2025-08-05 21:15:59' + INTERVAL @decalage DAY),
(1049, 30, 'badge', 'Badge « Explorateur »', 'explorateur:1', 'valide', '2025-08-05 21:15:59' + INTERVAL @decalage DAY),
(1049, 5, 'avis', 'Avis sur « Une brève histoire du temps »', 'livre:1010', 'valide', '2025-08-09 19:17:36' + INTERVAL @decalage DAY),
(1049, 10, 'premier_avis', 'Bonus premier avis validé', 'premier', 'valide', '2025-08-11 23:17:36' + INTERVAL @decalage DAY),
(1049, 5, 'badge', 'Badge « Plume critique » bronze', 'plume_critique:1', 'valide', '2025-08-11 23:17:36' + INTERVAL @decalage DAY),
(1049, 5, 'avis', 'Avis sur « L''Étranger »', 'livre:4', 'valide', '2025-09-14 23:15:59' + INTERVAL @decalage DAY),
(1049, 65, 'achat', 'Commande n° 1516', 'commande:1516', 'valide', '2025-09-17 19:12:42' + INTERVAL @decalage DAY),
(1049, 1, 'bonus_niveau', 'Bonus Dévoreur de pages +3 % (commande n° 1516)', 'commande:1516', 'valide', '2025-09-17 19:12:42' + INTERVAL @decalage DAY),
(1049, 43, 'achat', 'Commande n° 1517', 'commande:1517', 'valide', '2025-10-15 18:48:32' + INTERVAL @decalage DAY),
(1049, 1, 'bonus_niveau', 'Bonus Dévoreur de pages +3 % (commande n° 1517)', 'commande:1517', 'valide', '2025-10-15 18:48:32' + INTERVAL @decalage DAY),
(1049, 92, 'achat', 'Commande n° 1518', 'commande:1518', 'valide', '2025-11-10 09:25:28' + INTERVAL @decalage DAY),
(1049, 2, 'bonus_niveau', 'Bonus Dévoreur de pages +3 % (commande n° 1518)', 'commande:1518', 'valide', '2025-11-10 09:25:28' + INTERVAL @decalage DAY),
(1049, 30, 'badge', 'Badge « Collectionneur »', 'collectionneur:1', 'valide', '2025-11-10 09:25:28' + INTERVAL @decalage DAY),
(1049, 27, 'achat', 'Commande n° 1519', 'commande:1519', 'valide', '2025-11-12 17:49:20' + INTERVAL @decalage DAY),
(1049, 5, 'avis', 'Avis sur « Thérèse Raquin »', 'livre:5', 'valide', '2025-12-17 12:25:28' + INTERVAL @decalage DAY),
(1049, -400, 'depense', 'Bon de 21,50 € (commande n° 1520)', 'commande:1520', 'valide', '2025-12-27 09:04:02' + INTERVAL @decalage DAY),
(1049, 60, 'achat', 'Commande n° 1520', 'commande:1520', 'valide', '2025-12-27 09:04:02' + INTERVAL @decalage DAY),
(1049, 1, 'bonus_niveau', 'Bonus Dévoreur de pages +3 % (commande n° 1520)', 'commande:1520', 'valide', '2025-12-27 09:04:02' + INTERVAL @decalage DAY),
(1049, 30, 'badge', 'Badge « Lecteur fidèle » or', 'lecteur_fidele:3', 'valide', '2025-12-27 09:04:02' + INTERVAL @decalage DAY),
(1049, 56, 'achat', 'Commande n° 1521', 'commande:1521', 'valide', '2026-02-06 20:47:40' + INTERVAL @decalage DAY),
(1049, 2, 'bonus_niveau', 'Bonus Rat de bibliothèque +5 % (commande n° 1521)', 'commande:1521', 'valide', '2026-02-06 20:47:40' + INTERVAL @decalage DAY),
(1049, 30, 'badge', 'Badge « Un an parmi nous »', 'un_an:1', 'valide', '2026-02-06 20:47:40' + INTERVAL @decalage DAY),
(1049, 77, 'achat', 'Commande n° 1522', 'commande:1522', 'valide', '2026-04-04 10:26:41' + INTERVAL @decalage DAY),
(1049, 3, 'bonus_niveau', 'Bonus Rat de bibliothèque +5 % (commande n° 1522)', 'commande:1522', 'valide', '2026-04-04 10:26:41' + INTERVAL @decalage DAY),
(1049, 28, 'achat', 'Commande n° 1523', 'commande:1523', 'valide', '2026-04-16 08:53:10' + INTERVAL @decalage DAY),
(1049, 1, 'bonus_niveau', 'Bonus Rat de bibliothèque +5 % (commande n° 1523)', 'commande:1523', 'valide', '2026-04-16 08:53:10' + INTERVAL @decalage DAY),
(1049, 43, 'achat', 'Commande n° 1524', 'commande:1524', 'valide', '2026-04-20 21:32:52' + INTERVAL @decalage DAY),
(1049, 2, 'bonus_niveau', 'Bonus Rat de bibliothèque +5 % (commande n° 1524)', 'commande:1524', 'valide', '2026-04-20 21:32:52' + INTERVAL @decalage DAY),
(1049, 52, 'achat', 'Commande n° 1525', 'commande:1525', 'valide', '2026-05-24 18:33:05' + INTERVAL @decalage DAY),
(1049, 2, 'bonus_niveau', 'Bonus Rat de bibliothèque +5 % (commande n° 1525)', 'commande:1525', 'valide', '2026-05-24 18:33:05' + INTERVAL @decalage DAY),
(1049, 51, 'achat', 'Commande n° 1526', 'commande:1526', 'valide', '2026-06-11 14:59:41' + INTERVAL @decalage DAY),
(1049, 2, 'bonus_niveau', 'Bonus Rat de bibliothèque +5 % (commande n° 1526)', 'commande:1526', 'valide', '2026-06-11 14:59:41' + INTERVAL @decalage DAY),
(1049, 34, 'achat', 'Commande n° 1527', 'commande:1527', 'valide', '2026-07-31 19:42:58' + INTERVAL @decalage DAY),
(1049, 1, 'bonus_niveau', 'Bonus Rat de bibliothèque +5 % (commande n° 1527)', 'commande:1527', 'valide', '2026-07-31 19:42:58' + INTERVAL @decalage DAY),
(1049, 5, 'avis', 'Avis sur « Pixel et Moustache - Tome 1 : Le chat qui voulait voler »', 'livre:1017', 'valide', '2026-08-11 20:42:58' + INTERVAL @decalage DAY),
(1049, 56, 'achat', 'Commande n° 1528', 'commande:1528', 'valide', '2026-08-20 15:48:30' + INTERVAL @decalage DAY),
(1049, 2, 'bonus_niveau', 'Bonus Rat de bibliothèque +5 % (commande n° 1528)', 'commande:1528', 'valide', '2026-08-20 15:48:30' + INTERVAL @decalage DAY),
(1049, 10, 'presence', 'Présence à « Troc de livres de la rentrée »', 'inscription:1081', 'valide', '2026-09-19 10:00:00' + INTERVAL @decalage DAY),
(1049, 5, 'badge', 'Badge « Habitué des rencontres » bronze', 'habitue_rencontres:1', 'valide', '2026-09-19 10:00:00' + INTERVAL @decalage DAY),
(1050, 37, 'achat', 'Commande n° 1529', 'commande:1529', 'valide', '2024-09-10 12:42:48' + INTERVAL @decalage DAY),
(1050, 20, 'premier_achat', 'Bonus premier achat', 'commande:1529', 'valide', '2024-09-10 12:42:48' + INTERVAL @decalage DAY),
(1050, 5, 'badge', 'Badge « Lecteur fidèle » bronze', 'lecteur_fidele:1', 'valide', '2024-09-10 12:42:48' + INTERVAL @decalage DAY),
(1050, 13, 'achat', 'Commande n° 1530', 'commande:1530', 'valide', '2024-09-10 18:39:07' + INTERVAL @decalage DAY),
(1050, 27, 'achat', 'Commande n° 1531', 'commande:1531', 'valide', '2024-09-17 12:48:09' + INTERVAL @decalage DAY),
(1050, 15, 'badge', 'Badge « Lecteur fidèle » argent', 'lecteur_fidele:2', 'valide', '2024-09-17 12:48:09' + INTERVAL @decalage DAY),
(1050, 5, 'avis', 'Avis sur « Avant toi »', 'livre:1034', 'valide', '2024-09-19 03:39:07' + INTERVAL @decalage DAY),
(1050, 10, 'premier_avis', 'Bonus premier avis validé', 'premier', 'valide', '2024-09-21 06:39:07' + INTERVAL @decalage DAY),
(1050, 5, 'badge', 'Badge « Plume critique » bronze', 'plume_critique:1', 'valide', '2024-09-21 06:39:07' + INTERVAL @decalage DAY),
(1050, 68, 'achat', 'Commande n° 1532', 'commande:1532', 'valide', '2024-09-22 13:40:40' + INTERVAL @decalage DAY),
(1050, 5, 'avis', 'Avis sur « Bescherelle - L''anglais pour tous »', 'livre:1012', 'valide', '2024-09-27 13:48:09' + INTERVAL @decalage DAY),
(1050, 5, 'avis', 'Avis sur « Les Fleurs du mal »', 'livre:1002', 'valide', '2024-10-07 21:39:07' + INTERVAL @decalage DAY),
(1050, -200, 'depense', 'Bon de 10,50 € (commande n° 1533)', 'commande:1533', 'valide', '2024-10-27 14:24:27' + INTERVAL @decalage DAY),
(1050, 15, 'achat', 'Commande n° 1533', 'commande:1533', 'valide', '2024-10-27 14:24:27' + INTERVAL @decalage DAY),
(1050, 10, 'achat', 'Commande n° 1534', 'commande:1534', 'valide', '2024-12-09 08:16:04' + INTERVAL @decalage DAY),
(1050, 18, 'achat', 'Commande n° 1535', 'commande:1535', 'valide', '2024-12-12 17:30:10' + INTERVAL @decalage DAY),
(1050, 6, 'achat', 'Commande n° 1536', 'commande:1536', 'valide', '2024-12-16 11:22:21' + INTERVAL @decalage DAY),
(1050, 30, 'badge', 'Badge « Explorateur »', 'explorateur:1', 'valide', '2024-12-16 11:22:21' + INTERVAL @decalage DAY),
(1050, 10, 'achat', 'Commande n° 1537', 'commande:1537', 'valide', '2024-12-22 13:40:17' + INTERVAL @decalage DAY),
(1050, -100, 'depense', 'Bon de 5,00 € (commande n° 1538)', 'commande:1538', 'valide', '2024-12-23 18:35:11' + INTERVAL @decalage DAY),
(1050, 23, 'achat', 'Commande n° 1538', 'commande:1538', 'valide', '2024-12-23 18:35:11' + INTERVAL @decalage DAY),
(1050, 30, 'badge', 'Badge « Collectionneur »', 'collectionneur:1', 'valide', '2024-12-23 18:35:11' + INTERVAL @decalage DAY),
(1050, 45, 'achat', 'Commande n° 1539', 'commande:1539', 'valide', '2025-01-07 20:48:09' + INTERVAL @decalage DAY),
(1050, 60, 'achat', 'Commande n° 1540', 'commande:1540', 'valide', '2025-01-10 13:07:42' + INTERVAL @decalage DAY),
(1050, 1, 'bonus_niveau', 'Bonus Dévoreur de pages +3 % (commande n° 1540)', 'commande:1540', 'valide', '2025-01-10 13:07:42' + INTERVAL @decalage DAY),
(1050, 30, 'badge', 'Badge « Lecteur fidèle » or', 'lecteur_fidele:3', 'valide', '2025-01-10 13:07:42' + INTERVAL @decalage DAY),
(1050, 20, 'achat', 'Commande n° 1541', 'commande:1541', 'valide', '2025-01-10 16:52:49' + INTERVAL @decalage DAY),
(1050, 20, 'achat', 'Commande n° 1542', 'commande:1542', 'valide', '2025-01-12 12:16:04' + INTERVAL @decalage DAY),
(1050, 5, 'avis', 'Avis sur « Dune - Tome 1 »', 'livre:1033', 'valide', '2025-01-11 21:48:09' + INTERVAL @decalage DAY),
(1050, -100, 'depense', 'Bon de 5,00 € (commande n° 1543)', 'commande:1543', 'valide', '2025-01-21 13:45:41' + INTERVAL @decalage DAY),
(1050, 13, 'achat', 'Commande n° 1543', 'commande:1543', 'valide', '2025-01-21 13:45:41' + INTERVAL @decalage DAY),
(1050, 5, 'avis', 'Avis sur « 1984 »', 'livre:1025', 'valide', '2025-01-28 23:45:41' + INTERVAL @decalage DAY),
(1050, -100, 'depense', 'Bon de 5,00 € (commande n° 1544)', 'commande:1544', 'valide', '2025-03-02 19:25:52' + INTERVAL @decalage DAY);

INSERT IGNORE INTO point_mouvement (id_user, points, type, libelle, reference, statut, date_mouvement) VALUES
(1050, 17, 'achat', 'Commande n° 1544', 'commande:1544', 'valide', '2025-03-02 19:25:52' + INTERVAL @decalage DAY),
(1050, 23, 'achat', 'Commande n° 1545', 'commande:1545', 'valide', '2025-03-06 16:26:46' + INTERVAL @decalage DAY),
(1050, 5, 'achat', 'Commande n° 1546', 'commande:1546', 'valide', '2025-03-09 10:02:06' + INTERVAL @decalage DAY),
(1050, -100, 'depense', 'Bon de 5,00 € (commande n° 1547)', 'commande:1547', 'valide', '2025-03-23 20:31:48' + INTERVAL @decalage DAY),
(1050, 69, 'achat', 'Commande n° 1547', 'commande:1547', 'valide', '2025-03-23 20:31:48' + INTERVAL @decalage DAY),
(1050, 2, 'bonus_niveau', 'Bonus Dévoreur de pages +3 % (commande n° 1547)', 'commande:1547', 'valide', '2025-03-23 20:31:48' + INTERVAL @decalage DAY),
(1050, 29, 'achat', 'Commande n° 1548', 'commande:1548', 'valide', '2025-04-11 17:30:55' + INTERVAL @decalage DAY),
(1050, 9, 'achat', 'Commande n° 1549', 'commande:1549', 'valide', '2025-04-22 15:55:14' + INTERVAL @decalage DAY),
(1050, 23, 'achat', 'Commande n° 1550', 'commande:1550', 'valide', '2025-04-24 15:44:53' + INTERVAL @decalage DAY),
(1050, 5, 'avis', 'Avis sur « Je sais cuisiner »', 'livre:1013', 'valide', '2025-05-08 00:30:55' + INTERVAL @decalage DAY),
(1050, 13, 'achat', 'Commande n° 1551', 'commande:1551', 'valide', '2025-05-24 12:47:22' + INTERVAL @decalage DAY),
(1050, -100, 'depense', 'Bon de 5,00 € (commande n° 1552)', 'commande:1552', 'valide', '2025-06-06 13:54:42' + INTERVAL @decalage DAY),
(1050, 15, 'achat', 'Commande n° 1552', 'commande:1552', 'valide', '2025-06-06 13:54:42' + INTERVAL @decalage DAY),
(1050, 17, 'achat', 'Commande n° 1553', 'commande:1553', 'valide', '2025-06-16 14:02:57' + INTERVAL @decalage DAY),
(1050, 8, 'achat', 'Commande n° 1554', 'commande:1554', 'valide', '2025-07-16 13:33:19' + INTERVAL @decalage DAY),
(1050, 36, 'achat', 'Commande n° 1555', 'commande:1555', 'valide', '2025-07-28 18:06:21' + INTERVAL @decalage DAY),
(1050, 1, 'bonus_niveau', 'Bonus Dévoreur de pages +3 % (commande n° 1555)', 'commande:1555', 'valide', '2025-07-28 18:06:21' + INTERVAL @decalage DAY),
(1050, -100, 'depense', 'Bon de 5,00 € (commande n° 1556)', 'commande:1556', 'valide', '2025-08-20 20:40:49' + INTERVAL @decalage DAY),
(1050, 40, 'achat', 'Commande n° 1556', 'commande:1556', 'valide', '2025-08-20 20:40:49' + INTERVAL @decalage DAY),
(1050, 1, 'bonus_niveau', 'Bonus Dévoreur de pages +3 % (commande n° 1556)', 'commande:1556', 'valide', '2025-08-20 20:40:49' + INTERVAL @decalage DAY),
(1050, 6, 'achat', 'Commande n° 1557', 'commande:1557', 'valide', '2025-09-02 21:11:20' + INTERVAL @decalage DAY),
(1050, 30, 'badge', 'Badge « Un an parmi nous »', 'un_an:1', 'valide', '2025-09-02 21:11:20' + INTERVAL @decalage DAY),
(1050, 16, 'achat', 'Commande n° 1558', 'commande:1558', 'valide', '2025-09-07 12:46:37' + INTERVAL @decalage DAY),
(1050, 28, 'achat', 'Commande n° 1559', 'commande:1559', 'valide', '2025-09-10 08:32:24' + INTERVAL @decalage DAY),
(1050, 1, 'bonus_niveau', 'Bonus Rat de bibliothèque +5 % (commande n° 1559)', 'commande:1559', 'valide', '2025-09-10 08:32:24' + INTERVAL @decalage DAY),
(1050, 29, 'achat', 'Commande n° 1560', 'commande:1560', 'valide', '2025-09-10 13:30:43' + INTERVAL @decalage DAY),
(1050, 1, 'bonus_niveau', 'Bonus Rat de bibliothèque +5 % (commande n° 1560)', 'commande:1560', 'valide', '2025-09-10 13:30:43' + INTERVAL @decalage DAY),
(1050, 12, 'achat', 'Commande n° 1561', 'commande:1561', 'valide', '2025-09-13 11:59:39' + INTERVAL @decalage DAY),
(1050, -100, 'depense', 'Bon de 5,00 € (commande n° 1562)', 'commande:1562', 'valide', '2025-09-17 13:41:31' + INTERVAL @decalage DAY),
(1050, 15, 'achat', 'Commande n° 1562', 'commande:1562', 'valide', '2025-09-17 13:41:31' + INTERVAL @decalage DAY),
(1050, 7, 'achat', 'Commande n° 1563', 'commande:1563', 'valide', '2025-09-21 10:35:10' + INTERVAL @decalage DAY),
(1050, 23, 'achat', 'Commande n° 1564', 'commande:1564', 'valide', '2025-09-21 12:26:36' + INTERVAL @decalage DAY),
(1050, 1, 'bonus_niveau', 'Bonus Rat de bibliothèque +5 % (commande n° 1564)', 'commande:1564', 'valide', '2025-09-21 12:26:36' + INTERVAL @decalage DAY),
(1050, 26, 'achat', 'Commande n° 1565', 'commande:1565', 'valide', '2025-09-22 20:28:01' + INTERVAL @decalage DAY),
(1050, 1, 'bonus_niveau', 'Bonus Rat de bibliothèque +5 % (commande n° 1565)', 'commande:1565', 'valide', '2025-09-22 20:28:01' + INTERVAL @decalage DAY),
(1050, 37, 'achat', 'Commande n° 1566', 'commande:1566', 'valide', '2025-09-30 20:45:08' + INTERVAL @decalage DAY),
(1050, 1, 'bonus_niveau', 'Bonus Rat de bibliothèque +5 % (commande n° 1566)', 'commande:1566', 'valide', '2025-09-30 20:45:08' + INTERVAL @decalage DAY),
(1050, -200, 'depense', 'Bon de 10,50 € (commande n° 1567)', 'commande:1567', 'valide', '2025-10-04 20:15:06' + INTERVAL @decalage DAY),
(1050, 64, 'achat', 'Commande n° 1567', 'commande:1567', 'valide', '2025-10-04 20:15:06' + INTERVAL @decalage DAY),
(1050, 3, 'bonus_niveau', 'Bonus Rat de bibliothèque +5 % (commande n° 1567)', 'commande:1567', 'valide', '2025-10-04 20:15:06' + INTERVAL @decalage DAY),
(1050, 26, 'achat', 'Commande n° 1568', 'commande:1568', 'valide', '2025-10-12 09:38:18' + INTERVAL @decalage DAY),
(1050, 1, 'bonus_niveau', 'Bonus Rat de bibliothèque +5 % (commande n° 1568)', 'commande:1568', 'valide', '2025-10-12 09:38:18' + INTERVAL @decalage DAY),
(1050, 26, 'achat', 'Commande n° 1569', 'commande:1569', 'valide', '2025-10-26 10:14:38' + INTERVAL @decalage DAY),
(1050, 1, 'bonus_niveau', 'Bonus Rat de bibliothèque +5 % (commande n° 1569)', 'commande:1569', 'valide', '2025-10-26 10:14:38' + INTERVAL @decalage DAY),
(1050, 5, 'avis', 'Avis sur « Hitchcock / Truffaut - Édition définitive »', 'livre:1041', 'valide', '2025-11-04 02:45:08' + INTERVAL @decalage DAY),
(1050, -100, 'depense', 'Bon de 5,00 € (commande n° 1570)', 'commande:1570', 'valide', '2025-11-08 11:11:15' + INTERVAL @decalage DAY),
(1050, 41, 'achat', 'Commande n° 1570', 'commande:1570', 'valide', '2025-11-08 11:11:15' + INTERVAL @decalage DAY),
(1050, 2, 'bonus_niveau', 'Bonus Rat de bibliothèque +5 % (commande n° 1570)', 'commande:1570', 'valide', '2025-11-08 11:11:15' + INTERVAL @decalage DAY),
(1050, 15, 'achat', 'Commande n° 1571', 'commande:1571', 'valide', '2025-11-11 12:37:27' + INTERVAL @decalage DAY),
(1050, 5, 'avis', 'Avis sur « Watchmen »', 'livre:1023', 'valide', '2025-12-01 20:11:15' + INTERVAL @decalage DAY),
(1050, 19, 'achat', 'Commande n° 1572', 'commande:1572', 'valide', '2025-12-06 14:16:40' + INTERVAL @decalage DAY),
(1050, 5, 'avis', 'Avis sur « Bleach - Tome 01 »', 'livre:3', 'valide', '2025-12-08 12:37:27' + INTERVAL @decalage DAY),
(1050, -100, 'depense', 'Bon de 5,00 € (commande n° 1573)', 'commande:1573', 'valide', '2025-12-10 18:04:44' + INTERVAL @decalage DAY),
(1050, 12, 'achat', 'Commande n° 1573', 'commande:1573', 'valide', '2025-12-10 18:04:44' + INTERVAL @decalage DAY),
(1050, 21, 'achat', 'Commande n° 1574', 'commande:1574', 'valide', '2025-12-18 15:17:36' + INTERVAL @decalage DAY),
(1050, 1, 'bonus_niveau', 'Bonus Rat de bibliothèque +5 % (commande n° 1574)', 'commande:1574', 'valide', '2025-12-18 15:17:36' + INTERVAL @decalage DAY),
(1050, 30, 'achat', 'Commande n° 1575', 'commande:1575', 'valide', '2026-01-06 12:43:36' + INTERVAL @decalage DAY),
(1050, 1, 'bonus_niveau', 'Bonus Rat de bibliothèque +5 % (commande n° 1575)', 'commande:1575', 'valide', '2026-01-06 12:43:36' + INTERVAL @decalage DAY),
(1050, 14, 'achat', 'Commande n° 1576', 'commande:1576', 'valide', '2026-01-08 11:29:05' + INTERVAL @decalage DAY),
(1050, 16, 'achat', 'Commande n° 1577', 'commande:1577', 'valide', '2026-01-10 18:37:59' + INTERVAL @decalage DAY),
(1050, 13, 'achat', 'Commande n° 1578', 'commande:1578', 'valide', '2026-01-13 16:49:42' + INTERVAL @decalage DAY),
(1050, 7, 'achat', 'Commande n° 1579', 'commande:1579', 'valide', '2026-01-19 20:52:54' + INTERVAL @decalage DAY),
(1050, -100, 'depense', 'Bon de 5,00 € (commande n° 1580)', 'commande:1580', 'valide', '2026-01-28 08:05:23' + INTERVAL @decalage DAY),
(1050, 29, 'achat', 'Commande n° 1580', 'commande:1580', 'valide', '2026-01-28 08:05:23' + INTERVAL @decalage DAY),
(1050, 1, 'bonus_niveau', 'Bonus Rat de bibliothèque +5 % (commande n° 1580)', 'commande:1580', 'valide', '2026-01-28 08:05:23' + INTERVAL @decalage DAY),
(1050, 38, 'achat', 'Commande n° 1581', 'commande:1581', 'valide', '2026-02-15 15:18:00' + INTERVAL @decalage DAY),
(1050, 1, 'bonus_niveau', 'Bonus Rat de bibliothèque +5 % (commande n° 1581)', 'commande:1581', 'valide', '2026-02-15 15:18:00' + INTERVAL @decalage DAY),
(1050, 44, 'achat', 'Commande n° 1582', 'commande:1582', 'valide', '2026-03-10 11:58:48' + INTERVAL @decalage DAY),
(1050, 2, 'bonus_niveau', 'Bonus Rat de bibliothèque +5 % (commande n° 1582)', 'commande:1582', 'valide', '2026-03-10 11:58:48' + INTERVAL @decalage DAY),
(1050, -100, 'depense', 'Bon de 5,00 € (commande n° 1583)', 'commande:1583', 'valide', '2026-03-26 08:29:08' + INTERVAL @decalage DAY),
(1050, 41, 'achat', 'Commande n° 1583', 'commande:1583', 'valide', '2026-03-26 08:29:08' + INTERVAL @decalage DAY),
(1050, 2, 'bonus_niveau', 'Bonus Rat de bibliothèque +5 % (commande n° 1583)', 'commande:1583', 'valide', '2026-03-26 08:29:08' + INTERVAL @decalage DAY),
(1050, 22, 'achat', 'Commande n° 1584', 'commande:1584', 'valide', '2026-04-06 08:29:06' + INTERVAL @decalage DAY),
(1050, 1, 'bonus_niveau', 'Bonus Rat de bibliothèque +5 % (commande n° 1584)', 'commande:1584', 'valide', '2026-04-06 08:29:06' + INTERVAL @decalage DAY),
(1050, 6, 'achat', 'Commande n° 1585', 'commande:1585', 'valide', '2026-05-17 22:49:49' + INTERVAL @decalage DAY),
(1050, 28, 'achat', 'Commande n° 1586', 'commande:1586', 'valide', '2026-06-02 12:51:53' + INTERVAL @decalage DAY),
(1050, 1, 'bonus_niveau', 'Bonus Rat de bibliothèque +5 % (commande n° 1586)', 'commande:1586', 'valide', '2026-06-02 12:51:53' + INTERVAL @decalage DAY),
(1050, 12, 'achat', 'Commande n° 1587', 'commande:1587', 'valide', '2026-06-02 19:41:27' + INTERVAL @decalage DAY),
(1050, -100, 'depense', 'Bon de 5,00 € (commande n° 1588)', 'commande:1588', 'valide', '2026-06-12 09:39:04' + INTERVAL @decalage DAY),
(1050, 42, 'achat', 'Commande n° 1588', 'commande:1588', 'valide', '2026-06-12 09:39:04' + INTERVAL @decalage DAY),
(1050, 2, 'bonus_niveau', 'Bonus Rat de bibliothèque +5 % (commande n° 1588)', 'commande:1588', 'valide', '2026-06-12 09:39:04' + INTERVAL @decalage DAY),
(1050, -100, 'depense', 'Bon de 5,00 € (commande n° 1589)', 'commande:1589', 'valide', '2026-06-25 09:26:40' + INTERVAL @decalage DAY),
(1050, 13, 'achat', 'Commande n° 1589', 'commande:1589', 'valide', '2026-06-25 09:26:40' + INTERVAL @decalage DAY),
(1050, 11, 'achat', 'Commande n° 1590', 'commande:1590', 'valide', '2026-07-10 17:29:23' + INTERVAL @decalage DAY),
(1050, 22, 'achat', 'Commande n° 1591', 'commande:1591', 'valide', '2026-07-27 09:41:09' + INTERVAL @decalage DAY),
(1050, 1, 'bonus_niveau', 'Bonus Rat de bibliothèque +5 % (commande n° 1591)', 'commande:1591', 'valide', '2026-07-27 09:41:09' + INTERVAL @decalage DAY),
(1050, 15, 'achat', 'Commande n° 1592', 'commande:1592', 'valide', '2026-08-13 19:28:09' + INTERVAL @decalage DAY),
(1050, 27, 'achat', 'Commande n° 1593', 'commande:1593', 'valide', '2026-08-21 16:52:43' + INTERVAL @decalage DAY),
(1050, 1, 'bonus_niveau', 'Bonus Rat de bibliothèque +5 % (commande n° 1593)', 'commande:1593', 'valide', '2026-08-21 16:52:43' + INTERVAL @decalage DAY),
(1050, 13, 'achat', 'Commande n° 1594', 'commande:1594', 'valide', '2026-08-23 17:10:55' + INTERVAL @decalage DAY),
(1050, 46, 'achat', 'Commande n° 1595', 'commande:1595', 'valide', '2026-09-09 12:46:40' + INTERVAL @decalage DAY),
(1050, 3, 'bonus_niveau', 'Bonus Bibliophile +8 % (commande n° 1595)', 'commande:1595', 'valide', '2026-09-09 12:46:40' + INTERVAL @decalage DAY),
(1050, 10, 'presence', 'Présence à « Troc de livres de la rentrée »', 'inscription:1077', 'valide', '2026-09-19 10:00:00' + INTERVAL @decalage DAY),
(1050, 5, 'badge', 'Badge « Habitué des rencontres » bronze', 'habitue_rencontres:1', 'valide', '2026-09-19 10:00:00' + INTERVAL @decalage DAY),
(1050, 13, 'achat', 'Commande n° 1596', 'commande:1596', 'valide', '2026-09-28 13:59:41' + INTERVAL @decalage DAY),
(1050, 1, 'bonus_niveau', 'Bonus Bibliophile +8 % (commande n° 1596)', 'commande:1596', 'valide', '2026-09-28 13:59:41' + INTERVAL @decalage DAY),
(1050, 13, 'achat', 'Commande n° 1597', 'commande:1597', 'valide', '2026-09-30 22:44:25' + INTERVAL @decalage DAY),
(1050, 1, 'bonus_niveau', 'Bonus Bibliophile +8 % (commande n° 1597)', 'commande:1597', 'valide', '2026-09-30 22:44:25' + INTERVAL @decalage DAY),
(1050, -100, 'depense', 'Bon de 5,00 € (commande n° 1598)', 'commande:1598', 'valide', '2026-10-23 08:05:12' + INTERVAL @decalage DAY),
(1050, 34, 'achat', 'Commande n° 1598', 'commande:1598', 'valide', '2026-10-23 08:05:12' + INTERVAL @decalage DAY),
(1050, 2, 'bonus_niveau', 'Bonus Bibliophile +8 % (commande n° 1598)', 'commande:1598', 'valide', '2026-10-23 08:05:12' + INTERVAL @decalage DAY);

INSERT IGNORE INTO badge_obtenu (id_user, code, palier, date_obtention, vu) VALUES
(1003, 'habitue_rencontres', 1, '2026-09-19 12:00:00' + INTERVAL @decalage DAY, 1),
(1008, 'lecteur_fidele', 1, '2026-03-22 12:26:38' + INTERVAL @decalage DAY, 1),
(1008, 'habitue_rencontres', 1, '2026-09-19 10:00:00' + INTERVAL @decalage DAY, 1),
(1009, 'lecteur_fidele', 1, '2026-08-17 19:59:53' + INTERVAL @decalage DAY, 1),
(1010, 'lecteur_fidele', 1, '2026-07-27 20:06:35' + INTERVAL @decalage DAY, 1),
(1010, 'plume_critique', 1, '2026-08-03 07:06:35' + INTERVAL @decalage DAY, 1),
(1011, 'lecteur_fidele', 1, '2026-01-28 21:14:18' + INTERVAL @decalage DAY, 1),
(1011, 'plume_critique', 1, '2026-07-08 17:47:43' + INTERVAL @decalage DAY, 1),
(1011, 'lecteur_fidele', 2, '2026-07-10 16:52:14' + INTERVAL @decalage DAY, 1),
(1011, 'habitue_rencontres', 1, '2026-09-19 11:00:00' + INTERVAL @decalage DAY, 1),
(1012, 'lecteur_fidele', 1, '2025-09-18 10:40:25' + INTERVAL @decalage DAY, 1),
(1013, 'lecteur_fidele', 1, '2026-06-21 09:08:45' + INTERVAL @decalage DAY, 1),
(1013, 'plume_critique', 1, '2026-06-28 21:08:45' + INTERVAL @decalage DAY, 1),
(1014, 'lecteur_fidele', 1, '2026-08-18 19:52:45' + INTERVAL @decalage DAY, 1),
(1014, 'plume_critique', 1, '2026-09-13 00:52:45' + INTERVAL @decalage DAY, 1),
(1015, 'lecteur_fidele', 1, '2026-01-23 10:27:17' + INTERVAL @decalage DAY, 1),
(1015, 'plume_critique', 1, '2026-02-21 18:27:17' + INTERVAL @decalage DAY, 1),
(1015, 'habitue_rencontres', 1, '2026-09-19 11:00:00' + INTERVAL @decalage DAY, 1),
(1016, 'lecteur_fidele', 1, '2026-09-30 18:40:55' + INTERVAL @decalage DAY, 1),
(1017, 'lecteur_fidele', 1, '2026-06-09 19:24:10' + INTERVAL @decalage DAY, 1),
(1017, 'lecteur_fidele', 2, '2026-08-29 08:27:39' + INTERVAL @decalage DAY, 1),
(1017, 'plume_critique', 1, '2026-09-13 15:27:39' + INTERVAL @decalage DAY, 1),
(1017, 'habitue_rencontres', 1, '2026-09-19 12:00:00' + INTERVAL @decalage DAY, 1),
(1018, 'lecteur_fidele', 1, '2026-06-19 15:05:24' + INTERVAL @decalage DAY, 1),
(1019, 'lecteur_fidele', 1, '2025-11-01 17:15:39' + INTERVAL @decalage DAY, 1),
(1019, 'plume_critique', 1, '2026-07-14 03:59:35' + INTERVAL @decalage DAY, 1),
(1020, 'lecteur_fidele', 1, '2026-07-26 09:34:02' + INTERVAL @decalage DAY, 1),
(1020, 'habitue_rencontres', 1, '2026-09-19 10:00:00' + INTERVAL @decalage DAY, 1),
(1020, 'lecteur_fidele', 2, '2026-10-08 09:59:15' + INTERVAL @decalage DAY, 1),
(1021, 'lecteur_fidele', 1, '2026-03-29 19:24:55' + INTERVAL @decalage DAY, 1),
(1021, 'plume_critique', 1, '2026-04-12 04:24:55' + INTERVAL @decalage DAY, 1),
(1021, 'lecteur_fidele', 2, '2026-07-31 13:44:01' + INTERVAL @decalage DAY, 1),
(1022, 'lecteur_fidele', 1, '2026-01-20 18:47:53' + INTERVAL @decalage DAY, 1),
(1022, 'habitue_rencontres', 1, '2026-09-19 10:00:00' + INTERVAL @decalage DAY, 1),
(1023, 'lecteur_fidele', 1, '2026-02-17 21:53:58' + INTERVAL @decalage DAY, 1),
(1023, 'plume_critique', 1, '2026-05-19 03:26:08' + INTERVAL @decalage DAY, 1),
(1023, 'habitue_rencontres', 1, '2026-09-19 10:00:00' + INTERVAL @decalage DAY, 1),
(1024, 'lecteur_fidele', 1, '2026-08-28 10:37:42' + INTERVAL @decalage DAY, 1),
(1024, 'lecteur_fidele', 2, '2026-10-03 14:26:15' + INTERVAL @decalage DAY, 1),
(1025, 'lecteur_fidele', 1, '2025-10-18 09:33:07' + INTERVAL @decalage DAY, 1),
(1025, 'plume_critique', 1, '2025-12-16 22:30:06' + INTERVAL @decalage DAY, 1),
(1025, 'lecteur_fidele', 2, '2026-03-22 09:43:01' + INTERVAL @decalage DAY, 1),
(1025, 'un_an', 1, '2026-09-12 17:41:13' + INTERVAL @decalage DAY, 1),
(1025, 'coeur_genereux', 1, '2026-10-08 19:23:06' + INTERVAL @decalage DAY, 1),
(1026, 'lecteur_fidele', 1, '2025-05-25 22:33:16' + INTERVAL @decalage DAY, 1),
(1026, 'plume_critique', 1, '2025-08-09 03:31:10' + INTERVAL @decalage DAY, 1),
(1026, 'lecteur_fidele', 2, '2026-04-10 20:41:02' + INTERVAL @decalage DAY, 1),
(1026, 'un_an', 1, '2026-04-10 20:41:02' + INTERVAL @decalage DAY, 1),
(1026, 'habitue_rencontres', 1, '2026-09-19 12:00:00' + INTERVAL @decalage DAY, 1),
(1027, 'lecteur_fidele', 1, '2026-03-07 22:37:11' + INTERVAL @decalage DAY, 1),
(1027, 'lecteur_fidele', 2, '2026-04-27 22:32:55' + INTERVAL @decalage DAY, 1),
(1027, 'plume_critique', 1, '2026-05-10 10:32:55' + INTERVAL @decalage DAY, 1),
(1028, 'lecteur_fidele', 1, '2026-03-13 09:07:41' + INTERVAL @decalage DAY, 1),
(1028, 'lecteur_fidele', 2, '2026-05-31 08:06:06' + INTERVAL @decalage DAY, 1),
(1028, 'plume_critique', 1, '2026-08-25 21:52:21' + INTERVAL @decalage DAY, 1),
(1028, 'coeur_genereux', 1, '2026-09-26 17:12:03' + INTERVAL @decalage DAY, 1),
(1028, 'explorateur', 1, '2026-10-09 22:34:29' + INTERVAL @decalage DAY, 1),
(1029, 'lecteur_fidele', 1, '2025-11-05 20:47:52' + INTERVAL @decalage DAY, 1),
(1029, 'plume_critique', 1, '2026-02-11 03:49:39' + INTERVAL @decalage DAY, 1),
(1029, 'lecteur_fidele', 2, '2026-03-15 21:41:25' + INTERVAL @decalage DAY, 1),
(1029, 'explorateur', 1, '2026-06-15 11:06:51' + INTERVAL @decalage DAY, 1),
(1029, 'habitue_rencontres', 1, '2026-09-19 10:00:00' + INTERVAL @decalage DAY, 1),
(1030, 'lecteur_fidele', 1, '2026-06-05 18:22:09' + INTERVAL @decalage DAY, 1),
(1030, 'lecteur_fidele', 2, '2026-08-18 08:27:10' + INTERVAL @decalage DAY, 1),
(1030, 'plume_critique', 1, '2026-09-01 12:55:22' + INTERVAL @decalage DAY, 1),
(1030, 'habitue_rencontres', 1, '2026-09-19 11:00:00' + INTERVAL @decalage DAY, 1),
(1031, 'lecteur_fidele', 1, '2025-07-23 09:30:29' + INTERVAL @decalage DAY, 1),
(1031, 'lecteur_fidele', 2, '2025-11-23 11:39:37' + INTERVAL @decalage DAY, 1),
(1031, 'un_an', 1, '2026-06-24 18:35:16' + INTERVAL @decalage DAY, 1),
(1031, 'plume_critique', 1, '2026-08-02 03:29:50' + INTERVAL @decalage DAY, 1),
(1032, 'lecteur_fidele', 1, '2026-05-26 16:45:00' + INTERVAL @decalage DAY, 1),
(1032, 'lecteur_fidele', 2, '2026-06-13 12:47:14' + INTERVAL @decalage DAY, 1),
(1032, 'habitue_rencontres', 1, '2026-09-19 11:00:00' + INTERVAL @decalage DAY, 1),
(1032, 'plume_critique', 1, '2026-10-07 16:00:34' + INTERVAL @decalage DAY, 1),
(1033, 'lecteur_fidele', 1, '2025-10-17 15:04:41' + INTERVAL @decalage DAY, 1),
(1033, 'lecteur_fidele', 2, '2026-01-20 18:34:31' + INTERVAL @decalage DAY, 1),
(1033, 'habitue_rencontres', 1, '2026-09-19 10:00:00' + INTERVAL @decalage DAY, 1),
(1033, 'un_an', 1, '2026-09-19 10:00:00' + INTERVAL @decalage DAY, 1),
(1033, 'collectionneur', 1, '2026-09-26 12:29:19' + INTERVAL @decalage DAY, 1),
(1033, 'plume_critique', 1, '2026-10-05 23:16:35' + INTERVAL @decalage DAY, 1),
(1034, 'lecteur_fidele', 1, '2026-03-30 11:35:54' + INTERVAL @decalage DAY, 1),
(1034, 'lecteur_fidele', 2, '2026-04-13 16:32:21' + INTERVAL @decalage DAY, 1),
(1034, 'explorateur', 1, '2026-06-03 20:32:04' + INTERVAL @decalage DAY, 1),
(1034, 'plume_critique', 1, '2026-08-05 17:23:57' + INTERVAL @decalage DAY, 1),
(1034, 'collectionneur', 1, '2026-08-23 08:51:32' + INTERVAL @decalage DAY, 1),
(1034, 'habitue_rencontres', 1, '2026-09-19 11:00:00' + INTERVAL @decalage DAY, 1),
(1035, 'lecteur_fidele', 1, '2026-02-25 15:57:44' + INTERVAL @decalage DAY, 1),
(1035, 'lecteur_fidele', 2, '2026-05-14 09:11:50' + INTERVAL @decalage DAY, 1),
(1035, 'plume_critique', 1, '2026-05-20 20:17:00' + INTERVAL @decalage DAY, 1),
(1035, 'habitue_rencontres', 1, '2026-09-19 12:00:00' + INTERVAL @decalage DAY, 1),
(1035, 'collectionneur', 1, '2026-10-16 20:00:18' + INTERVAL @decalage DAY, 1),
(1036, 'lecteur_fidele', 1, '2025-08-11 19:40:09' + INTERVAL @decalage DAY, 1),
(1036, 'lecteur_fidele', 2, '2026-03-27 17:20:14' + INTERVAL @decalage DAY, 1),
(1036, 'plume_critique', 1, '2026-06-27 20:40:10' + INTERVAL @decalage DAY, 1),
(1036, 'un_an', 1, '2026-06-27 20:40:10' + INTERVAL @decalage DAY, 1),
(1037, 'lecteur_fidele', 1, '2025-07-14 08:01:16' + INTERVAL @decalage DAY, 1),
(1037, 'plume_critique', 1, '2025-08-02 19:01:16' + INTERVAL @decalage DAY, 1),
(1037, 'lecteur_fidele', 2, '2025-12-28 13:47:33' + INTERVAL @decalage DAY, 1),
(1037, 'un_an', 1, '2026-09-07 18:34:05' + INTERVAL @decalage DAY, 1),
(1037, 'collectionneur', 1, '2026-10-15 13:07:33' + INTERVAL @decalage DAY, 1),
(1038, 'lecteur_fidele', 1, '2026-03-11 16:00:51' + INTERVAL @decalage DAY, 1),
(1038, 'plume_critique', 1, '2026-05-06 18:04:18' + INTERVAL @decalage DAY, 1),
(1038, 'lecteur_fidele', 2, '2026-05-20 14:27:06' + INTERVAL @decalage DAY, 1),
(1039, 'lecteur_fidele', 1, '2025-09-20 22:26:30' + INTERVAL @decalage DAY, 1),
(1039, 'lecteur_fidele', 2, '2025-12-01 14:43:07' + INTERVAL @decalage DAY, 1),
(1039, 'plume_critique', 1, '2026-01-13 22:01:45' + INTERVAL @decalage DAY, 1),
(1039, 'explorateur', 1, '2026-05-02 22:07:42' + INTERVAL @decalage DAY, 1),
(1039, 'collectionneur', 1, '2026-05-02 22:07:42' + INTERVAL @decalage DAY, 1),
(1039, 'lecteur_fidele', 3, '2026-06-24 22:24:00' + INTERVAL @decalage DAY, 1),
(1039, 'un_an', 1, '2026-08-24 09:26:33' + INTERVAL @decalage DAY, 1),
(1039, 'coeur_genereux', 1, '2026-09-23 19:21:50' + INTERVAL @decalage DAY, 1),
(1040, 'lecteur_fidele', 1, '2025-04-24 17:01:00' + INTERVAL @decalage DAY, 1),
(1040, 'plume_critique', 1, '2025-06-28 03:01:18' + INTERVAL @decalage DAY, 1),
(1040, 'lecteur_fidele', 2, '2025-07-13 16:45:58' + INTERVAL @decalage DAY, 1),
(1040, 'un_an', 1, '2026-05-04 22:46:29' + INTERVAL @decalage DAY, 1),
(1040, 'collectionneur', 1, '2026-05-07 09:40:56' + INTERVAL @decalage DAY, 1),
(1040, 'explorateur', 1, '2026-07-12 19:34:54' + INTERVAL @decalage DAY, 1),
(1040, 'lecteur_fidele', 3, '2026-07-29 17:04:21' + INTERVAL @decalage DAY, 1),
(1041, 'lecteur_fidele', 1, '2025-08-29 16:54:35' + INTERVAL @decalage DAY, 1),
(1041, 'plume_critique', 1, '2025-09-30 20:02:17' + INTERVAL @decalage DAY, 1),
(1041, 'lecteur_fidele', 2, '2025-11-03 18:46:48' + INTERVAL @decalage DAY, 1),
(1041, 'explorateur', 1, '2026-05-18 09:29:18' + INTERVAL @decalage DAY, 1),
(1041, 'un_an', 1, '2026-08-12 16:43:37' + INTERVAL @decalage DAY, 1),
(1041, 'collectionneur', 1, '2026-09-16 20:28:13' + INTERVAL @decalage DAY, 1),
(1042, 'lecteur_fidele', 1, '2025-09-15 12:25:05' + INTERVAL @decalage DAY, 1),
(1042, 'plume_critique', 1, '2025-10-01 21:25:05' + INTERVAL @decalage DAY, 1),
(1042, 'lecteur_fidele', 2, '2025-12-08 12:00:47' + INTERVAL @decalage DAY, 1),
(1042, 'explorateur', 1, '2026-06-28 17:31:38' + INTERVAL @decalage DAY, 1),
(1042, 'habitue_rencontres', 1, '2026-09-19 12:00:00' + INTERVAL @decalage DAY, 1),
(1042, 'un_an', 1, '2026-09-19 12:00:00' + INTERVAL @decalage DAY, 1),
(1042, 'collectionneur', 1, '2026-10-15 10:27:07' + INTERVAL @decalage DAY, 1),
(1043, 'lecteur_fidele', 1, '2025-02-06 16:36:44' + INTERVAL @decalage DAY, 1),
(1043, 'plume_critique', 1, '2025-03-10 03:36:44' + INTERVAL @decalage DAY, 1),
(1043, 'lecteur_fidele', 2, '2025-10-24 17:22:07' + INTERVAL @decalage DAY, 1),
(1043, 'un_an', 1, '2026-02-22 21:36:21' + INTERVAL @decalage DAY, 1),
(1043, 'habitue_rencontres', 1, '2026-09-19 11:00:00' + INTERVAL @decalage DAY, 1),
(1044, 'lecteur_fidele', 1, '2025-03-13 19:58:52' + INTERVAL @decalage DAY, 1),
(1044, 'lecteur_fidele', 2, '2025-05-15 08:03:38' + INTERVAL @decalage DAY, 1),
(1044, 'plume_critique', 1, '2025-06-13 15:03:38' + INTERVAL @decalage DAY, 1),
(1044, 'collectionneur', 1, '2025-12-13 08:14:30' + INTERVAL @decalage DAY, 1),
(1044, 'explorateur', 1, '2025-12-26 22:31:00' + INTERVAL @decalage DAY, 1),
(1044, 'lecteur_fidele', 3, '2026-03-18 10:07:52' + INTERVAL @decalage DAY, 1),
(1044, 'un_an', 1, '2026-03-18 10:07:52' + INTERVAL @decalage DAY, 1),
(1044, 'coeur_genereux', 1, '2026-09-10 18:37:22' + INTERVAL @decalage DAY, 1),
(1044, 'habitue_rencontres', 1, '2026-09-19 10:00:00' + INTERVAL @decalage DAY, 1),
(1045, 'lecteur_fidele', 1, '2025-07-08 13:36:46' + INTERVAL @decalage DAY, 1),
(1045, 'lecteur_fidele', 2, '2025-08-19 13:24:53' + INTERVAL @decalage DAY, 1),
(1045, 'collectionneur', 1, '2026-05-26 20:25:00' + INTERVAL @decalage DAY, 1),
(1045, 'un_an', 1, '2026-07-10 21:48:43' + INTERVAL @decalage DAY, 1),
(1045, 'lecteur_fidele', 3, '2026-08-07 15:17:45' + INTERVAL @decalage DAY, 1);

INSERT IGNORE INTO badge_obtenu (id_user, code, palier, date_obtention, vu) VALUES
(1045, 'habitue_rencontres', 1, '2026-09-19 10:00:00' + INTERVAL @decalage DAY, 1),
(1045, 'plume_critique', 1, '2026-10-03 15:49:09' + INTERVAL @decalage DAY, 1),
(1046, 'lecteur_fidele', 1, '2025-05-02 13:07:13' + INTERVAL @decalage DAY, 1),
(1046, 'plume_critique', 1, '2025-06-17 22:13:46' + INTERVAL @decalage DAY, 1),
(1046, 'lecteur_fidele', 2, '2025-07-31 11:27:34' + INTERVAL @decalage DAY, 1),
(1046, 'un_an', 1, '2026-05-05 18:45:59' + INTERVAL @decalage DAY, 1),
(1046, 'habitue_rencontres', 1, '2026-09-19 10:00:00' + INTERVAL @decalage DAY, 1),
(1047, 'lecteur_fidele', 1, '2025-04-20 10:15:15' + INTERVAL @decalage DAY, 1),
(1047, 'lecteur_fidele', 2, '2025-05-01 20:52:26' + INTERVAL @decalage DAY, 1),
(1047, 'plume_critique', 1, '2025-08-03 16:17:21' + INTERVAL @decalage DAY, 1),
(1047, 'collectionneur', 1, '2025-12-30 21:15:22' + INTERVAL @decalage DAY, 1),
(1047, 'lecteur_fidele', 3, '2026-01-30 15:27:51' + INTERVAL @decalage DAY, 1),
(1047, 'un_an', 1, '2026-08-04 10:57:24' + INTERVAL @decalage DAY, 1),
(1048, 'lecteur_fidele', 1, '2025-03-07 17:40:42' + INTERVAL @decalage DAY, 1),
(1048, 'lecteur_fidele', 2, '2025-03-11 15:18:57' + INTERVAL @decalage DAY, 1),
(1048, 'plume_critique', 1, '2025-03-25 22:40:42' + INTERVAL @decalage DAY, 1),
(1048, 'collectionneur', 1, '2025-08-22 18:10:07' + INTERVAL @decalage DAY, 1),
(1048, 'lecteur_fidele', 3, '2025-10-06 22:04:46' + INTERVAL @decalage DAY, 1),
(1048, 'explorateur', 1, '2025-10-06 22:04:46' + INTERVAL @decalage DAY, 1),
(1048, 'un_an', 1, '2026-02-21 10:39:49' + INTERVAL @decalage DAY, 1),
(1049, 'lecteur_fidele', 1, '2025-01-04 14:53:23' + INTERVAL @decalage DAY, 1),
(1049, 'lecteur_fidele', 2, '2025-04-14 16:25:59' + INTERVAL @decalage DAY, 1),
(1049, 'explorateur', 1, '2025-08-05 21:15:59' + INTERVAL @decalage DAY, 1),
(1049, 'plume_critique', 1, '2025-08-11 23:17:36' + INTERVAL @decalage DAY, 1),
(1049, 'collectionneur', 1, '2025-11-10 09:25:28' + INTERVAL @decalage DAY, 1),
(1049, 'lecteur_fidele', 3, '2025-12-27 09:04:02' + INTERVAL @decalage DAY, 1),
(1049, 'un_an', 1, '2026-02-06 20:47:40' + INTERVAL @decalage DAY, 1),
(1049, 'habitue_rencontres', 1, '2026-09-19 10:00:00' + INTERVAL @decalage DAY, 1),
(1050, 'lecteur_fidele', 1, '2024-09-10 12:42:48' + INTERVAL @decalage DAY, 1),
(1050, 'lecteur_fidele', 2, '2024-09-17 12:48:09' + INTERVAL @decalage DAY, 1),
(1050, 'plume_critique', 1, '2024-09-21 06:39:07' + INTERVAL @decalage DAY, 1),
(1050, 'explorateur', 1, '2024-12-16 11:22:21' + INTERVAL @decalage DAY, 1),
(1050, 'collectionneur', 1, '2024-12-23 18:35:11' + INTERVAL @decalage DAY, 1),
(1050, 'lecteur_fidele', 3, '2025-01-10 13:07:42' + INTERVAL @decalage DAY, 1),
(1050, 'un_an', 1, '2025-09-02 21:11:20' + INTERVAL @decalage DAY, 1),
(1050, 'habitue_rencontres', 1, '2026-09-19 10:00:00' + INTERVAL @decalage DAY, 1);

INSERT IGNORE INTO favori (id_user, id_livre, date_ajout) SELECT 1004, 2, '2026-02-24 21:42:21' + INTERVAL @decalage DAY FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 2);

INSERT IGNORE INTO favori (id_user, id_livre, date_ajout) SELECT 1005, 4, '2026-09-29 19:00:37' + INTERVAL @decalage DAY FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 4);

INSERT IGNORE INTO favori (id_user, id_livre, date_ajout) SELECT 1022, 6, '2026-09-06 22:43:52' + INTERVAL @decalage DAY FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 6);

INSERT IGNORE INTO favori (id_user, id_livre, date_ajout) SELECT 1025, 1, '2025-08-29 08:35:36' + INTERVAL @decalage DAY FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 1);

INSERT IGNORE INTO favori (id_user, id_livre, date_ajout) SELECT 1025, 7, '2026-10-10 17:17:39' + INTERVAL @decalage DAY FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 7);

INSERT IGNORE INTO favori (id_user, id_livre, date_ajout) SELECT 1030, 2, '2026-09-23 12:05:01' + INTERVAL @decalage DAY FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 2);

INSERT IGNORE INTO favori (id_user, id_livre, date_ajout) SELECT 1033, 10, '2026-10-10 10:51:23' + INTERVAL @decalage DAY FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 10);

INSERT IGNORE INTO favori (id_user, id_livre, date_ajout) SELECT 1034, 7, '2026-03-16 17:33:47' + INTERVAL @decalage DAY FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 7);

INSERT IGNORE INTO favori (id_user, id_livre, date_ajout) SELECT 1036, 6, '2026-07-18 16:18:14' + INTERVAL @decalage DAY FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 6);

INSERT IGNORE INTO favori (id_user, id_livre, date_ajout) SELECT 1037, 8, '2025-08-14 15:58:27' + INTERVAL @decalage DAY FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 8);

INSERT IGNORE INTO favori (id_user, id_livre, date_ajout) SELECT 1038, 1, '2026-03-03 19:24:22' + INTERVAL @decalage DAY FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 1);

INSERT IGNORE INTO favori (id_user, id_livre, date_ajout) SELECT 1039, 10, '2026-01-16 13:52:28' + INTERVAL @decalage DAY FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 10);

INSERT IGNORE INTO favori (id_user, id_livre, date_ajout) SELECT 1040, 3, '2025-06-01 14:34:28' + INTERVAL @decalage DAY FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 3);

INSERT IGNORE INTO favori (id_user, id_livre, date_ajout) SELECT 1040, 8, '2026-08-27 16:47:08' + INTERVAL @decalage DAY FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 8);

INSERT IGNORE INTO favori (id_user, id_livre, date_ajout) SELECT 1041, 2, '2026-01-03 13:22:05' + INTERVAL @decalage DAY FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 2);

INSERT IGNORE INTO favori (id_user, id_livre, date_ajout) SELECT 1043, 4, '2026-05-13 16:41:07' + INTERVAL @decalage DAY FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 4);

INSERT IGNORE INTO favori (id_user, id_livre, date_ajout) SELECT 1043, 5, '2025-03-20 14:59:04' + INTERVAL @decalage DAY FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 5);

INSERT IGNORE INTO favori (id_user, id_livre, date_ajout) SELECT 1045, 4, '2026-01-23 21:53:51' + INTERVAL @decalage DAY FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 4);

INSERT IGNORE INTO favori (id_user, id_livre, date_ajout) SELECT 1048, 10, '2025-02-13 15:04:57' + INTERVAL @decalage DAY FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 10);

INSERT IGNORE INTO favori (id_user, id_livre, date_ajout) SELECT 1048, 7, '2026-07-10 15:01:17' + INTERVAL @decalage DAY FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 7);

INSERT IGNORE INTO favori (id_user, id_livre, date_ajout) SELECT 1048, 8, '2026-07-11 22:12:42' + INTERVAL @decalage DAY FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 8);

INSERT IGNORE INTO favori (id_user, id_livre, date_ajout) SELECT 1049, 2, '2025-06-10 18:23:24' + INTERVAL @decalage DAY FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 2);

INSERT IGNORE INTO favori (id_user, id_livre, date_ajout) SELECT 1049, 5, '2026-07-12 15:56:40' + INTERVAL @decalage DAY FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 5);

INSERT IGNORE INTO favori (id_user, id_livre, date_ajout) SELECT 1050, 11, '2025-04-23 20:18:16' + INTERVAL @decalage DAY FROM DUAL WHERE EXISTS (SELECT 1 FROM livre WHERE id_livre = 11);

INSERT IGNORE INTO favori (id_user, id_livre, date_ajout) VALUES
(1003, 1043, '2026-07-12 10:33:40' + INTERVAL @decalage DAY),
(1003, 1036, '2026-02-14 16:05:50' + INTERVAL @decalage DAY),
(1003, 1027, '2026-01-31 18:40:30' + INTERVAL @decalage DAY),
(1004, 1042, '2026-08-12 09:28:41' + INTERVAL @decalage DAY),
(1005, 1029, '2025-10-17 22:57:24' + INTERVAL @decalage DAY),
(1005, 1020, '2026-02-24 13:04:41' + INTERVAL @decalage DAY),
(1006, 1015, '2026-10-07 18:04:14' + INTERVAL @decalage DAY),
(1007, 1020, '2026-09-28 09:58:52' + INTERVAL @decalage DAY),
(1008, 1032, '2026-07-10 11:46:32' + INTERVAL @decalage DAY),
(1008, 1007, '2026-09-23 22:47:30' + INTERVAL @decalage DAY),
(1009, 1008, '2026-02-21 15:38:08' + INTERVAL @decalage DAY),
(1010, 1033, '2026-07-14 22:51:18' + INTERVAL @decalage DAY),
(1010, 1027, '2026-09-29 21:53:02' + INTERVAL @decalage DAY),
(1011, 1028, '2026-06-16 21:52:41' + INTERVAL @decalage DAY),
(1011, 1039, '2026-08-29 18:06:13' + INTERVAL @decalage DAY),
(1011, 1025, '2026-01-28 11:27:42' + INTERVAL @decalage DAY),
(1013, 1009, '2026-08-19 10:40:27' + INTERVAL @decalage DAY),
(1014, 1042, '2026-09-19 18:48:08' + INTERVAL @decalage DAY),
(1017, 1012, '2026-06-26 17:07:32' + INTERVAL @decalage DAY),
(1017, 1039, '2026-05-22 11:03:40' + INTERVAL @decalage DAY),
(1017, 1023, '2026-06-18 20:39:34' + INTERVAL @decalage DAY),
(1017, 1047, '2026-10-10 13:20:03' + INTERVAL @decalage DAY),
(1018, 1033, '2026-07-06 20:58:58' + INTERVAL @decalage DAY),
(1018, 1016, '2026-08-17 21:26:26' + INTERVAL @decalage DAY),
(1018, 1036, '2026-09-14 21:49:45' + INTERVAL @decalage DAY),
(1022, 1015, '2026-07-02 20:07:41' + INTERVAL @decalage DAY),
(1023, 1044, '2026-01-28 18:51:28' + INTERVAL @decalage DAY),
(1025, 1030, '2026-07-19 20:21:04' + INTERVAL @decalage DAY),
(1025, 1046, '2026-05-05 17:48:41' + INTERVAL @decalage DAY),
(1025, 1041, '2026-06-21 15:13:17' + INTERVAL @decalage DAY),
(1026, 1033, '2026-09-27 17:28:54' + INTERVAL @decalage DAY),
(1026, 1032, '2025-07-25 15:32:44' + INTERVAL @decalage DAY),
(1026, 1002, '2025-05-18 21:32:02' + INTERVAL @decalage DAY),
(1026, 1027, '2025-05-01 17:17:15' + INTERVAL @decalage DAY),
(1026, 1026, '2025-08-12 21:07:15' + INTERVAL @decalage DAY),
(1027, 1046, '2026-08-12 19:35:47' + INTERVAL @decalage DAY),
(1028, 1044, '2026-10-06 10:55:23' + INTERVAL @decalage DAY),
(1028, 1028, '2026-04-01 19:35:12' + INTERVAL @decalage DAY),
(1029, 1026, '2026-06-25 19:31:35' + INTERVAL @decalage DAY),
(1029, 1019, '2026-03-13 22:57:38' + INTERVAL @decalage DAY),
(1029, 1007, '2026-02-15 12:01:53' + INTERVAL @decalage DAY),
(1029, 1032, '2026-09-26 22:00:29' + INTERVAL @decalage DAY),
(1030, 1042, '2026-07-24 12:27:59' + INTERVAL @decalage DAY),
(1030, 1006, '2026-08-08 12:12:16' + INTERVAL @decalage DAY),
(1030, 1033, '2026-10-10 22:57:14' + INTERVAL @decalage DAY),
(1030, 1013, '2026-07-15 13:48:38' + INTERVAL @decalage DAY),
(1030, 1027, '2026-08-05 13:58:00' + INTERVAL @decalage DAY),
(1031, 1011, '2025-08-30 22:19:02' + INTERVAL @decalage DAY),
(1032, 1042, '2026-05-25 15:45:14' + INTERVAL @decalage DAY),
(1032, 1013, '2026-07-14 08:12:36' + INTERVAL @decalage DAY),
(1032, 1008, '2026-06-13 20:36:16' + INTERVAL @decalage DAY),
(1032, 1043, '2026-06-26 12:29:32' + INTERVAL @decalage DAY),
(1032, 1033, '2026-08-16 15:26:53' + INTERVAL @decalage DAY),
(1033, 1039, '2026-07-05 16:07:25' + INTERVAL @decalage DAY),
(1033, 1003, '2025-10-27 09:45:48' + INTERVAL @decalage DAY),
(1034, 1019, '2026-07-10 17:00:11' + INTERVAL @decalage DAY),
(1034, 1043, '2026-04-11 10:53:14' + INTERVAL @decalage DAY),
(1035, 1046, '2026-08-02 21:13:37' + INTERVAL @decalage DAY),
(1035, 1008, '2026-07-17 19:10:00' + INTERVAL @decalage DAY),
(1035, 1001, '2026-02-03 18:52:12' + INTERVAL @decalage DAY),
(1035, 1028, '2026-06-10 22:46:50' + INTERVAL @decalage DAY),
(1036, 1042, '2026-02-28 08:47:45' + INTERVAL @decalage DAY),
(1036, 1025, '2026-04-29 18:47:36' + INTERVAL @decalage DAY),
(1037, 1001, '2026-01-16 18:20:43' + INTERVAL @decalage DAY),
(1038, 1039, '2026-01-09 15:53:58' + INTERVAL @decalage DAY),
(1038, 1040, '2026-06-17 21:50:57' + INTERVAL @decalage DAY),
(1038, 1029, '2026-07-10 22:28:09' + INTERVAL @decalage DAY),
(1038, 1043, '2026-10-10 12:27:04' + INTERVAL @decalage DAY),
(1038, 1017, '2026-05-16 09:18:47' + INTERVAL @decalage DAY),
(1039, 1044, '2025-12-14 14:55:20' + INTERVAL @decalage DAY),
(1039, 1039, '2025-11-25 16:37:26' + INTERVAL @decalage DAY),
(1039, 1009, '2026-03-09 14:27:37' + INTERVAL @decalage DAY),
(1039, 1022, '2026-01-04 19:58:04' + INTERVAL @decalage DAY),
(1039, 1026, '2025-08-30 17:51:48' + INTERVAL @decalage DAY),
(1039, 1019, '2025-09-05 19:33:44' + INTERVAL @decalage DAY),
(1040, 1028, '2025-05-02 13:17:54' + INTERVAL @decalage DAY),
(1040, 1035, '2025-04-22 08:09:52' + INTERVAL @decalage DAY),
(1040, 1039, '2026-10-03 14:16:31' + INTERVAL @decalage DAY),
(1040, 1001, '2025-09-10 17:18:48' + INTERVAL @decalage DAY),
(1040, 1040, '2025-10-23 10:20:05' + INTERVAL @decalage DAY),
(1041, 1029, '2026-07-18 10:46:41' + INTERVAL @decalage DAY),
(1041, 1039, '2026-09-18 20:09:36' + INTERVAL @decalage DAY),
(1042, 1011, '2026-08-08 20:48:07' + INTERVAL @decalage DAY),
(1042, 1033, '2026-05-26 22:31:59' + INTERVAL @decalage DAY),
(1042, 1038, '2026-02-26 08:12:49' + INTERVAL @decalage DAY),
(1042, 1010, '2025-12-03 17:34:28' + INTERVAL @decalage DAY),
(1042, 1008, '2026-02-25 11:01:25' + INTERVAL @decalage DAY),
(1042, 1013, '2026-01-30 16:16:45' + INTERVAL @decalage DAY),
(1043, 1007, '2026-09-10 14:39:14' + INTERVAL @decalage DAY),
(1043, 1025, '2026-06-09 14:30:55' + INTERVAL @decalage DAY),
(1043, 1013, '2026-01-28 13:51:46' + INTERVAL @decalage DAY),
(1043, 1032, '2025-04-02 16:54:58' + INTERVAL @decalage DAY),
(1043, 1012, '2025-08-09 18:28:11' + INTERVAL @decalage DAY),
(1044, 1035, '2025-09-03 11:53:06' + INTERVAL @decalage DAY),
(1044, 1032, '2025-09-27 20:41:13' + INTERVAL @decalage DAY),
(1044, 1011, '2025-07-31 21:43:02' + INTERVAL @decalage DAY),
(1044, 1041, '2025-05-30 08:54:15' + INTERVAL @decalage DAY),
(1045, 1046, '2025-11-07 08:34:08' + INTERVAL @decalage DAY),
(1045, 1001, '2025-11-30 12:31:15' + INTERVAL @decalage DAY),
(1045, 1011, '2026-09-14 21:44:22' + INTERVAL @decalage DAY),
(1045, 1003, '2025-09-28 08:36:27' + INTERVAL @decalage DAY),
(1045, 1012, '2025-11-06 15:12:11' + INTERVAL @decalage DAY),
(1046, 1024, '2025-03-01 22:41:56' + INTERVAL @decalage DAY),
(1046, 1037, '2026-01-24 14:59:54' + INTERVAL @decalage DAY),
(1046, 1016, '2025-05-22 19:09:18' + INTERVAL @decalage DAY),
(1046, 1004, '2026-03-30 14:20:48' + INTERVAL @decalage DAY),
(1046, 1006, '2026-09-02 11:56:14' + INTERVAL @decalage DAY),
(1047, 1001, '2025-10-14 10:06:20' + INTERVAL @decalage DAY),
(1047, 1028, '2025-06-17 12:08:10' + INTERVAL @decalage DAY),
(1047, 1034, '2025-04-18 13:50:37' + INTERVAL @decalage DAY),
(1047, 1024, '2025-03-30 08:13:36' + INTERVAL @decalage DAY),
(1047, 1029, '2026-04-09 12:38:48' + INTERVAL @decalage DAY),
(1048, 1029, '2026-07-14 18:38:59' + INTERVAL @decalage DAY),
(1048, 1043, '2025-06-15 16:18:10' + INTERVAL @decalage DAY),
(1048, 1044, '2026-07-07 17:22:51' + INTERVAL @decalage DAY),
(1049, 1008, '2026-10-23 16:49:59' + INTERVAL @decalage DAY),
(1049, 1012, '2025-05-21 20:52:25' + INTERVAL @decalage DAY),
(1050, 1020, '2026-04-29 18:03:15' + INTERVAL @decalage DAY),
(1050, 1037, '2024-11-29 15:03:23' + INTERVAL @decalage DAY),
(1050, 1030, '2025-07-10 13:18:12' + INTERVAL @decalage DAY),
(1050, 1026, '2026-08-21 11:33:55' + INTERVAL @decalage DAY),
(1050, 1047, '2024-10-03 14:24:30' + INTERVAL @decalage DAY),
(1050, 1023, '2025-08-07 12:51:10' + INTERVAL @decalage DAY),
(1050, 1039, '2026-02-21 09:55:02' + INTERVAL @decalage DAY),
(1050, 1038, '2024-10-26 10:20:33' + INTERVAL @decalage DAY);

INSERT IGNORE INTO alerte_stock (id_user, id_livre, date_creation, date_envoi) VALUES
(1014, 1029, '2026-09-27 08:29:01' + INTERVAL @decalage DAY, NULL),
(1034, 1029, '2026-10-21 12:03:32' + INTERVAL @decalage DAY, NULL),
(1011, 1029, '2026-10-04 08:24:47' + INTERVAL @decalage DAY, NULL),
(1006, 1029, '2026-10-14 19:19:36' + INTERVAL @decalage DAY, NULL),
(1010, 1029, '2026-10-05 09:33:16' + INTERVAL @decalage DAY, NULL),
(1032, 1033, '2026-10-03 14:36:18' + INTERVAL @decalage DAY, NULL),
(1016, 1033, '2026-09-24 14:27:32' + INTERVAL @decalage DAY, NULL),
(1039, 1033, '2026-10-22 16:56:12' + INTERVAL @decalage DAY, NULL),
(1019, 1033, '2026-09-29 18:43:25' + INTERVAL @decalage DAY, NULL),
(1023, 1035, '2026-09-24 11:26:38' + INTERVAL @decalage DAY, NULL),
(1050, 1035, '2026-10-15 13:14:21' + INTERVAL @decalage DAY, NULL),
(1049, 1035, '2026-10-18 10:28:11' + INTERVAL @decalage DAY, NULL),
(1011, 1035, '2026-10-08 19:40:00' + INTERVAL @decalage DAY, NULL),
(1014, 1035, '2026-10-09 15:33:29' + INTERVAL @decalage DAY, NULL),
(1019, 1035, '2026-10-06 20:28:49' + INTERVAL @decalage DAY, NULL);

INSERT INTO don (id_don, id_donneur, id_livre, id_categorie, titre, auteur, etat, description, photo, lieu_retrait, conditions_retrait, date_limite, statut, code, code_court, id_beneficiaire, date_creation, date_reservation, date_recuperation) VALUES
(1001, 1028, 1026, (SELECT id_categorie FROM categorie WHERE slug = 'policier-thriller'), 'Les Rivières pourpres', 'Jean-Christophe Grangé', 'bon', 'Livre de mes enfants, en bon état. Parfait pour une première lecture.', NULL, 'Parc de la Tête d''Or, entrée Enfants du Rhône', 'En semaine après 18h', '2026-11-05' + INTERVAL @decalage DAY, 'recupere', '2134b5ef7a9385b947df26d7c7206240', '8J6T8E', 1043, '2026-09-21 08:12:03' + INTERVAL @decalage DAY, '2026-09-24 13:12:03' + INTERVAL @decalage DAY, '2026-09-26 17:12:03' + INTERVAL @decalage DAY),
(1002, 1025, 1020, (SELECT id_categorie FROM categorie WHERE slug = 'economie-entreprise'), 'Le Capital au XXIe siècle', 'Thomas Piketty', 'use', 'Livre de mes enfants, en bon état. Parfait pour une première lecture.', NULL, 'Place Bellecour, près de la statue', 'Sur rendez-vous, par message', '2026-11-14' + INTERVAL @decalage DAY, 'recupere', '3ba06996479b2da1f1f43f54caf2295e', '955WF4', 1006, '2026-09-30 12:23:06' + INTERVAL @decalage DAY, '2026-10-03 15:23:06' + INTERVAL @decalage DAY, '2026-10-08 19:23:06' + INTERVAL @decalage DAY),
(1003, 1039, 1041, (SELECT id_categorie FROM categorie WHERE slug = 'musique'), 'Hitchcock / Truffaut - Édition définitive', 'François Truffaut', 'bon', 'Livre de mes enfants, en bon état. Parfait pour une première lecture.', NULL, 'Parc de la Tête d''Or, entrée Enfants du Rhône', 'Le samedi matin', '2026-11-02' + INTERVAL @decalage DAY, 'recupere', '2495ee2124e0216e3bc1683d7e11dfb4', 'YQTPRH', 1028, '2026-09-18 15:21:50' + INTERVAL @decalage DAY, '2026-09-20 16:21:50' + INTERVAL @decalage DAY, '2026-09-23 19:21:50' + INTERVAL @decalage DAY),
(1004, 1044, 1029, (SELECT id_categorie FROM categorie WHERE slug = 'ados'), 'Hunger Games - Tome 1', 'Suzanne Collins', 'bon', 'Quelques traces de lecture sur la tranche, mais toutes les pages sont propres. À récupérer en main propre.', NULL, 'Parc de la Tête d''Or, entrée Enfants du Rhône', 'En semaine après 18h', '2026-10-18' + INTERVAL @decalage DAY, 'recupere', '2fbaa0c307d206f4262bcc739f1e6400', 'SBBNBU', 1009, '2026-09-03 12:37:22' + INTERVAL @decalage DAY, '2026-09-06 16:37:22' + INTERVAL @decalage DAY, '2026-09-10 18:37:22' + INTERVAL @decalage DAY),
(1005, 1041, 1006, (SELECT id_categorie FROM categorie WHERE slug = 'college'), 'Bescherelle - La conjugaison pour tous', 'Bescherelle', 'bon', 'Quelques traces de lecture sur la tranche, mais toutes les pages sont propres. À récupérer en main propre.', NULL, 'Place Bellecour, près de la statue', 'Sur rendez-vous, par message', '2026-10-20' + INTERVAL @decalage DAY, 'reserve', '64f5120e372216336d0fb9f3d4404147', 'S8TX2N', 1031, '2026-09-05 10:58:23' + INTERVAL @decalage DAY, '2026-09-08 16:58:23' + INTERVAL @decalage DAY, NULL),
(1006, 1049, 1034, (SELECT id_categorie FROM categorie WHERE slug = 'romance'), 'Avant toi', 'Jojo Moyes', 'tres bon', 'Couverture un peu cornée, intérieur impeccable. Idéal pour découvrir la série.', NULL, 'Bibliothèque de Villeurbanne, hall d''entrée', 'Le samedi matin', '2026-11-14' + INTERVAL @decalage DAY, 'reserve', '92c9bc8e731b01d3ca48d0ecf91632cd', 'V7KK5C', 1035, '2026-09-30 12:19:14' + INTERVAL @decalage DAY, '2026-10-03 16:19:14' + INTERVAL @decalage DAY, NULL),
(1007, 1045, 1027, (SELECT id_categorie FROM categorie WHERE slug = 'informatique'), 'Coder proprement', 'Robert C. Martin', 'tres bon', 'Lu une seule fois, comme neuf. Je préfère qu''il serve à quelqu''un plutôt qu''il prenne la poussière.', NULL, 'Place Bellecour, près de la statue', 'En semaine après 18h', '2026-11-30' + INTERVAL @decalage DAY, 'disponible', 'd6ec3d10520017db277748bb8926fbc1', 'JTGZBN', NULL, '2026-10-16 22:27:46' + INTERVAL @decalage DAY, NULL, NULL),
(1008, 1040, 1028, (SELECT id_categorie FROM categorie WHERE slug = 'bandes-dessinees'), 'Blacksad - Tome 1 : Quelque part entre les ombres', 'Juan Díaz Canales, Juanjo Guarnido', 'tres bon', 'Lu une seule fois, comme neuf. Je préfère qu''il serve à quelqu''un plutôt qu''il prenne la poussière.', NULL, 'Bibliothèque de Villeurbanne, hall d''entrée', 'En semaine après 18h', '2026-11-26' + INTERVAL @decalage DAY, 'disponible', '668b0c736a9cc27d26558869eec4b8a3', 'VCWZ9C', NULL, '2026-10-12 12:58:40' + INTERVAL @decalage DAY, NULL, NULL),
(1009, 1037, 1025, (SELECT id_categorie FROM categorie WHERE slug = 'science-fiction'), '1984', 'George Orwell', 'bon', 'Livre de mes enfants, en bon état. Parfait pour une première lecture.', NULL, 'Parc de la Tête d''Or, entrée Enfants du Rhône', 'Sur rendez-vous, par message', '2026-11-30' + INTERVAL @decalage DAY, 'disponible', 'd915a1a9abad01adfd50e8b32ffef650', 'BRPDMC', NULL, '2026-10-16 12:26:55' + INTERVAL @decalage DAY, NULL, NULL),
(1010, 1026, 1043, (SELECT id_categorie FROM categorie WHERE slug = 'policier-thriller'), 'La Vérité sur l''affaire Harry Quebert', 'Joël Dicker', 'bon', 'Lu une seule fois, comme neuf. Je préfère qu''il serve à quelqu''un plutôt qu''il prenne la poussière.', NULL, 'Gare Part-Dieu, devant l''entrée Villette', 'En semaine après 18h', '2026-11-26' + INTERVAL @decalage DAY, 'disponible', '315423b70bedd1fc03c40685c2ccc3f1', 'MM4478', NULL, '2026-10-12 22:12:55' + INTERVAL @decalage DAY, NULL, NULL);

INSERT IGNORE INTO evenement_inscription (id_inscription, id_evenement, id_user, statut, code, code_court, date_inscription, present, date_presence, prioritaire) SELECT 1001, 1, 1035, 'inscrit', '350baaaefe4641cac800ba0ea0bd3d68', '6XLE86', '2026-10-20 22:16:15' + INTERVAL @decalage DAY, 0, NULL, 0 FROM DUAL WHERE EXISTS (SELECT 1 FROM evenement WHERE id_evenement = 1);

INSERT IGNORE INTO evenement_inscription (id_inscription, id_evenement, id_user, statut, code, code_court, date_inscription, present, date_presence, prioritaire) SELECT 1002, 1, 1042, 'inscrit', 'dd7cbed1fe09a6095863fd393eb331ad', 'URB2Z9', '2026-10-13 10:56:52' + INTERVAL @decalage DAY, 0, NULL, 0 FROM DUAL WHERE EXISTS (SELECT 1 FROM evenement WHERE id_evenement = 1);

INSERT IGNORE INTO evenement_inscription (id_inscription, id_evenement, id_user, statut, code, code_court, date_inscription, present, date_presence, prioritaire) SELECT 1003, 1, 1003, 'inscrit', 'd69bc208c34df579728834f097547ef4', 'DLJFUL', '2026-10-16 14:20:37' + INTERVAL @decalage DAY, 0, NULL, 0 FROM DUAL WHERE EXISTS (SELECT 1 FROM evenement WHERE id_evenement = 1);

INSERT IGNORE INTO evenement_inscription (id_inscription, id_evenement, id_user, statut, code, code_court, date_inscription, present, date_presence, prioritaire) SELECT 1004, 1, 1047, 'inscrit', 'ae1be1969e7ef784cfca5f70099aef61', 'TX3TTU', '2026-10-13 16:49:31' + INTERVAL @decalage DAY, 0, NULL, 0 FROM DUAL WHERE EXISTS (SELECT 1 FROM evenement WHERE id_evenement = 1);

INSERT IGNORE INTO evenement_inscription (id_inscription, id_evenement, id_user, statut, code, code_court, date_inscription, present, date_presence, prioritaire) SELECT 1005, 1, 1031, 'inscrit', 'f7c5a44d96b7aea0305431bc2249e34e', 'F6JXWX', '2026-10-16 08:38:27' + INTERVAL @decalage DAY, 0, NULL, 0 FROM DUAL WHERE EXISTS (SELECT 1 FROM evenement WHERE id_evenement = 1);

INSERT IGNORE INTO evenement_inscription (id_inscription, id_evenement, id_user, statut, code, code_court, date_inscription, present, date_presence, prioritaire) SELECT 1006, 1, 1005, 'inscrit', '8584ffc8116414d849ae5d20d56aa203', '3N5N3A', '2026-10-22 21:18:51' + INTERVAL @decalage DAY, 0, NULL, 0 FROM DUAL WHERE EXISTS (SELECT 1 FROM evenement WHERE id_evenement = 1);

INSERT IGNORE INTO evenement_inscription (id_inscription, id_evenement, id_user, statut, code, code_court, date_inscription, present, date_presence, prioritaire) SELECT 1007, 1, 1023, 'inscrit', 'dadc62296f70953030d063966204c49f', 'ES7VNZ', '2026-10-12 13:11:48' + INTERVAL @decalage DAY, 0, NULL, 0 FROM DUAL WHERE EXISTS (SELECT 1 FROM evenement WHERE id_evenement = 1);

INSERT IGNORE INTO evenement_inscription (id_inscription, id_evenement, id_user, statut, code, code_court, date_inscription, present, date_presence, prioritaire) SELECT 1008, 1, 1019, 'inscrit', '129f4044f57ef3783222b67a6ac72f51', 'AZ8M36', '2026-10-17 08:29:35' + INTERVAL @decalage DAY, 0, NULL, 0 FROM DUAL WHERE EXISTS (SELECT 1 FROM evenement WHERE id_evenement = 1);

INSERT IGNORE INTO evenement_inscription (id_inscription, id_evenement, id_user, statut, code, code_court, date_inscription, present, date_presence, prioritaire) SELECT 1009, 1, 1015, 'inscrit', '8d71378a351dd8f462a95606222f604d', 'CQ88MU', '2026-10-21 20:50:08' + INTERVAL @decalage DAY, 0, NULL, 0 FROM DUAL WHERE EXISTS (SELECT 1 FROM evenement WHERE id_evenement = 1);

INSERT IGNORE INTO evenement_inscription (id_inscription, id_evenement, id_user, statut, code, code_court, date_inscription, present, date_presence, prioritaire) SELECT 1010, 1, 1028, 'inscrit', '3b7b275b8a586abd86517f700c52d97a', 'M7LY3U', '2026-10-12 19:04:24' + INTERVAL @decalage DAY, 0, NULL, 0 FROM DUAL WHERE EXISTS (SELECT 1 FROM evenement WHERE id_evenement = 1);

INSERT IGNORE INTO evenement_inscription (id_inscription, id_evenement, id_user, statut, code, code_court, date_inscription, present, date_presence, prioritaire) SELECT 1011, 2, 1015, 'inscrit', '9b8882b26241fa8017b4e0e675c5fc91', 'YZBG9X', '2026-10-21 14:28:03' + INTERVAL @decalage DAY, 0, NULL, 0 FROM DUAL WHERE EXISTS (SELECT 1 FROM evenement WHERE id_evenement = 2);

INSERT IGNORE INTO evenement_inscription (id_inscription, id_evenement, id_user, statut, code, code_court, date_inscription, present, date_presence, prioritaire) SELECT 1012, 2, 1031, 'inscrit', '2769bed1f6adda653e1ed63b3ec2beaa', 'C2XDQQ', '2026-10-21 16:09:48' + INTERVAL @decalage DAY, 0, NULL, 0 FROM DUAL WHERE EXISTS (SELECT 1 FROM evenement WHERE id_evenement = 2);

INSERT IGNORE INTO evenement_inscription (id_inscription, id_evenement, id_user, statut, code, code_court, date_inscription, present, date_presence, prioritaire) SELECT 1013, 2, 1013, 'inscrit', 'decf94a53d8d4aa7fd597fe822839350', 'WP38SV', '2026-10-23 14:03:55' + INTERVAL @decalage DAY, 0, NULL, 0 FROM DUAL WHERE EXISTS (SELECT 1 FROM evenement WHERE id_evenement = 2);

INSERT IGNORE INTO evenement_inscription (id_inscription, id_evenement, id_user, statut, code, code_court, date_inscription, present, date_presence, prioritaire) SELECT 1014, 2, 1035, 'inscrit', 'd4725cf1ce3e5af0d9c9fc974f8798aa', 'XULZVX', '2026-10-14 09:09:50' + INTERVAL @decalage DAY, 0, NULL, 0 FROM DUAL WHERE EXISTS (SELECT 1 FROM evenement WHERE id_evenement = 2);

INSERT IGNORE INTO evenement_inscription (id_inscription, id_evenement, id_user, statut, code, code_court, date_inscription, present, date_presence, prioritaire) SELECT 1015, 2, 1009, 'inscrit', 'e52a85f1abc0df43d0c66b3f11a60589', '8VS4XL', '2026-10-20 16:19:02' + INTERVAL @decalage DAY, 0, NULL, 0 FROM DUAL WHERE EXISTS (SELECT 1 FROM evenement WHERE id_evenement = 2);

INSERT IGNORE INTO evenement_inscription (id_inscription, id_evenement, id_user, statut, code, code_court, date_inscription, present, date_presence, prioritaire) SELECT 1016, 2, 1032, 'inscrit', 'acfca0e22c11e304f43cbb49cfac458b', 'A9YEGJ', '2026-10-18 16:12:09' + INTERVAL @decalage DAY, 0, NULL, 0 FROM DUAL WHERE EXISTS (SELECT 1 FROM evenement WHERE id_evenement = 2);

INSERT IGNORE INTO evenement_inscription (id_inscription, id_evenement, id_user, statut, code, code_court, date_inscription, present, date_presence, prioritaire) SELECT 1017, 2, 1016, 'inscrit', '86b1dd808b032059f4df032e2275bede', 'WCJ3JD', '2026-10-24 12:47:04' + INTERVAL @decalage DAY, 0, NULL, 0 FROM DUAL WHERE EXISTS (SELECT 1 FROM evenement WHERE id_evenement = 2);

INSERT IGNORE INTO evenement_inscription (id_inscription, id_evenement, id_user, statut, code, code_court, date_inscription, present, date_presence, prioritaire) SELECT 1018, 2, 1014, 'inscrit', 'bc974e04c708d6b56c200d2dcbc3ea4d', '8RGVQ3', '2026-10-12 14:21:16' + INTERVAL @decalage DAY, 0, NULL, 0 FROM DUAL WHERE EXISTS (SELECT 1 FROM evenement WHERE id_evenement = 2);

INSERT IGNORE INTO evenement_inscription (id_inscription, id_evenement, id_user, statut, code, code_court, date_inscription, present, date_presence, prioritaire) SELECT 1019, 2, 1023, 'inscrit', '199d2b3c32c17c570479f75fbc016ebd', 'WHLLRR', '2026-10-13 09:39:25' + INTERVAL @decalage DAY, 0, NULL, 0 FROM DUAL WHERE EXISTS (SELECT 1 FROM evenement WHERE id_evenement = 2);

INSERT IGNORE INTO evenement_inscription (id_inscription, id_evenement, id_user, statut, code, code_court, date_inscription, present, date_presence, prioritaire) SELECT 1020, 2, 1004, 'inscrit', 'f93e77257002072bd478e7c99c25f66b', '2DJC52', '2026-10-15 11:44:37' + INTERVAL @decalage DAY, 0, NULL, 0 FROM DUAL WHERE EXISTS (SELECT 1 FROM evenement WHERE id_evenement = 2);

INSERT IGNORE INTO evenement_inscription (id_inscription, id_evenement, id_user, statut, code, code_court, date_inscription, present, date_presence, prioritaire) SELECT 1021, 2, 1022, 'inscrit', 'a96c612d81e4378b1ee1052dcf193fdb', '49KQHD', '2026-10-12 18:30:12' + INTERVAL @decalage DAY, 0, NULL, 0 FROM DUAL WHERE EXISTS (SELECT 1 FROM evenement WHERE id_evenement = 2);

INSERT IGNORE INTO evenement_inscription (id_inscription, id_evenement, id_user, statut, code, code_court, date_inscription, present, date_presence, prioritaire) SELECT 1022, 2, 1049, 'inscrit', '95c8e228065988f183c331b3ef43bfd7', 'HFYSEJ', '2026-10-22 10:55:22' + INTERVAL @decalage DAY, 0, NULL, 0 FROM DUAL WHERE EXISTS (SELECT 1 FROM evenement WHERE id_evenement = 2);

INSERT IGNORE INTO evenement_inscription (id_inscription, id_evenement, id_user, statut, code, code_court, date_inscription, present, date_presence, prioritaire) SELECT 1023, 2, 1012, 'inscrit', 'c0068b3dafb1dbfb58f1c21c83db2056', 'CU9A5D', '2026-10-18 19:11:40' + INTERVAL @decalage DAY, 0, NULL, 0 FROM DUAL WHERE EXISTS (SELECT 1 FROM evenement WHERE id_evenement = 2);

INSERT IGNORE INTO evenement_inscription (id_inscription, id_evenement, id_user, statut, code, code_court, date_inscription, present, date_presence, prioritaire) SELECT 1024, 2, 1050, 'inscrit', 'e7744538da5a43b9d73cd95704df7a9b', 'LMN9YM', '2026-10-23 20:26:12' + INTERVAL @decalage DAY, 0, NULL, 0 FROM DUAL WHERE EXISTS (SELECT 1 FROM evenement WHERE id_evenement = 2);

INSERT IGNORE INTO evenement_inscription (id_inscription, id_evenement, id_user, statut, code, code_court, date_inscription, present, date_presence, prioritaire) SELECT 1025, 2, 1030, 'inscrit', 'b29dbd127d885b60a40a3b7b8407f362', '3PXTWT', '2026-10-24 20:33:08' + INTERVAL @decalage DAY, 0, NULL, 0 FROM DUAL WHERE EXISTS (SELECT 1 FROM evenement WHERE id_evenement = 2);

INSERT IGNORE INTO evenement_inscription (id_inscription, id_evenement, id_user, statut, code, code_court, date_inscription, present, date_presence, prioritaire) SELECT 1026, 2, 1006, 'inscrit', '43b29f5433fbdb0048f9d2fb1c0413fc', 'NHXZ77', '2026-10-21 21:09:15' + INTERVAL @decalage DAY, 0, NULL, 0 FROM DUAL WHERE EXISTS (SELECT 1 FROM evenement WHERE id_evenement = 2);

INSERT IGNORE INTO evenement_inscription (id_inscription, id_evenement, id_user, statut, code, code_court, date_inscription, present, date_presence, prioritaire) SELECT 1027, 2, 1005, 'inscrit', '7bb75f723041049bf74dd544b9b93e0d', 'EPC3KC', '2026-10-24 17:17:07' + INTERVAL @decalage DAY, 0, NULL, 0 FROM DUAL WHERE EXISTS (SELECT 1 FROM evenement WHERE id_evenement = 2);

INSERT IGNORE INTO evenement_inscription (id_inscription, id_evenement, id_user, statut, code, code_court, date_inscription, present, date_presence, prioritaire) SELECT 1028, 2, 1021, 'inscrit', 'c478a106ece28efc855ba20b7ef81639', '3J76GA', '2026-10-23 19:37:13' + INTERVAL @decalage DAY, 0, NULL, 0 FROM DUAL WHERE EXISTS (SELECT 1 FROM evenement WHERE id_evenement = 2);

INSERT IGNORE INTO evenement_inscription (id_inscription, id_evenement, id_user, statut, code, code_court, date_inscription, present, date_presence, prioritaire) SELECT 1029, 2, 1038, 'inscrit', 'b5ceee612356f1e3a7d9f78488dc1f90', '8TDCQX', '2026-10-17 11:46:50' + INTERVAL @decalage DAY, 0, NULL, 0 FROM DUAL WHERE EXISTS (SELECT 1 FROM evenement WHERE id_evenement = 2);

INSERT IGNORE INTO evenement_inscription (id_inscription, id_evenement, id_user, statut, code, code_court, date_inscription, present, date_presence, prioritaire) SELECT 1030, 2, 1019, 'inscrit', '7bac64dab2f05968e5a95bf69ec1502f', 'XMCXTM', '2026-10-18 20:14:56' + INTERVAL @decalage DAY, 0, NULL, 0 FROM DUAL WHERE EXISTS (SELECT 1 FROM evenement WHERE id_evenement = 2);

INSERT IGNORE INTO evenement_inscription (id_inscription, id_evenement, id_user, statut, code, code_court, date_inscription, present, date_presence, prioritaire) SELECT 1031, 2, 1046, 'inscrit', '3190d7c364deb044eeac7336b9a78f20', '93REWE', '2026-10-17 13:41:31' + INTERVAL @decalage DAY, 0, NULL, 0 FROM DUAL WHERE EXISTS (SELECT 1 FROM evenement WHERE id_evenement = 2);

INSERT IGNORE INTO evenement_inscription (id_inscription, id_evenement, id_user, statut, code, code_court, date_inscription, present, date_presence, prioritaire) SELECT 1032, 2, 1003, 'inscrit', 'e7bb16fcd76fc861417827ed0c42562b', '4Z9RBS', '2026-10-14 14:10:40' + INTERVAL @decalage DAY, 0, NULL, 0 FROM DUAL WHERE EXISTS (SELECT 1 FROM evenement WHERE id_evenement = 2);

INSERT IGNORE INTO evenement_inscription (id_inscription, id_evenement, id_user, statut, code, code_court, date_inscription, present, date_presence, prioritaire) SELECT 1033, 2, 1044, 'inscrit', '8fd27922f009b428eb7bdfff2cfa0ba7', 'MY2GHG', '2026-10-19 17:12:53' + INTERVAL @decalage DAY, 0, NULL, 0 FROM DUAL WHERE EXISTS (SELECT 1 FROM evenement WHERE id_evenement = 2);

INSERT IGNORE INTO evenement_inscription (id_inscription, id_evenement, id_user, statut, code, code_court, date_inscription, present, date_presence, prioritaire) SELECT 1034, 2, 1043, 'inscrit', '0afa1da9d309183973daebe831cc3688', 'NE3M9F', '2026-10-14 10:48:41' + INTERVAL @decalage DAY, 0, NULL, 0 FROM DUAL WHERE EXISTS (SELECT 1 FROM evenement WHERE id_evenement = 2);

INSERT IGNORE INTO evenement_inscription (id_inscription, id_evenement, id_user, statut, code, code_court, date_inscription, present, date_presence, prioritaire) SELECT 1035, 2, 1020, 'inscrit', '4e2a15913c249e52f00182ac8b60ddfc', 'UGYMCK', '2026-10-23 20:14:30' + INTERVAL @decalage DAY, 0, NULL, 0 FROM DUAL WHERE EXISTS (SELECT 1 FROM evenement WHERE id_evenement = 2);

INSERT IGNORE INTO evenement_inscription (id_inscription, id_evenement, id_user, statut, code, code_court, date_inscription, present, date_presence, prioritaire) SELECT 1036, 2, 1028, 'attente', '860d42c332fc80b1543e1e003dfcee64', 'MV58MG', '2026-10-20 15:20:51' + INTERVAL @decalage DAY, 0, NULL, 0 FROM DUAL WHERE EXISTS (SELECT 1 FROM evenement WHERE id_evenement = 2);

INSERT IGNORE INTO evenement_inscription (id_inscription, id_evenement, id_user, statut, code, code_court, date_inscription, present, date_presence, prioritaire) SELECT 1037, 2, 1024, 'attente', '67543269748ac6a5ff385c3bbcc3abd4', 'R6HBGC', '2026-10-21 15:04:05' + INTERVAL @decalage DAY, 0, NULL, 0 FROM DUAL WHERE EXISTS (SELECT 1 FROM evenement WHERE id_evenement = 2);

INSERT IGNORE INTO evenement_inscription (id_inscription, id_evenement, id_user, statut, code, code_court, date_inscription, present, date_presence, prioritaire) SELECT 1038, 2, 1026, 'attente', '60a18f0f35bff87f4677cdcabaf28fc6', 'NNL63S', '2026-10-19 15:59:37' + INTERVAL @decalage DAY, 0, NULL, 0 FROM DUAL WHERE EXISTS (SELECT 1 FROM evenement WHERE id_evenement = 2);

INSERT IGNORE INTO evenement_inscription (id_inscription, id_evenement, id_user, statut, code, code_court, date_inscription, present, date_presence, prioritaire) SELECT 1039, 2, 1027, 'attente', 'ba6938cbb301d8d26efd54b22e53bc9b', '5D3XPV', '2026-10-22 16:17:35' + INTERVAL @decalage DAY, 0, NULL, 0 FROM DUAL WHERE EXISTS (SELECT 1 FROM evenement WHERE id_evenement = 2);

INSERT INTO evenement_inscription (id_inscription, id_evenement, id_user, statut, code, code_court, date_inscription, present, date_presence, prioritaire) VALUES
(1040, 1001, 1040, 'inscrit', 'ae49e04aad38750e9bcdc58bc4bc4708', 'EWJMQL', '2026-10-17 20:35:00' + INTERVAL @decalage DAY, 0, NULL, 0),
(1041, 1001, 1003, 'inscrit', 'dcb8a0893c72a49dd4311d56be7c2683', 'RWSLFU', '2026-10-18 22:36:41' + INTERVAL @decalage DAY, 0, NULL, 0),
(1042, 1001, 1047, 'inscrit', '810473a84fd918659beae9e824a15f8f', '6JKFKN', '2026-10-01 20:40:23' + INTERVAL @decalage DAY, 0, NULL, 0),
(1043, 1001, 1039, 'inscrit', 'aa22aa13b43b16740db6afb4c93822bd', 'JV8YVX', '2026-10-19 21:01:40' + INTERVAL @decalage DAY, 0, NULL, 0),
(1044, 1001, 1030, 'inscrit', '7f82a32ed0f253c751b9a54e9ccf1350', '7M3VQ5', '2026-10-10 08:29:09' + INTERVAL @decalage DAY, 0, NULL, 0),
(1045, 1001, 1048, 'inscrit', '58b2474f6b59c776d7e3a9c27cb12e67', 'YN3TRH', '2026-10-01 18:24:32' + INTERVAL @decalage DAY, 0, NULL, 0),
(1046, 1001, 1050, 'inscrit', '092f419bb9f0e9fce4679697d8e1b833', 'CU3F47', '2026-10-02 22:23:41' + INTERVAL @decalage DAY, 0, NULL, 0),
(1047, 1001, 1025, 'inscrit', 'bd8da03a1b490ac3aa62e19676ee7f06', 'JHMJAF', '2026-10-05 11:36:21' + INTERVAL @decalage DAY, 0, NULL, 0),
(1048, 1001, 1049, 'inscrit', 'a1faf1efac8e5b06de65443be747f97c', 'J7URBA', '2026-10-09 19:46:30' + INTERVAL @decalage DAY, 0, NULL, 0),
(1049, 1001, 1046, 'inscrit', '6971da95eff9df666c9a921a53ba303c', 'NTXE5Z', '2026-10-01 20:00:45' + INTERVAL @decalage DAY, 0, NULL, 0),
(1050, 1001, 1043, 'inscrit', '41a4195c7f659c7db2ae43133025f548', '4DJQGS', '2026-10-03 09:47:47' + INTERVAL @decalage DAY, 0, NULL, 0),
(1051, 1001, 1017, 'inscrit', 'f5a0b97d0aaad3201ec40478d6db28ed', '9J6HKU', '2026-10-07 21:53:14' + INTERVAL @decalage DAY, 0, NULL, 0),
(1052, 1001, 1012, 'inscrit', 'e84b982f00ac4980631ace27447ac334', 'KGBZX9', '2026-10-16 12:36:43' + INTERVAL @decalage DAY, 0, NULL, 0),
(1053, 1001, 1045, 'inscrit', '716c4832273acc53806b84460ee3ef95', 'R8Q2V6', '2026-10-05 10:09:49' + INTERVAL @decalage DAY, 0, NULL, 0),
(1054, 1001, 1004, 'inscrit', 'baf7cfcc2328a29287a233b9c25637d5', 'GB42X8', '2026-10-15 15:03:58' + INTERVAL @decalage DAY, 0, NULL, 0),
(1055, 1001, 1028, 'inscrit', 'b24e584fd6254415d53fcf4b5beb6203', 'DM8USY', '2026-10-06 12:22:57' + INTERVAL @decalage DAY, 0, NULL, 0),
(1056, 1001, 1021, 'inscrit', '1f2b6dc390d09389166a535ef3c320c6', '9HPHMM', '2026-10-10 19:17:13' + INTERVAL @decalage DAY, 0, NULL, 0),
(1057, 1001, 1035, 'inscrit', '81d5a0622080001b6daf0ea1d4556595', 'ZMGQ33', '2026-10-12 10:16:49' + INTERVAL @decalage DAY, 0, NULL, 0),
(1058, 1001, 1019, 'inscrit', 'ffd7920be53c06abb32ddd1883597804', 'TMNUH8', '2026-10-16 19:38:18' + INTERVAL @decalage DAY, 0, NULL, 0),
(1059, 1001, 1026, 'inscrit', 'b2ff066743ff91555ead4b86efab4df1', '8NTTPK', '2026-10-06 14:43:21' + INTERVAL @decalage DAY, 0, NULL, 0),
(1060, 1002, 1043, 'inscrit', 'd8bc2d465aee751e42d3c848f91008df', 'M22LV8', '2026-09-11 15:34:46' + INTERVAL @decalage DAY, 1, '2026-09-19 11:00:00' + INTERVAL @decalage DAY, 0),
(1061, 1002, 1003, 'inscrit', '89b4959fabd5869c24a0ec3d33e42d98', 'J8965D', '2026-09-07 17:37:35' + INTERVAL @decalage DAY, 1, '2026-09-19 12:00:00' + INTERVAL @decalage DAY, 0),
(1062, 1002, 1033, 'inscrit', '8798329f6332862dad365040d0224228', '3TL89L', '2026-09-07 21:07:48' + INTERVAL @decalage DAY, 1, '2026-09-19 10:00:00' + INTERVAL @decalage DAY, 0),
(1063, 1002, 1020, 'inscrit', '0423d0f2fc2a974d96a77f1df78c839e', 'TVWXLH', '2026-09-01 21:57:50' + INTERVAL @decalage DAY, 1, '2026-09-19 10:00:00' + INTERVAL @decalage DAY, 0),
(1064, 1002, 1022, 'inscrit', '1dcc94fd262dfadfc189a25010253e2c', '6HAY93', '2026-09-02 18:36:36' + INTERVAL @decalage DAY, 1, '2026-09-19 10:00:00' + INTERVAL @decalage DAY, 0),
(1065, 1002, 1044, 'inscrit', '2649bb4c87364b2e91e588e5440c4972', 'QTXWCK', '2026-08-29 22:29:55' + INTERVAL @decalage DAY, 1, '2026-09-19 10:00:00' + INTERVAL @decalage DAY, 0),
(1066, 1002, 1011, 'inscrit', '6c929d907fb7d6c2db6d995457b5113a', 'HCAYAP', '2026-09-16 10:33:07' + INTERVAL @decalage DAY, 1, '2026-09-19 11:00:00' + INTERVAL @decalage DAY, 0),
(1067, 1002, 1035, 'inscrit', '19d13544584a1e39ef8443e3ab5642aa', '6LDFRU', '2026-09-01 16:42:05' + INTERVAL @decalage DAY, 1, '2026-09-19 12:00:00' + INTERVAL @decalage DAY, 0),
(1068, 1002, 1032, 'inscrit', '6e299732cefc5d5f319d0e5ec1db2df1', '66S98S', '2026-09-15 12:18:56' + INTERVAL @decalage DAY, 1, '2026-09-19 11:00:00' + INTERVAL @decalage DAY, 0),
(1069, 1002, 1023, 'inscrit', '83da16ea16b55898305107cda6e6f14a', 'UGTCFE', '2026-09-13 11:31:58' + INTERVAL @decalage DAY, 1, '2026-09-19 10:00:00' + INTERVAL @decalage DAY, 0),
(1070, 1002, 1034, 'inscrit', 'f7e89caecfffc830332b3566a3360115', 'K6TALC', '2026-09-04 12:32:45' + INTERVAL @decalage DAY, 1, '2026-09-19 11:00:00' + INTERVAL @decalage DAY, 0),
(1071, 1002, 1046, 'inscrit', '26470b5c3a56cbee03f1c388c05d04aa', 'TRVNQ8', '2026-08-29 14:24:10' + INTERVAL @decalage DAY, 1, '2026-09-19 10:00:00' + INTERVAL @decalage DAY, 0),
(1072, 1002, 1015, 'inscrit', '7e81c1d4ddff7c02e01749b19b390a93', 'HQAZAR', '2026-09-03 19:41:07' + INTERVAL @decalage DAY, 1, '2026-09-19 11:00:00' + INTERVAL @decalage DAY, 0),
(1073, 1002, 1045, 'inscrit', '57909724c5c41b94a84558f79ea6a397', 'L69CCV', '2026-09-09 19:04:06' + INTERVAL @decalage DAY, 1, '2026-09-19 10:00:00' + INTERVAL @decalage DAY, 0),
(1074, 1002, 1042, 'inscrit', '31715a79bc0f6e80d6de7b2edfe4b026', '8E5734', '2026-09-01 19:40:41' + INTERVAL @decalage DAY, 1, '2026-09-19 12:00:00' + INTERVAL @decalage DAY, 0),
(1075, 1002, 1017, 'inscrit', 'c2c99af7a929af925ec224817fea9acb', '89NFGC', '2026-09-07 14:03:42' + INTERVAL @decalage DAY, 1, '2026-09-19 12:00:00' + INTERVAL @decalage DAY, 0),
(1076, 1002, 1030, 'inscrit', '44cd5f12cc47822ba35c2030ed92b98f', 'S2MD89', '2026-08-27 20:17:45' + INTERVAL @decalage DAY, 1, '2026-09-19 11:00:00' + INTERVAL @decalage DAY, 0),
(1077, 1002, 1050, 'inscrit', '7caadf89f5a64485462ee9d0ed510cc4', 'ZJU85L', '2026-09-10 09:31:44' + INTERVAL @decalage DAY, 1, '2026-09-19 10:00:00' + INTERVAL @decalage DAY, 0),
(1078, 1002, 1029, 'inscrit', '3178ed3c80954a0f3e7bcce66c5db0b6', '27Q9XD', '2026-09-05 19:06:23' + INTERVAL @decalage DAY, 1, '2026-09-19 10:00:00' + INTERVAL @decalage DAY, 0),
(1079, 1002, 1026, 'inscrit', 'e65114264d3f235c788f8956816f3c9d', '3YJMRX', '2026-08-27 16:36:14' + INTERVAL @decalage DAY, 1, '2026-09-19 12:00:00' + INTERVAL @decalage DAY, 0),
(1080, 1002, 1008, 'inscrit', '6c53920c5263e330ae614c2c0c3df736', 'L9QW66', '2026-09-05 11:29:28' + INTERVAL @decalage DAY, 1, '2026-09-19 10:00:00' + INTERVAL @decalage DAY, 0),
(1081, 1002, 1049, 'inscrit', 'fbfb2f95c744946836b4df0a354ac591', '9ZWRCE', '2026-09-11 12:59:58' + INTERVAL @decalage DAY, 1, '2026-09-19 10:00:00' + INTERVAL @decalage DAY, 0),
(1082, 1002, 1018, 'inscrit', '0e099799aa187db60d981528ceeb37f1', 'BFM6AW', '2026-09-05 09:56:25' + INTERVAL @decalage DAY, 0, NULL, 0),
(1083, 1002, 1031, 'inscrit', '6891e429f513125e81730c30212e2ee6', '7KV5GN', '2026-08-28 09:33:12' + INTERVAL @decalage DAY, 0, NULL, 0),
(1084, 1002, 1009, 'inscrit', '7c0d8c3f4fca6c21d3349ab9ff70e77d', 'PTRZZ9', '2026-08-27 10:56:23' + INTERVAL @decalage DAY, 0, NULL, 0),
(1085, 1002, 1004, 'inscrit', '4ac4b55df7abbe81f6f6b24619e7107f', 'YBJFUS', '2026-09-13 10:45:49' + INTERVAL @decalage DAY, 0, NULL, 0),
(1086, 1002, 1012, 'inscrit', 'f73f9bb24a2430754f60c06f2f699f0c', 'EVQ5AN', '2026-09-14 11:48:01' + INTERVAL @decalage DAY, 0, NULL, 0),
(1087, 1003, 1015, 'inscrit', '46dac9d41c191d0f05f1b8341245a027', 'W676M8', '2026-10-23 20:07:04' + INTERVAL @decalage DAY, 0, NULL, 0),
(1088, 1003, 1045, 'inscrit', 'a0b9c3a98a32f8d8d76ad763e0f2c0d5', 'L4MLEM', '2026-10-18 19:58:47' + INTERVAL @decalage DAY, 0, NULL, 0),
(1089, 1003, 1020, 'inscrit', 'b276a5b7b3d5b9e042ed575011dc59b0', 'G9QTYZ', '2026-10-16 22:42:23' + INTERVAL @decalage DAY, 0, NULL, 0),
(1090, 1003, 1041, 'inscrit', 'e88e934f4dfd091b7e81f1522de5fd06', 'VP3UF7', '2026-10-18 09:37:16' + INTERVAL @decalage DAY, 0, NULL, 0),
(1091, 1003, 1044, 'inscrit', 'c79ee05825c8b3c750ee41096f67f2f3', 'LWVJYA', '2026-10-13 10:51:52' + INTERVAL @decalage DAY, 0, NULL, 0),
(1092, 1003, 1028, 'inscrit', 'a3bef633d687e81c0dc6706c6723dc7c', 'BAJWSC', '2026-10-17 14:08:17' + INTERVAL @decalage DAY, 0, NULL, 0),
(1093, 1003, 1006, 'inscrit', '98d0d2d36933a4f897954a4cdf48e842', 'C2JCCJ', '2026-10-19 13:35:54' + INTERVAL @decalage DAY, 0, NULL, 0),
(1094, 1003, 1013, 'inscrit', '02a958300ada542aaa8471b8775f2c4d', '3DE73N', '2026-10-10 20:38:59' + INTERVAL @decalage DAY, 0, NULL, 0);

INSERT INTO newsletter_abonne (email, actif, date_inscription) VALUES
('marie.lacroix@example.com', 1, '2026-04-12 08:22:15' + INTERVAL @decalage DAY),
('antoine.vidal@example.com', 1, '2026-06-26 08:30:09' + INTERVAL @decalage DAY),
('sophie.renard@example.com', 1, '2026-05-29 14:36:14' + INTERVAL @decalage DAY),
('julien.marchal@example.com', 1, '2025-11-16 10:02:46' + INTERVAL @decalage DAY),
('claire.boyer@example.com', 1, '2025-09-17 10:47:26' + INTERVAL @decalage DAY),
('pierre.colin@example.com', 1, '2025-10-10 09:25:23' + INTERVAL @decalage DAY);

INSERT INTO livre (id_livre, titre, auteur, id_categorie, is_valide) VALUES
(1099, 'Le Mage du Kremlin', 'Giuliano da Empoli', (SELECT id_categorie FROM categorie WHERE slug = 'roman'), 0);

INSERT INTO exemplaire (id_exemplaire, id_livre, etat, prix, type_offre, is_disponible, quantite, id_proprietaire, statut_annonce, description, mode_remise, ville_remise, motif_moderation, id_moderateur, date_creation, date_moderation) VALUES
(1801, 1033, 'bon', 4.50, 'vente', 1, 1, 1003, 'publiee', 'Lu une fois, quelques marques de lecture sur la tranche. Toutes les pages sont propres.', 'main_propre', 'Lyon', NULL, (SELECT MIN(id) FROM `user` WHERE role = 'admin'), '2026-10-14 18:12:00' + INTERVAL @decalage DAY, '2026-10-15 09:30:00' + INTERVAL @decalage DAY),
(1802, 1028, 'tres bon', 7.00, 'vente', 1, 1, 1004, 'publiee', 'Album cartonné en très bon état, couverture sans pli.\nJe peux le poster ou vous le remettre près de la Part-Dieu.', 'les_deux', 'Lyon', NULL, (SELECT MIN(id) FROM `user` WHERE role = 'admin'), '2026-10-16 20:05:00' + INTERVAL @decalage DAY, '2026-10-17 10:02:00' + INTERVAL @decalage DAY),
(1803, 1036, 'use', 1.50, 'vente', 1, 1, 1017, 'publiee', 'Exemplaire de poche bien aimé : couverture un peu cornée, texte complet.', 'envoi', NULL, NULL, (SELECT MIN(id) FROM `user` WHERE role = 'admin'), '2026-10-18 11:40:00' + INTERVAL @decalage DAY, '2026-10-18 15:10:00' + INTERVAL @decalage DAY),
(1804, 1025, 'bon', 3.00, 'vente', 1, 1, 1020, 'publiee', 'Édition Folio, légèrement jaunie. Remise possible près de la gare de Lyon.', 'main_propre', 'Paris', NULL, (SELECT MIN(id) FROM `user` WHERE role = 'admin'), '2026-10-19 08:55:00' + INTERVAL @decalage DAY, '2026-10-19 14:20:00' + INTERVAL @decalage DAY),
(1805, 1029, 'tres bon', 4.00, 'vente', 1, 1, 1031, 'publiee', NULL, 'les_deux', 'Lyon', NULL, (SELECT MIN(id) FROM `user` WHERE role = 'admin'), '2026-10-20 17:30:00' + INTERVAL @decalage DAY, '2026-10-21 09:00:00' + INTERVAL @decalage DAY),
(1806, 1027, 'bon', 14.00, 'vente', 1, 1, 1012, 'publiee', 'Quelques passages surlignés au crayon dans les deux premiers chapitres. Idéal pour un étudiant en informatique.', 'envoi', NULL, NULL, (SELECT MIN(id) FROM `user` WHERE role = 'admin'), '2026-10-21 21:15:00' + INTERVAL @decalage DAY, '2026-10-22 10:45:00' + INTERVAL @decalage DAY),
(1807, 1038, 'neuf', 5.50, 'vente', 1, 1, 1045, 'en_validation', 'Reçu en double à Noël, jamais ouvert.', 'main_propre', 'Lyon', NULL, NULL, '2026-10-25 19:20:00' + INTERVAL @decalage DAY, NULL),
(1808, 1099, 'bon', 6.00, 'vente', 1, 1, 1006, 'en_validation', 'Grand format, bon état général. Le livre n''est pas encore au catalogue.', 'main_propre', 'Paris', NULL, NULL, '2026-10-26 08:10:00' + INTERVAL @decalage DAY, NULL),
(1809, 1043, 'use', 60.00, 'vente', 1, 1, 1009, 'refusee', 'Couverture abîmée, mais le livre se lit très bien.', 'envoi', NULL, 'Prix trop élevé pour un exemplaire usé : entre 2 et 5 € conviendrait mieux.', (SELECT MIN(id) FROM `user` WHERE role = 'admin'), '2026-10-22 13:00:00' + INTERVAL @decalage DAY, '2026-10-23 09:15:00' + INTERVAL @decalage DAY),
(1810, 1021, 'tres bon', 5.00, 'vente', 1, 1, 1050, 'publiee', 'Lu une seule fois, comme neuf. Remise possible dans le Vieux-Lille.', 'main_propre', 'Lille', NULL, (SELECT MIN(id) FROM `user` WHERE role = 'admin'), '2026-10-23 18:40:00' + INTERVAL @decalage DAY, '2026-10-24 09:05:00' + INTERVAL @decalage DAY),
(1811, 1034, 'bon', 3.50, 'vente', 1, 1, 1050, 'retiree', NULL, 'les_deux', 'Lille', NULL, (SELECT MIN(id) FROM `user` WHERE role = 'admin'), '2026-10-12 10:00:00' + INTERVAL @decalage DAY, '2026-10-12 16:30:00' + INTERVAL @decalage DAY);

COMMIT;
