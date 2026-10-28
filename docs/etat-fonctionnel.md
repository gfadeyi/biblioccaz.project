# État fonctionnel et prochaine priorité

Audit de reprise du 28 octobre 2026, sur `refonte`, à partir du commit `5c8bd1a`.

## Sources et portée

Sources lues : README, feuille de route, plan F5, workflow commun, instructions locales, rapport de relais et rapports de livraison/migration F5. La revue du code porte sur les points d'entrée et bibliothèques cités ci-dessous ; ce n'est pas un audit exhaustif de chaque ligne.

Le document Google Docs [« Fonctionnalités du site »](https://docs.google.com/document/d/12eXyG-hgVNoNivrep4W7dBiC9N9wzH8VLaj-uGHEDsA/edit) est la référence fonctionnelle principale désignée par le propriétaire. Son contenu n'a pas pu être consulté pendant cette session : lecture web indisponible et aucune session documentaire connectée. Le cahier des charges original n'a pas été retrouvé dans le dépôt. La numérotation complète F1 à Fn et les exigences manquantes ne peuvent donc pas être certifiées. Les fonctionnalités ci-dessous sont rapprochées des documents disponibles, sans inventer de nouvelles exigences ni supposer leur conformité complète. Aucun statut Google Docs n'a été modifié.

Une présence dans le code ne vaut pas recette. Les journaux de tests conservés sont des preuves historiques sur l'environnement isolé ; ils n'attestent ni une nouvelle exécution ni un parcours réel sur la base principale.

## 1. Terminées et vérifiées dans le périmètre attesté

| Périmètre | Code confirmé | Preuves et limites |
|---|---|---|
| Emprunts F5 | `emprunts_lib.php`, actions et pages lecteur, tickets, back-office, migrations | Campagne historique de 141 assertions, contrôles complémentaires HTML/PDF et UX, journaux d'attente/concurrence/mails ; recette graphique validée manuellement. Le scan physique QR et un audit WCAG exhaustif restent non attestés. |
| Compatibilité avec F5 | Filtres magasin dans `livres_lib.php`, commandes, demandes de vente, dons, événements et points | Journal de non-régression : achat/annulation et stocks, cycle de vente confirmé, réservation/annulation de don et conversation, inscription/attente/promotion d'événement et cohérence de solde. Uniquement ces scénarios sont attestés, pas l'ensemble des modules. |
| Livraison SQL F5 | `maj_emprunts.sql`, `maj_emprunts_securite.sql`, installation complète | Rapport d'import autorisé : colonne `blocage_retard`, intégrité et encodage vérifiés. Aucun nouveau contrôle SQL lors de cette reprise. |

## 2. Implémentées mais incomplètes

| Périmètre | Constat du code et écart |
|---|---|
| Parcours « Rentrée & études » de la feuille de route | Les colonnes `livre.niveau_scolaire` et `livre.matiere` sont déclarées dans l'installation et la migration des univers. Aucun usage PHP de ces champs n'a été trouvé. Le catalogue recherche déjà par ISBN, mais ses filtres n'exposent ni niveau ni matière. La saisie et le filtrage spécialisés restent à définir et à réaliser ; les catégories/tags existants ne constituent pas automatiquement ce parcours. |

## 3. Non commencées ou explicitement différées

| Périmètre | État |
|---|---|
| Assistant d'installation initiale de l'administrateur | Mentionné comme différé dans les instructions ; aucune page dédiée identifiée. Nécessité contractuelle à confirmer dans le cahier des charges. Un compte initial existe déjà dans le script d'installation. |
| BibliOccaz Demo | Phase future explicitement exclue de la session. Aucun hébergement, compte, tunnel, adaptation de code ou déploiement à lancer. |
| Autres exigences absentes des documents disponibles | Non classables sans le cahier des charges original. Ne pas conclure qu'elles sont réalisées ou inexistantes. |

## 4. Présentes, nécessitant corrections ou tests supplémentaires

| Périmètre | Code observé | Contrôles restant à couvrir |
|---|---|---|
| Comptes, connexion, récupération du mot de passe | Pages d'inscription/connexion, captcha, récupération et changement de mot de passe | Récupération sécurisée et vérifiée par 53 assertions isolées le 28 octobre 2026 : CSRF aux deux étapes, anti-répétition, lien expiré/consommé, usage unique concurrent, connexion avec nouveau mot de passe et changement connecté. Les autres parcours du module Comptes ne sont pas déclarés entièrement recettés. Aucune écriture sur la base principale. Limites et propositions de statuts ci-dessous. |
| Catalogue et découverte | `catalogue.php`, `livres_lib.php`, catégories, cartes, recherche titre/auteur/ISBN/éditeur et offres multiples | Matrice complète des filtres, pagination, entrées malformées et accessibilité à compléter ; le défaut de parcours scolaire est décrit séparément. |
| Commandes et fidélité au paiement | `panier_calcul.php`, `confirmation_achat.php`, `commandes_lib.php` ; verrous, bons et bonus de niveau présents | Non-régression achat/annulation attestée ; recette complète des seuils, bons, concurrence et remboursements non démontrée par le seul journal F5. |
| Annonces et ventes F3 | `annonces_lib.php`, `ventes_lib.php`, reçus et écrans de modération | Parcours de vente nominal attesté par non-régression ; litiges, plafonds mensuels, expirations et tous les droits nécessitent leurs preuves dédiées. |
| Dons | Pages de création/réservation et code de retrait, bibliothèques et back-office | Réservation et annulation attestées ; création, retrait et scan physique ne sont pas exhaustivement démontrés dans les journaux relus. |
| Événements et propositions auteur | `evenements_lib.php` : inscriptions, attente, promotion, propositions, validation/refus | Inscription et promotion attestées ; billets, présence et proposition auteur à recetter spécifiquement si modifiés. |
| Points, niveaux, badges F7 | `points_lib.php`, page dédiée et intégration au paiement ; bonus, livraison par niveau et bon mensuel d'anniversaire d'inscription présents | Ne pas redévelopper F7. Récompenses d'emprunt et cohérence du solde couvertes ; autres badges et bornes calendaires à vérifier séparément. La priorité événement est codée ; la file F5 reste chronologique : portée exacte de l'avantage « priorité sur les listes d'attente » à confronter au besoin original avant toute modification métier. |
| Messagerie | Conversations liées aux transactions, participants, droits, limitation et clôture dans `messagerie_lib.php` | Intégration don/vente/emprunt observée et partiellement testée ; anti-spam, signalements et modération à recetter de façon ciblée. |
| Compte enrichi et notifications | Favoris, alertes de stock, adresses, photo, mails et newsletter présents dans les bibliothèques/pages | Existence confirmée, mais aucune nouvelle campagne exhaustive exécutée pendant cet audit. Ne pas déclarer ces parcours entièrement vérifiés. |
| Back-office | Sessions séparées, matrice `DROITS`, droits `catalogue`, `moderation`, `evenements`, `admin` et écrans dédiés | Droits F5 attestés ; matrice complète de l'ensemble des écrans à conserver comme objectif de recette, pas comme résultat acquis. |

## Priorité recommandée

Le lot de sécurisation de la récupération de mot de passe est désormais implémenté et testé en environnement isolé, en attente de revue et d'autorisation de commit. Ensuite, le parcours scolaire constitue un candidat cohérent car explicitement prévu dans la feuille de route et déjà préparé côté schéma.

Le développement métier suivant n'est pas sélectionné définitivement sans le cahier des charges original. Le format des niveaux/matières et la portée des avantages de fidélité nécessitent une vérification du besoin avant d'imposer des choix produit. Aucune modification des règles F1 à F5 n'est engagée sur la seule base de cet audit.

## Préparation du lot recommandé : récupération du mot de passe

- Objectif : garantir qu'une demande de réinitialisation provient du formulaire de la session, sans changer la charte graphique, le message générique ou la durée d'une heure.
- Parcours : visiteur → demande de lien → réception Mailpit → nouveau mot de passe → connexion ; demande invalide, lien expiré et réutilisation refusée.
- Fichiers pressentis : `recuparation_mdp.php` ; revue de `modifier_mdp.php` et des helpers `avis_lib.php`, de la configuration et du mailer. Un script de recette privé peut être créé dans l'environnement isolé.
- SQL : aucune nouvelle colonne nécessaire pour la protection CSRF ; jeton haché et expiration existent déjà. Aucune migration ni accès à la base principale prévu.
- Sécurité : réutiliser `jetonCsrf()` et `verifierCsrf()`, contrôler le POST avant écriture/envoi, conserver PDO, échappement et réponse ne révélant pas l'existence d'un compte. Examiner séparément l'usage unique concurrent du jeton, sans supposer que la lecture puis l'UPDATE actuels sont atomiques. La politique anti-abus supplémentaire doit être cadrée plutôt qu'inventée.
- Acceptation : jeton CSRF absent/incorrect refusé sans modification du jeton de récupération ni email ; jeton valide accepté ; formulaire présent et lisible ; message générique maintenu ; lien valide utilisable, expiré ou consommé refusé ; mot de passe connecté toujours protégé.
- Tests : preuve préalable d'isolation CLI/HTTP et absence d'accès à `biblioccaz`, fixtures fictives, mails capturés dans Mailpit, comparaison avant/après des champs de récupération ; syntaxe PHP, diff et revue de sécurité. Tester ensuite connexion, changement du mot de passe connecté et sessions séparées. Ne pas répéter les 141 assertions F5 pour cette correction locale.
- UX : conserver page, libellés, feedback et focus existants ; tester état d'erreur CSRF et saisie clavier à 375/1440 px lorsqu'un navigateur est disponible. Aucune refonte.

## Préparation du candidat métier suivant : scolaire

- Objectif et parcours : retrouver un manuel par niveau, matière ou ISBN, consulter ses offres vente/don/emprunt et gérer les métadonnées depuis le catalogue d'équipe.
- Fichiers possibles : `catalogue.php`, champs du catalogue et `admin/livre.php`, `partie_champs_categorie.php`, fiche livre, navigation existante et bibliothèques de catégories. Déterminer les points de saisie auteur/annonce concernés avant de les élargir.
- SQL : les deux colonnes sont déjà déclarées ; vérifier leur présence sur la base isolée avant de proposer une migration. Ne pas ajouter une table ou changer le modèle sans besoin confirmé.
- Décisions à confirmer : vocabulaire et format niveau/matière, traitement des livres hors scolaire et place dans la navigation existante.
- Acceptation : filtres combinables, ISBN existant conservé, pagination et liens gardant les filtres, états vides utiles, droits d'édition catalogue, champs validés/échappés et requêtes préparées ; disponibilité et séparation des offres F5 préservées.
- Recette isolée : jeux avec plusieurs niveaux/matières, valeurs absentes, ISBN et accents ; paramètres invalides ; contrôle des quatre types d'offres ; non-régression catalogue général et fiche livre. Réutiliser les composants responsive actuels et vérifier le volet de filtres mobile.

## État de cette session

La reprise initiale ci-dessus était un audit sans modification métier. La session suivante a reçu l'autorisation de sécuriser la récupération : modifications ciblées de `recuparation_mdp.php` et `modifier_mdp.php`, ajout de `recuperation_mdp_lib.php`, sans migration, changement graphique ou modification du README. Le délai entre deux émissions d'un compte est de 60 secondes, appliqué atomiquement en base avec les colonnes existantes ; la session autorise cinq demandes en quinze minutes, que l'email soit connu ou non. La réponse reste générique, y compris en cas de limitation ou d'erreur d'envoi. Une durée minimale de réponse de 400 ms réduit les écarts courants ; l'envoi SMTP synchrone peut encore créer des écarts temporels, donc aucune garantie de temps constant n'est revendiquée.

Le jeton est revérifié dans l'UPDATE qui change le mot de passe et le consomme. La session du navigateur est renouvelée après succès et, en mode récupération, vidée avant retour à la connexion. Cela ne révoque pas automatiquement les autres sessions déjà ouvertes sur d'autres appareils : aucun mécanisme global de révocation n'a été ajouté dans cette correction ciblée.

53 assertions fonctionnelles/de non-régression réussies avec le compte SQL restreint de l'environnement de test existant : accès à `biblioccaz` refusé avant le serveur, routage test et SMTP local vérifiés ; emails fictifs capturés par Mailpit. La connexion réelle a été testée avec un état CAPTCHA prévalidé en fixture ; l'interaction visuelle du puzzle n'a pas été exécutée. Syntaxe PHP et `git diff --check` vérifiés. Pas de nouvelle campagne F5 ni de validation visuelle desktop/mobile : structure et styles conservés, contrôles HTTP sans erreur PHP. Aucun accès applicatif à la base principale, aucun commit ni push.

## Propositions pour Google Docs, à rapprocher des libellés réels

Les couleurs de référence sont : vert « Opérationnel », jaune « À optimiser », orange « En cours », rouge « Non commencé ». Les lignes exactes et leur couleur actuelle n'étant pas accessibles, les propositions suivantes ne sont pas des mises à jour effectuées :

| Ligne fonctionnelle à rechercher | Statut proposé | Justification et réserve |
|---|---|---|
| Demande de récupération du mot de passe / envoi du lien | Vert — Opérationnel, sur le périmètre local testé | CSRF absent/invalide refusé, email inconnu non révélé par le message, jeton haché, expiration, anti-répétition et capture Mailpit vérifiés. À appliquer seulement si le libellé ne promet pas un envoi en production ou une protection anti-abus distribuée. |
| Réinitialisation par lien à usage unique | Vert — Opérationnel, sur le périmètre local testé | Succès, expiration, rejeu et concurrence vérifiés ; ancien mot de passe refusé et nouveau accepté. |
| Sécurité globale de l'authentification / gestion complète des sessions, si une telle ligne existe | Jaune — À optimiser | Révocation globale des sessions non réalisée, anti-abus par IP/service non ajouté et absence de garantie de temps constant ; aucune recette exhaustive inscription/captcha/rôles revendiquée. |

Le propriétaire doit valider ces correspondances avant toute modification du document partagé. La couverture contractuelle globale reste à rapprocher du contenu Google Docs réel.
