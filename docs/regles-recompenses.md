# Administration des récompenses

La page « Règles de récompenses » du back-office est réservée aux administrateurs actifs. Elle permet de modifier les points par action, les plafonds mensuels avis/ventes/emprunts, les seuils des six niveaux et les paliers des badges existants. Les identités des badges, leurs types d'activité, les avantages des niveaux et les règles des bons restent inchangés.

Chaque champ est validé côté serveur : entiers non négatifs, points par euro de 0 à 10, récompenses de 0 à 1 000, plafonds de 0 à 100, niveaux strictement croissants commençant à zéro et paliers de badges strictement croissants. Les objectifs Explorateur et Pilier sont limités aux six univers/badges classiques existants. Zéro désactive les points d'une action ; aucun mouvement artificiel à zéro n'est créé.

## Application et historique

- Les changements affectent les prochaines attributions et les prochains contrôles de niveau/badge ; ils ne réécrivent ni les points déjà enregistrés ni les montants des avis/ventes en attente.
- Une annulation retire le montant réellement accordé à sa commande, y compris le bonus initial, même si le barème a changé depuis.
- Les badges déjà obtenus sont conservés si un seuil augmente. Abaisser un seuil peut débloquer un nouveau badge sur l'activité existante lors du prochain contrôle : la confirmation du formulaire annonce explicitement cet effet.
- Les niveaux comparent le cumul existant aux nouveaux seuils. Les dépenses ne sont pas transformées en perte de cumul ; le calcul existant est conservé.
- Les modifications sont versionnées, avec administrateur, date et anciennes/nouvelles valeurs. Un formulaire devenu ancien est refusé plutôt que d'écraser une modification concurrente. Les références uniques du registre de points et des badges restent en place contre les doubles attributions.
- Les comptes rendus affichent les montants stockés, pas une reconstruction du passé avec le barème actuel.

Le formulaire exige POST, CSRF et confirmation. Les droits sont contrôlés par la page et de nouveau sous verrou dans l'enregistrement. Les règles d'une requête sont lues comme un instantané cohérent ; une autre requête voit la version nouvellement enregistrée.

## Installation

Migration nouvelle : `sql/maj_regles_recompenses.sql`. L'installation complète l'inclut également. Sur une base existante, sauvegarder et vérifier la restauration sur copie avant toute application autorisée. Ne pas réimporter le schéma complet ni les données de démonstration. Aucun import principal n'est réalisé par la livraison du code.

La migration est rejouable et conserve une configuration déjà personnalisée. Sans migration, le barème historique est utilisé et l'interface indique que l'édition est indisponible. Aucun DDL automatique n'est exécuté par l'application. Conserver code compatible et sauvegarde pour tout retour arrière ; aucune restauration automatique de la base principale.

## Profil actif : décision encore nécessaire

La référence fonctionnelle donne des exemples de connexion récente et d'actions sur une période, mais ne fixe aucun délai, nombre minimal, combinaison des critères ni avantage. Le compte administrativement actif et la connexion F6 ne constituent pas un profil de participation actif. Aucun critère arbitraire et aucun privilège de participation ne sont activés dans ce lot. L'administration des conditions de ce statut reste à compléter après définition métier.

## Vérifications et limites

Recette isolée : droits, CSRF, confirmation, valeurs invalides, changements concurrents, historique, barème effectif des achats/dons/ventes/emprunts/avis, calculs après remise/bon, annulation historique, plafonds, conservation des badges et repli sans migration. Les présences utilisent le même accès au barème ; une recette dédiée du parcours complet des présences reste distincte.

La recette F6 et récupération est relancée après intégration. Emails fictifs capturés localement, aucune écriture sur la base principale. Les tests ne constituent pas une observation visuelle desktop/mobile ; l'interface conserve les cartes/formulaires Bootstrap du back-office avec labels, champs numériques et historique repliable.
