# Plan F5 : emprunt de livres (mode bibliothèque)

## Fonctionnement en bref
1. Un gestionnaire met un exemplaire du magasin en emprunt depuis la gestion des exemplaires (`admin/exemplaires.php`) : soit il prend un exemplaire du stock de vente (le stock baisse de 1), soit il ajoute un exemplaire neuf réservé à l'emprunt. Réglages par exemplaire : durée (21 jours par défaut), renouvellements autorisés (2 par défaut), blocage en cas de retard (oui par défaut).
2. Un lecteur connecté clique « Emprunter » sur la fiche livre. Le site vérifie qu'il peut emprunter (rôle, compte, email vérifié, aucun retard, aucun blocage, moins de 3 emprunts ou réservations en cours). L'exemplaire passe « réservé » ; le lecteur reçoit un mail et un code de retrait (6 caractères + QR). Une conversation avec l'équipe s'ouvre.
3. Si aucun exemplaire n'est libre, le lecteur peut se mettre en liste d'attente (3 livres au maximum). Dès qu'un exemplaire se libère, il est réservé automatiquement pour le premier lecteur de la liste qui peut emprunter (mail).
4. La réservation non retirée expire après 3 jours ouvrés (fin de journée, samedi et dimanche non comptés).
5. Au magasin, le gestionnaire valide le retrait par le code ou en scannant le QR : date de début, date de retour prévue, exemplaire « emprunté », ticket d'emprunt PDF.
6. Le lecteur peut renouveler (2 fois, +14 jours) si l'emprunt n'est pas en retard et si personne n'attend ce livre.
7. Rappels par mail 3 jours avant et le jour du retour ; en retard, au lendemain puis chaque semaine. Les retards sont en tête de la page Emprunts du back-office.
8. Au retour, le gestionnaire marque l'emprunt « rendu ». À l'heure : +5 points (5 emprunts récompensés par mois au maximum) et badge « Habitué de la bibliothèque ». En retard (si l'exemplaire applique le blocage) : blocage des emprunts pendant autant de jours que de jours de retard, 30 au maximum. Le lecteur voit son blocage et sa date de fin ; l'équipe peut le lever avec un motif.
9. Toutes les vérifications (expiration, rappels, relances) se font à l'ouverture des pages concernées (`verifierEmprunts()`), sans tâche planifiée.

## Tables et colonnes (`sql/maj_emprunts.sql`, aussi dans `sql/biblioccaz.sql`)
- `exemplaire` (colonnes ajoutées) : `statut_pret` (`disponible`, `reserve`, `emprunte`), `duree_emprunt_jours` (21), `renouvellements_max` (2), `blocage_retard` (1). Un exemplaire d'emprunt = `id_proprietaire` NULL, `type_offre = 'emprunt'`, `quantite = 1`. Il n'entre jamais dans le panier ni dans le stock de vente (toutes les requêtes du magasin exigent `type_offre = 'vente'`). Retiré de l'emprunt = `is_disponible = 0`.
- `emprunt` : `id_emprunt`, `id_exemplaire`, `id_user`, `statut`, `code_retrait` (unique), `duree_jours`, `renouvellements_max`, `nb_renouvellements`, `date_reservation`, `date_expiration_reservation`, `date_retrait`, `date_retour_prevue`, `date_retour`, `jours_retard`, `id_valide_par`, `id_retour_par`, `rappel_avant`, `rappel_jour`, `date_derniere_relance`, `nb_relances`, `motif_annulation`.
- `blocage_emprunt` : `id_blocage`, `id_user`, `id_emprunt`, `jours`, `date_debut`, `date_fin`, `motif`, `date_levee`, `id_leve_par`, `motif_levee`. Actif si non levé et `date_fin` future.
- `emprunt_attente` : `id_attente`, `id_livre`, `id_user`, `date_demande`, unique (`id_livre`, `id_user`).

## Statuts d'un emprunt
`reserve` (à retirer) → `en_cours` (emprunté) → `rendu`. Sorties possibles d'une réservation : `expire` (non retirée à temps), `annule` (par le lecteur ou l'équipe, avec motif). Un emprunt `en_cours` dont la date de retour est passée est « en retard » (calculé, pas un statut).

## Pages
Lecteur :
- `detail_livre.php` : bloc « Emprunter » (disponible, liste d'attente, raison du refus), lignes « À emprunter » dans « Toutes les offres ».
- `catalogue.php` : filtre « Emprunt » (origine `emprunt` dans `sqlOffres`), badge « À emprunter » sur la carte ; l'emprunt ne compte pas dans le prix affiché.
- `mes_emprunts.php` : blocage éventuel, réservations (code + QR, ticket), emprunts en cours (date de retour, Renouveler), listes d'attente, historique, conversation avec l'équipe.
- `emprunt_action.php` : réserver, annuler, renouveler, s'inscrire et quitter une liste d'attente (POST + CSRF).
- `emprunt_ticket.php` : ticket PDF (Dompdf) d'un emprunt du lecteur.
- `header.php` : « Mes emprunts » dans le menu du compte, avec compteur.

Équipe (droit `catalogue` : admin, gestionnaire) :
- `admin/emprunts.php` : valider un retrait par code ou QR, onglets « À préparer », « En cours » (retards en tête), « Historique », « Blocages » (lever avec motif), « Exemplaires ».
- `admin/emprunt_ticket.php` : ticket PDF.
- `admin/exemplaires.php` : bloc « Emprunt » (mettre en emprunt depuis le stock ou ajouter, réglages, retirer, remettre). Correction : les boutons +, − et « vider » ne touchent qu'aux offres de vente.
- `admin/conversation.php` : l'équipe peut répondre dans une conversation d'emprunt.
- Menu, compteur et « À traiter » de l'accueil du back-office.

## Mails
Réservation confirmée (avec code), réservation attribuée depuis la liste d'attente, réservation expirée, réservation annulée par l'équipe, retrait validé (date de retour), renouvellement, rappel J-3, rappel jour J, relance de retard, retour enregistré (avec points ou blocage), blocage levé.

## Règles
- Peuvent emprunter : rôles `client` et `auteur`, compte actif (non suspendu), email vérifié. Pas l'équipe.
- 3 emprunts ou réservations en cours au maximum ; aucun emprunt en retard ; aucun blocage actif.
- Une seule réservation ou un seul emprunt en cours par livre et par lecteur.
- Transactions avec `FOR UPDATE` sur l'exemplaire lors de la réservation, du retrait, du retour.
- Points : type `emprunt`, référence `emprunt:<id>`, +5 si rendu à l'heure, 5 par mois civil au maximum ; badge `habitue_bibliotheque` (paliers 1, 10, 30 emprunts rendus à l'heure).
- Messagerie : conversation `emprunt` (`id_objet = id_emprunt`), l'emprunteur seul participant, l'équipe répond depuis le back-office ; fermée au retour, à l'expiration ou à l'annulation.

## Décisions prises seul (à relire)
- Liste d'attente ajoutée (le cahier des charges parle de « personne n'attend ce livre »).
- « Éventuelles pénalités » = le blocage, activable ou non par exemplaire ; aucune pénalité financière.
- Le ticket PDF affiche le code en grand ; le QR est sur la page « Mes emprunts » (généré en JS comme pour les dons et les billets).
- Un exemplaire d'emprunt n'est jamais supprimé s'il a un historique ; il se retire de l'emprunt.

## Tests prévus
`php -l`, parcours de toutes les pages (y compris « Erreur de connexion » et « SQLSTATE »), parcours complet (réservation, limite de 3, refus de l'équipe, retard qui bloque, expiration, liste d'attente, retrait par code, ticket PDF, renouvellement et ses refus, rappels, relances, retour à l'heure et points, retour en retard et blocage, levée, droits, CSRF, mails dans Mailpit), non-régression (achat, annulation de commande, demande d'achat entre lecteurs, réservation d'un don, inscription à un événement).

## Validation du 27 octobre 2026

- Parcours métier, pages HTTP, droits, CSRF, tickets PDF, doubles soumissions et réservations concurrentes testés sur une copie indépendante avec compte SQL limité à une base de test.
- Non-régression exécutée : achat magasin et annulation, vente entre lecteurs jusqu'à confirmation, réservation et annulation de don, inscription et promotion de liste d'attente événement, soldes de points et messagerie.
- Correction de la collision de fonctions avec les événements : `positionAttenteEmprunt()` est propre à F5.
- Correction de la visibilité du retard lorsque trois emprunts sont actifs et précision des exemplaires réservés dans la fiche livre.
- Migrations rejouées et migration d'une ancienne structure testée ; conservation du réglage de pénalité pris à la réservation vérifiée.
- Validation visuelle interactive desktop/mobile et lecture physique du QR encore à effectuer : le navigateur de test est indisponible. Le téléchargement PDF est vérifié techniquement, sa mise en page n'est pas validée visuellement.
- Recette complémentaire : 45 contrôles ciblés HTML/PDF réussis sans relancer les 141 tests. IDs dupliqués de la recherche par code et conflit de variable des compteurs avec l'entête corrigés, sémantique accessible améliorée, marge blanche du QR portée à quatre modules. Vérification avec affichage et journal des erreurs PHP activés : aucun avertissement détecté au dernier passage. Chrome/Edge headless et rendu PDF bloqués par l'environnement ; captures responsive et scan physique restent non exécutés. Deux PDF de recette conservés dans `docs/captures/f5-ticket-*.pdf`.
- Aucune migration importée dans la base principale. Les vérifications automatiques ne remplacent pas la recette visuelle avant présentation.

## Lignes du document « Fonctionnalités du site » qui changent
Les lignes F5 (mise en emprunt, réservation, retrait et ticket, retours et blocages, relances) sont développées et testées fonctionnellement en environnement isolé. La direction graphique et la recette UX/UI ont été validées manuellement le 28 octobre 2026. Le scan physique QR sur smartphone reste non attesté.

## Préparation de livraison du 28 octobre 2026

- Base principale examinée en lecture seule : les tables et réglages initiaux F5 existent déjà ; seule la colonne `emprunt.blocage_retard` manque.
- Migration nécessaire pour cet état : `sql/maj_emprunts_securite.sql`. Pour une base sans F5, l'ordre reste `sql/maj_emprunts.sql`, puis `sql/maj_emprunts_securite.sql`.
- Sauvegarde complète horodatée créée dans sauvegardes, restaurée dans une base isolée ; structures, nombres de lignes et checksums des 33 tables identiques avant test de migration.
- Migration de sécurité et réexécution validées sur la restauration ; encodage utf8mb4 vérifié. Aucun import sur la base principale.
- Syntaxe des 24 fichiers PHP modifiés/nouveaux et git diff --check valides. Les campagnes fonctionnelles précédentes sont conservées sans réexécution inutile.
- L'import réel, une éventuelle restauration réelle, les commits et le push restent soumis à autorisation explicite.

## Installation contrôlée du 28 octobre 2026

- Après autorisation explicite, seule `sql/maj_emprunts_securite.sql` a été importée sur biblioccaz le 28 octobre 2026 à 02:18:19 (heure locale), avec le client MySQL XAMPP et utf8mb4. Import terminé avec un code 0.
- Préconditions vérifiées : branche refonte, migration identique au fichier testé et archivé, sauvegarde précédente intacte. Deux tables ayant évolué depuis cette sauvegarde, un nouveau dump a été créé puis restauré et comparé sur une base isolée avant import.
- `emprunt.blocage_retard` est désormais TINYINT(1) NOT NULL DEFAULT 1. Les 33 tables et leurs nombres de lignes sont conservés ; aucune autre structure modifiée, checksums des 32 autres tables inchangés. Encodage contrôlé sans caractère cassé.
- Requêtes F5 exécutées en lecture seule sur biblioccaz ; aucun parcours de réservation, retrait, renouvellement, retour, attribution de points ou messagerie n'a écrit de donnée métier réelle. La validation fonctionnelle de ces parcours reste celle de la base isolée.
- Aucune migration F5 restante pour le schéma observé. Aucun autre import, restauration, commit, push ou changement de branche effectué. Le scan physique QR reste non attesté.
