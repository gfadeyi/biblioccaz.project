# État fonctionnel et prochaine priorité

Audit de reprise du 28 octobre 2026, sur `refonte`, à partir du commit `5c8bd1a`.

## Sources et portée

Sources lues : README, feuille de route, plan F5, workflow commun, instructions locales, rapport de relais et rapports de livraison/migration F5. La revue du code porte sur les points d'entrée et bibliothèques cités ci-dessous ; ce n'est pas un audit exhaustif de chaque ligne.

Le document Google Docs [« Fonctionnalités du site »](https://docs.google.com/document/d/12eXyG-hgVNoNivrep4W7dBiC9N9wzH8VLaj-uGHEDsA/edit) est la référence fonctionnelle principale désignée par le propriétaire. Initialement inaccessible, son texte a été consulté en lecture seule pendant la session nocturne du 28 octobre 2026 via le connecteur disponible. Les propositions ci-dessous sont désormais rapprochées des libellés réels ; les couleurs actuelles n'ont pas été auditées par cette lecture textuelle. Aucun statut Google Docs n'a été modifié.

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
| Parcours « Rentrée & études » de la feuille de route | Raccordé depuis `2999729` : saisie d'équipe, fiche et filtres combinés niveau/matière. 54 assertions isolées confirmées à la reprise ; recette visuelle et points de saisie auteur/annonce à compléter selon le périmètre souhaité. |

## 3. Non commencées ou explicitement différées

| Périmètre | État |
|---|---|
| Assistant d'installation initiale de l'administrateur | Réalisé depuis : `admin/installation.php`, protégé par `JETON_INSTALLATION` dans `secrets.php` (32 caractères au moins), mot de passe de 12 caractères au moins. |
| BibliOccaz Demo | Phase future explicitement exclue de la session. Aucun hébergement, compte, tunnel, adaptation de code ou déploiement à lancer. |
| Autres exigences absentes des documents disponibles | Non classables sans le cahier des charges original. Ne pas conclure qu'elles sont réalisées ou inexistantes. |

## 4. Présentes, nécessitant corrections ou tests supplémentaires

| Périmètre | Code observé | Contrôles restant à couvrir |
|---|---|---|
| Comptes, connexion, récupération du mot de passe | Pages d'inscription/connexion, captcha, récupération et changement de mot de passe | Récupération sécurisée et vérifiée par 53 assertions isolées le 28 octobre 2026 : CSRF aux deux étapes, anti-répétition, lien expiré/consommé, usage unique concurrent, connexion avec nouveau mot de passe et changement connecté. Les autres parcours du module Comptes ne sont pas déclarés entièrement recettés. Aucune écriture sur la base principale. Limites et propositions de statuts ci-dessous. |
| Catalogue et découverte | `catalogue.php`, `livres_lib.php`, catégories, cartes, recherche titre/auteur/ISBN/éditeur et offres multiples | Matrice complète des filtres, pagination, entrées malformées et accessibilité à compléter ; le défaut de parcours scolaire est décrit séparément. |
| Commandes et fidélité au paiement | `panier_calcul.php`, `confirmation_achat.php`, `commandes_lib.php` ; verrous, bons et bonus de niveau présents | Recette du 1er novembre 2026 : seuils de remise et de livraison aux bornes, bons (minimum de 15 €, plafond de 50 %, solde insuffisant), double envoi simultané du paiement, dernier exemplaire disputé, prix relus en base, annulation avec stock et points rendus, mails. Le paiement reste simulé. |
| Annonces et ventes F3 | `annonces_lib.php`, `ventes_lib.php`, reçus et écrans de modération | Recette du 1er novembre 2026 : plafonds (5 demandes, 20 annonces, prix 0,50-150 €), expiration à 7 jours, file d'attente, annulation, vente conclue et reçu, litige, confirmation automatique, annulation par l'équipe avec points annulés. |
| Dons | Pages de création/réservation et code de retrait, bibliothèques et back-office | Recette du 1er novembre 2026 : création avec photo, modération, réservation et conversation, refus du donneur et d'un tiers, retrait par code court et lien du QR, 20 points. Le scan physique d'un QR code avec un téléphone reste à faire par le propriétaire. |
| Événements et propositions auteur | `evenements_lib.php` : inscriptions, attente, promotion, propositions, validation/refus | Recette du 1er novembre 2026 : jauge et attente, promotion, billet avec QR et code court, présence par code et par lien du QR (points une seule fois), export PDF, proposition d'auteur validée. Scan physique à faire par le propriétaire. |
| Points, niveaux, badges F7 | `points_lib.php`, page dédiée et intégration au paiement ; bonus, livraison par niveau et bon mensuel d'anniversaire d'inscription présents | Ne pas redévelopper F7. Récompenses d'emprunt et cohérence du solde couvertes ; quota mensuel des avis vérifié à la bascule de mois, fuseaux PHP et base alignés en production. La priorité événement suit le niveau ; la file des emprunts donne la priorité au profil actif depuis le 1er novembre 2026. |
| Messagerie | Conversations liées aux transactions, participants, droits, limitation et clôture dans `messagerie_lib.php` | Intégration don/vente/emprunt observée et partiellement testée ; anti-spam, signalements et modération à recetter de façon ciblée. |
| Compte enrichi et notifications | Favoris, alertes de stock, adresses, photo, mails et newsletter présents dans les bibliothèques/pages | Recette du 1er novembre 2026 : favoris (AJAX, refus en GET), alerte de retour en stock et mail, adresses (validation, propriété), abonnement et désabonnement newsletter. Photo de profil non recettée dans ce lot. |
| Back-office | Sessions séparées, matrice `DROITS`, droits `catalogue`, `moderation`, `evenements`, `admin` et écrans dédiés | Matrice complète du 1er novembre 2026 : 41 écrans × 6 profils (visiteur, client, auteur, modérateur, gestionnaire, administrateur) avec les droits par défaut ; les droits personnalisés par membre ne sont pas couverts. |

## Priorité recommandée

La récupération du mot de passe est publiée dans `5fc98c2`. Le lot sessions est publié dans `dfe605f` et le paramétrage des récompenses dans `5053cf8` ; leurs migrations principales restent à autoriser. Le parcours scolaire préparé côté schéma est raccordé pendant cette session. La définition du profil actif reste la prochaine décision métier nécessaire.

La référence est maintenant disponible : elle confirme les champs scolaires de F2 et les filtres de F9, ainsi que le paramétrage des récompenses de F7/F10. Les critères précis du profil actif ne sont toutefois pas fixés : ce point requiert encore une décision métier. Les données de niveau/matière peuvent rester des libellés libres existants sans inventer de nomenclature scolaire.

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

Le jeton est revérifié dans l'UPDATE qui change le mot de passe et le consomme. La session du navigateur est renouvelée après succès et, en mode récupération, vidée avant retour à la connexion. La correction initiale de récupération ne révoquait pas les autres appareils ; l'évolution F6 décrite ci-dessous invalide désormais les anciennes connexions à leur prochaine requête.

53 assertions fonctionnelles/de non-régression réussies avec le compte SQL restreint de l'environnement de test existant : accès à `biblioccaz` refusé avant le serveur, routage test et SMTP local vérifiés ; emails fictifs capturés par Mailpit. La connexion réelle a été testée avec un état CAPTCHA prévalidé en fixture ; l'interaction visuelle du puzzle n'a pas été exécutée. Syntaxe PHP et `git diff --check` vérifiés. Pas de nouvelle campagne F5 ni de validation visuelle desktop/mobile : structure et styles conservés, contrôles HTTP sans erreur PHP. Aucun accès applicatif à la base principale, aucun commit ni push.

## Propositions pour Google Docs, à rapprocher des libellés réels

Les couleurs de référence sont : vert « Opérationnel », jaune « À optimiser », orange « En cours », rouge « Non commencé ». Les lignes exactes et leur couleur actuelle n'étant pas accessibles, les propositions suivantes ne sont pas des mises à jour effectuées :

| Ligne fonctionnelle à rechercher | Statut proposé | Justification et réserve |
|---|---|---|
| Demande de récupération du mot de passe / envoi du lien | Vert — Opérationnel, sur le périmètre local testé | CSRF absent/invalide refusé, email inconnu non révélé par le message, jeton haché, expiration, anti-répétition et capture Mailpit vérifiés. À appliquer seulement si le libellé ne promet pas un envoi en production ou une protection anti-abus distribuée. |
| Réinitialisation par lien à usage unique | Vert — Opérationnel, sur le périmètre local testé | Succès, expiration, rejeu et concurrence vérifiés ; ancien mot de passe refusé et nouveau accepté. |
| Sécurité globale de l'authentification / gestion complète des sessions, si une telle ligne existe | Jaune — À optimiser tant que la livraison complète n'est pas activée | Révocation et invalidation des anciennes sessions ajoutées/testées par F6, mais migration principale et rendu visuel restent à valider. Anti-abus par IP/service non ajouté et absence de garantie de temps constant ; aucune recette exhaustive inscription/captcha/rôles revendiquée. |

Le propriétaire doit valider ces correspondances avant toute modification du document partagé. La couverture contractuelle globale reste à rapprocher du contenu Google Docs réel.

## Audit F6 — contrôle des sessions

L'audit initial a confirmé que la page En ligne ne supervisait que des membres récemment actifs, sans distinguer les connexions ni proposer de révocation. L'évolution du [plan F6](plan-f6.md) est maintenant implémentée et vérifiée en base isolée : sessions PHP conservées, registre d'empreintes, contrôle central des droits/suspensions et du mot de passe, fermeture individuelle ou globale avec POST/CSRF/confirmation. Les connexions d'un autre administrateur sont protégées côté serveur, conformément à l'option validée.

80 assertions F6/non-régression ciblée et 53 assertions de récupération réussies sur le code intégré. Migration du registre créée et rejouée uniquement dans la base isolée ; syntaxe et diff vérifiés. Les anciennes sessions sont refusées après révocation, modification de rôle, suspension ou changement du mot de passe. Un changement de rôle impose une reconnexion ; l'inactivité est de dix minutes et le rafraîchissement automatique ne la prolonge pas.

Statut proposé F6 local : orange « En cours » pour la livraison globale tant que migration principale et recette visuelle ne sont pas achevées. Le commit est désormais publié. Le parcours technique isolé est vérifié, sans revendication de rendu observé ni de migration réelle. Aucun statut Google Docs modifié.

Précision de numérotation issue du document désormais lu : « F6 » est le nom local du lot sessions livré par le commit dfe605f. Dans la référence officielle, la Fonctionnalité 6 correspond aux avis ; « Supervision des sessions » et « Contrôle des sessions » appartiennent à la Fonctionnalité 10. Les fichiers et preuves existants conservent leur nom pour assurer la continuité du suivi.

## Propositions F5 pour les cinq lignes signalées rouges

Lors de l'audit initial, l'accès Google Docs était indisponible. Le tableau ci-dessous conserve la correspondance alors proposée avec les axes du plan local. Le rapprochement précis figurant ensuite le remplace depuis la lecture effective de la référence ; les couleurs actuelles ne sont toujours pas attestées.

| Axe à rapprocher de la ligne réelle | Proposition | Preuves conservées et limites |
|---|---|---|
| Mise à disposition d'un livre en emprunt et réglages | Vert — Opérationnel sur la recette locale | Journal parcours : huit mises à disposition ; non-régression stock vente/emprunt et tests des réglages de pénalité. La migration principale est attestée séparément par son rapport. |
| Réservation, limite et attente | Vert — Opérationnel sur la recette locale | Journaux parcours, attente, HTTP et concurrence : limite de trois, doublons, éligibilité, attribution, CSRF, deux lecteurs pour un exemplaire et quatre réservations simultanées. Les positions à date identique ont aussi un test ciblé conservé. |
| Retrait par code/QR et tickets | Jaune — À optimiser si la ligne englobe la validation physique/visuelle | Retrait POST, double retrait, normalisation du contenu QR, droits de téléchargement et tickets PDF vérifiés techniquement. Scan physique sur smartphone et mise en page PDF observée non attestés. Si la ligne se limite au retrait par code, le vert peut être justifié par les tests disponibles. |
| Retours, renouvellements et blocages | Vert — Opérationnel sur la recette locale | Journaux parcours et compléments : renouvellement et refus, retour à l'heure/en retard, blocage plafonné, levée motivée, limite mensuelle de points et instantané de pénalité. Cela n'atteste pas une nouvelle recette sur la vraie base. |
| Mails, rappels et relances | Vert pour le déclenchement à l'ouverture des pages ; jaune si l'exigence impose un envoi sans visite | Captures Mailpit : réservation, renouvellement, retour, attribution, expiration, J-3, jour J et retard ; test anti-doublon des relances. Pas de tâche planifiée : un rappel sans accès aux pages n'est pas démontré ni implémenté. Aucun envoi réel de production testé. |

Les preuves permettent de remettre en question les cinq rouges, pas de les remplacer indistinctement par cinq verts. Aucun statut Google Docs changé.

## Rapprochement précis après lecture de la référence

Les propositions suivantes remplacent les correspondances hypothétiques précédentes, sans modification du document partagé :

| Libellé exact | Proposition | Preuves et réserve |
|---|---|---|
| F5 — Mise en emprunt d’un exemplaire | Vert — Opérationnel sur le périmètre local | Mise à disposition et réglages testés ; schéma principal F5 déjà installé et contrôlé par la livraison précédente. |
| F5 — Réservation en ligne | Vert — Opérationnel sur le périmètre local | Réservations, maximum, retards, droits, CSRF, attente et concurrence testés. |
| F5 — Validation de l’emprunt Lors du retrait en magasin | Vert — Opérationnel sur le périmètre local | Début/retour prévu/statut et génération PDF vérifiés. Le libellé ne requiert pas de scan physique QR ; la recette visuelle PDF reste une réserve distincte. |
| F5 — Gestion des retours | Vert — Opérationnel sur le périmètre local | Retour, historique, retard et blocage testés, avec non-régression durant F6 et récompenses. |
| F5 — Relances et gestion des retards | Jaune — À optimiser | Emails J-3/J0/retard capturés et signalement back-office testé. Les rappels dépendent encore d'une ouverture de page ; aucun envoi autonome sans visite ni tâche planifiée n'est attesté. |
| F10 — Supervision des sessions / Contrôle des sessions | Orange — En cours de livraison | 80 assertions et publication dfe605f, mais migration sessions principale et recette visuelle encore en attente. |
| F7 — Administration des règles de récompenses / F10 — Paramétrage des règles de récompenses | Jaune — À optimiser après activation du lot | Points, quotas, niveaux et paliers paramétrables avec versions/historique, droits et conservation des montants anciens testés. Conditions du profil actif et paramètres métier d'emprunt supplémentaires non couverts par cet écran. Migration principale en attente. |
| F7 — Statut de profil actif | Orange — En cours, décision nécessaire | Le texte donne des exemples mais ni délai de connexion, ni nombre/type d'actions sur une période, ni combinaison, ni avantages précis. Aucun critère arbitraire ni privilège automatique ajouté. |

## Administration des récompenses livrée

Voir [le guide des règles](regles-recompenses.md). Barème et plafonds mensuels sont pris en compte dans achats, dons, ventes, emprunts et avis ; présences raccordées au même barème. Les seuils des six niveaux et paliers des badges existants sont modifiables. Les montants en attente sont des instantanés ; annulations et historiques utilisent les valeurs enregistrées, jamais le nouveau barème. Les badges obtenus sont conservés et les effets d'une baisse de seuil sont annoncés avant confirmation.

Recette isolée : 69 assertions dédiées récompenses, puis 80 assertions F6/non-régression ciblée et 53 assertions récupération sur le code intégré. La migration `sql/maj_regles_recompenses.sql` est rejouable et incluse dans l'installation complète ; aucune migration principale de ce lot n'a été importée. Ni rendu visuel réel ni conformité WCAG exhaustive ne sont revendiqués.

## Recherche et parcours scolaire — lot nocturne

Le catalogue conserve ses offres, tris, tags, pagination et compteur Fetch. Les entrées HTTP non scalaires sont normalisées ; les caractères `%`, `_` et `!` sont recherchés littéralement. Les ISBN complets peuvent être saisis avec ou sans espaces/tirets, y compris dans les suggestions. Ces dernières sont temporisées et ignorent les réponses anciennes ; Échap et le clic extérieur les ferment sans réouverture tardive.

Le back-office permet de renseigner niveau scolaire et matière, facultatifs et bornés à 50/80 caractères. La fiche les affiche et le catalogue les combine aux autres filtres à partir des libellés existants. Les colonnes proviennent de `sql/maj_livres_details.sql`, déjà présent et repris dans l'installation complète : aucune nouvelle migration scolaire créée ou appliquée. Une installation ancienne doit vérifier ce prérequis avant activation.

Preuves : 54 assertions HTTP/normalisation sur la base isolée (filtres, ISBN, pagination, entrées invalides, droits, CSRF, création/modification scolaire), et 8 assertions JavaScript en simulation contrôlée (temporisation, annulation, réponses dans le désordre, erreurs, fermeture). Cela ne constitue pas une recette visuelle réelle : l'accès navigateur signale un hôte indisponible. Les recommandations personnalisées, filtres de localisation, mesure de performance sur grand volume et audit complet de tous les tris restent distincts.

Propositions Google Docs : F2 « Champs spécifiques pour les livres scolaires/universitaires » et F9 filtres niveau/matière — jaune, à optimiser/recetter visuellement après activation ; F9 recherche simple et suggestions — jaune, périmètre testé précisé ci-dessus, sans prétendre que toute la recherche avancée est achevée. Ne modifier les couleurs qu'après rapprochement des libellés exacts dans le document partagé.

## Reprise d'intégration — 28 octobre 2026

Les trois commits nocturnes sont présents et publiés, branche `refonte` propre avant correction. Les tables sessions/règles sont absentes de la base principale. Sauvegarde complète restaurée sur compte isolé, 33 tables identiques ; deux migrations rejouées et contraintes contrôlées. Aucune migration principale effectuée. Voir [le plan d'intégration et la checklist visuelle](integration-sessions-recompenses.md).

La recherche numérique est désormais exclusivement un préfixe ISBN normalisé, dans catalogue/Fetch/suggestions/inventaire ; le texte garde sa recherche habituelle. 58 assertions ISBN/statuts, 54 recherche/scolaire, 80 sessions et 69 récompenses réussies, plus 9 JavaScript. Le navigateur reste inaccessible : aucune observation graphique nouvelle. La supervision exclut maintenant aussi les comptes en attente, conformément au contrôle d'accès.

Propositions de statuts inchangées dans leur prudence : F5 quatre verts sur le périmètre local attesté et relances jaune ; sessions (F10 officiel, F6 local) orange jusqu'à activation et recette visuelle ; récompenses et recherche/filtres jaune tant que leurs réserves subsistent. « Compte actif » est administratif ; « en ligne » repose sur une session valide ; « profil participant actif » reste orange, critères non décidés. Aucun nouveau seuil ni récompense créé ; trois options sont documentées sans mise en œuvre. Aucune couleur Google Docs modifiée.

### Activation SQL autorisée

Le 28 octobre 2026, `maj_sessions.sql` puis `maj_regles_recompenses.sql` ont été appliquées avec succès sur `biblioccaz`, chaque import suivi de son contrôle avant le suivant. Les trois tables nouvelles correspondent exactement à la copie testée ; barème initial en version 1, registre sessions et historique des règles vides. Les 33 tables existantes sont préservées en structure, lignes et checksums, encodage contrôlé par le script dédié. La sauvegarde et sa restauration isolée ont été revérifiées avant import. La réserve de migration est levée ; les réserves de recette visuelle et d'interactions réelles demeurent, sans changement automatique des couleurs Google Docs. Les sessions anciennes doivent se reconnecter.

### Tableau de bord des connexions

Synthèse globale, recherche pseudo/nom/email, rôles, multi-session, pagination par utilisateur et détails des sessions sont développés, avec rendu serveur sans dépendance JavaScript. La fermeture globale ne vise que les clients/auteurs valides et préserve les autres rôles, même avec concurrence ; les actions individuelles F6 sont conservées. Motifs de reconnexion différenciés si vérifiables, sinon message générique. Voir [la documentation de supervision](supervision-sessions.md).

Preuves isolées : 66 assertions dédiées, 80 F6 et 53 authentification/récupération réussies. Aucune écriture principale pour ce lot. Migration complémentaire `maj_sessions_supervision.sql` préparée/testée sur base fictive, afin que le rôle d'ouverture soit connu et les sessions aux rôles modifiés immédiatement exclues des indicateurs. Sans cette colonne, le tableau annonce le prérequis ; l'authentification existante reste compatible. Proposition F10 « Supervision des sessions » et « Contrôle des sessions » : orange pendant la livraison, avec migration complémentaire, validation visuelle et commit en attente. Les statuts de compte, présence et participation restent distincts ; aucun seuil de profil participant ajouté.

La migration complémentaire a ensuite été explicitement autorisée et appliquée le 28 octobre 2026 à 15 h 51, après sauvegarde fraîche et restauration vérifiée. Données, nombres et checksums des 36 tables préexistantes préservés, clés/index/contraintes inchangés et encodage contrôlé. Les trois anciennes sessions restent sans rôle historique reconstitué et doivent se reconnecter. Réserve de migration levée ; rendu visuel et livraison Git toujours en attente. Aucun statut Google Docs modifié.

### Validation finale des deux lots

Le propriétaire confirme manuellement le comportement ISBN par préfixe et l'ouverture de la fiche correspondante. Aucun nouveau développement ISBN : 58 assertions catalogue/suggestions/Fetch/inventaire et 9 JavaScript confirment sa conservation et les recherches textuelles.

Libellés de fin de session harmonisés et ancien déclenchement par `?session=expiree` supprimé. Cause issue uniquement du serveur, à usage unique ; 42 assertions HTTP de messages site/admin réussies, puis 66 supervision, 80 F6, 53 authentification et 69 récompenses. Total des bancs de cette reprise : 377 assertions, avec recouvrements fonctionnels possibles. Les nouvelles interfaces n'ont pas été observées en navigateur, toujours indisponible. Les réserves visuelles demeurent et aucune couleur Google Docs n'est modifiée.

Aucun fichier de messagerie parmi les modifications locales à cette reprise ; ce chantier n'a pas été repris. Deux lots Git distincts étaient alors préparés. Ils ont ensuite été publiés séparément : lot A `0aea7f9`, lot B `7c2e37e`. La recette visuelle des nouvelles interfaces reste distincte de cette livraison Git.

### Lot C — activité et polling de la messagerie

Le correctif de `config.php` exclut le polling automatique de l'actualisation de l'activité, tout en conservant les contrôles F6 à chaque requête. Les trois horloges restent stables pendant les polls ; navigation et envoi volontaire les actualisent. Session valide, activité d'usage et présence humaine instantanée restent des notions distinctes.

21 assertions ciblées réussies sur base fictive, ainsi que 80 F6, 42 messages de fin de session et 53 authentification/récupération : 196 assertions exécutées pour cette reprise. Aucun changement SQL ni écriture principale. Correctif préparé, sans commit ni publication C. La réception et les refus ont été vérifiés en HTTP ; le comportement des onglets et des redirections Fetch reste à observer en navigateur. Aucun nouveau statut Google Docs n'est proposé comme opérationnel sur la seule base de ce correctif ; les réserves de recette visuelle sont conservées.

### Recette après publication A, B et C

Le commit C `b8ad2f2` est désormais publié. La nouvelle recette confirme le dépôt synchronisé avant intervention et les migrations déjà installées, sans les réexécuter. Un défaut HTTP a été reproduit : la redirection du polling consommait le message de fin de session en arrière-plan. Correction ciblée préparée : refus JSON, puis navigation explicite vers la connexion, motif conservé côté serveur et affiché une seule fois. Aucun nouveau commit de cette correction ni migration.

322 assertions réussies sur environnement fictif : 51 messagerie/réponses HTTP, 21 polling, 66 supervision, 80 F6, 42 messages, 53 authentification et 9 JavaScript simulé. La messagerie est vérifiée pour les envois, les non-lus, les limites séquentielles de messages et mails, les accès tiers, CSRF, l'échappement, les signalements, le masquage, la clôture, la suspension et les réponses de l'équipe aux emprunts. L'ouverture des trois types de conversation est testée par la bibliothèque ; cela n'atteste pas à nouveau tous les cycles de dons/ventes/emprunts. Le navigateur est indisponible : aucune nouvelle validation graphique ou WCAG exhaustive, et aucune validation physique des QR.

Propositions de suivi, à rapprocher des lignes actuelles du Google Docs sans changement direct :

| Périmètre | Proposition et preuve |
|---|---|
| Supervision et contrôle des sessions | Jaune « À optimiser » pour la ligne globale : migration et livraison acquises, 66 assertions de supervision réussies ; recette visuelle encore ouverte. Les actions serveur précisément testées sont fonctionnelles sur copie isolée. |
| Messagerie et interaction avec les sessions | Jaune « À optimiser » : recette HTTP et JavaScript ciblée réussie, correction des redirections à livrer et navigation réelle à confirmer. Ne pas assimiler cette preuve à une couverture complète des courses anti-spam. |
| Cinq lignes Emprunts F5 | Conserver les propositions détaillées existantes fondées sur les 141 assertions historiques et la validation graphique manuelle ; pas de nouvelle validation globale lors de cette recette, scan physique QR toujours non attesté. |
| Administration des récompenses, recherche et profils | Aucun nouveau changement de statut justifié par cette recette ; règles métier inchangées et profil participant toujours à définir. |

### Recette navigateur du 28 octobre 2026

Recette réalisée dans Chrome headless (Playwright, profil temporaire) sur copie isolée et base fictive, à 1440, 768 et 375 px. Aucune session réelle fermée, aucune règle de récompense modifiée et aucune écriture sur la base principale.

Validé en navigateur : tableau « En ligne » (indicateurs, recherches pseudo/nom/e-mail, filtres de rôle et multi-session, pagination, fermeture d'une session ou de toutes, protection des autres administrateurs, confirmation chiffrée de la fermeture globale) ; messages de fin de session (fermée, expirée, mot de passe, suspension, cause inconnue, back-office), affichés une seule fois et sans boucle ; messagerie (réservation d'un don, envoi, réception par polling en 30 secondes, bandeau, non-lus, accès tiers refusé, signalement, masquage, réponse de l'équipe sur un emprunt, quota horaire, limite de mails) ; navigation visible vers la connexion après expiration, fermeture à distance et dix minutes réelles d'inactivité pendant le polling ; recherche ISBN par préfixe avec et sans tirets, suggestions, résultats vides, filtres niveau et matière ; pages de règles et de niveaux des récompenses, validations et droits.

Corrections d'interface issues de la recette : un seul repère `main` par page, nom accessible du bouton des catégories sur mobile, rôles ARIA des alertes de connexion du back-office, bandeau de nouveau message annoncé aux technologies d'assistance, brouillon conservé après un envoi refusé, focus clavier renforcé dans le back-office, libellé de la messagerie actualisé, panneau vide masqué sur mobile, croix de recherche aux couleurs du site.

Limites : onglet masqué simulé par l'API de visibilité, reconnexion par session de test plutôt que par le puzzle de connexion, aucun lecteur d'écran réel, aucun audit WCAG exhaustif ni mesure de contraste automatisée.

| Périmètre | Proposition |
|---|---|
| Supervision et contrôle des sessions | Vert « Opérationnel » sur le périmètre local testé, sous réserve de la publication des corrections d'interface. |
| Messages de fin de session | Vert « Opérationnel ». |
| Messagerie liée aux transactions | Vert « Opérationnel » après publication des corrections ; jaune si la ligne exige un signalement visible des erreurs réseau du polling. |
| Recherche ISBN et suggestions | Vert « Opérationnel » sur le périmètre recherche simple. |
| Champs et filtres scolaires | Vert « Opérationnel » pour niveau et matière. |
| Administration des règles de récompenses | Vert « Opérationnel » pour l'affichage, les contrôles et les droits ; aucune nouvelle règle métier. |
| Profil participant actif | Inchangé : orange, critères non décidés. |

### Recommandations (F1 profil, F9 suggestions)

Livré sur la branche `refonte`. Migration `sql/maj_recommandations.sql` testée sur base isolée (rejouée deux fois), puis importée dans `biblioccaz` le 29 octobre 2026 après sauvegarde complète : les 36 tables existantes sont identiques avant et après, encodage vérifié.

- Profil : téléphone facultatif (contrôle de format, visible du titulaire et de l'administrateur seulement) et préférences de lecture facultatives par univers, catégories et thèmes, modifiables à tout moment.
- Algorithme dans `recommandations_lib.php`, poids centralisés dans `POIDS_RECOMMANDATION` : préférence de catégorie +5, de thème +3, achat +4, emprunt +3, note de 4 ou 5 +3, note de 1 ou 2 −2, favori +2, fiche consultée +1 ; univers parent et tags comptés à moitié. Score sur 100 : 60 affinité, 25 popularité (ventes et favoris, échelle logarithmique), 15 note moyenne pondérée par le nombre d'avis ; repli 60 popularité et 40 note sans aucune donnée. Trois livres au plus par catégorie. Exclusions : déjà achetés ou empruntés, indisponibles, écrits ou proposés par le lecteur.
- Affichage : page « Suggestions pour vous », carte du profil, carrousel « Pour vous » de l'accueil (lecteurs connectés ayant au moins un signal), bloc « Vous aimerez aussi » sur la fiche livre ; chaque livre porte sa raison. Tri « Pertinence » dans le catalogue, par défaut seulement lors d'une recherche.
- Vie privée : historique réservé aux lecteurs et auteurs connectés, 20 fiches au plus, 90 jours au plus, actif par défaut avec explication, suspendable et effaçable ; aucune donnée pour les visiteurs ni pour l'équipe.
- Fiche livre : l'influence du profil du lecteur est plafonnée à la moitié du poids du livre consulté, pour que la proximité reste la raison affichée même pour un gros acheteur.
- Performance : liste calculée une fois puis gardée 10 minutes en session ; disponibilité et exclusions relues à chaque affichage.

Preuves : 94 assertions sur base isolée (score, poids, repli, exclusions, cache, historique, CSRF, droits, téléphone, tri, fonctionnement sans migration) et bancs de non-régression ; recette navigateur à 1440, 768 et 375 px sans débordement ni erreur JavaScript. Recette en lecture sur le vrai catalogue avec un compte de démonstration à 1440 et 375 px : 80 cartes vérifiées une à une (explications justes, aucun doublon, aucun livre indisponible, déjà acheté ou emprunté), historique du compte effacé ensuite.

| Périmètre | Proposition |
|---|---|
| F1 Gestion du profil | Vert « Opérationnel ». |
| F9 Suggestions personnalisées | Vert « Opérationnel ». |
| F9 Algorithme simple de recommandation | Vert « Opérationnel ». |
| F9 Affichage des résultats (tri par pertinence) | Vert « Opérationnel ». |

### Candidatures et pages auteur (F1 création de compte, F10 gestion des utilisateurs)

Implémenté sur la branche `refonte`, migration `sql/maj_candidatures_auteur.sql` testée sur base isolée (rejouée deux fois, données existantes inchangées), puis importée dans `biblioccaz` le 29 octobre 2026 après sauvegarde complète : les 39 tables existantes sont identiques avant et après, encodage vérifié.

- Inscription : le choix « Auteur » ne donne plus directement le rôle ; il mène à `candidature_auteur.php`, qui crée un compte lecteur et la candidature en une seule fois. Un lecteur connecté candidate depuis son profil.
- Fiche : nom de plume, biographie (50 à 3 000 caractères), motivation (réservée à l'équipe), 1 à 10 ouvrages (titre obligatoire, ISBN-10 ou 13 vérifié par sa clé), liens http ou https, photo facultative contrôlée comme les autres images.
- Une seule candidature en attente ; après un refus, nouvelle demande possible 7 jours plus tard, avec la date affichée.
- Back-office `admin/candidatures_auteur.php` (onglet à côté des candidatures modérateur) : accepter (email confirmé exigé), refuser avec message obligatoire, mail au candidat, historique, liste des pages publiées avec masquage. Droit unique `candidatures_auteur` dans `DROITS`, réservé à l'administrateur.
- Acceptation : le compte passe auteur, ses sessions se ferment, la page publique est créée depuis la candidature. Les auteurs déjà inscrits créent leur page sans nouvelle validation.
- Page publique `auteur.php?a=…` (biographie, ouvrages reliés au catalogue par l'ISBN, livres disponibles, rencontres à venir et passées) et page « Nos auteurs » avec recherche ; invisibles si la page est masquée, le compte suspendu ou plus auteur. Le nom de l'auteur sur la fiche livre mène à sa page.

Preuves : 121 assertions sur base isolée et bancs de non-régression verts ; recette navigateur à 1440, 768 et 375 px sans débordement ni erreur JavaScript.



### Règles des emprunts et profil actif — 30 octobre 2026

Les règles générales des emprunts sont administrables : limites par lecteur, délai de retrait, durée et renouvellements proposés pour les nouveaux exemplaires, durée de prolongation enregistrée à la réservation. Historique versionné et droits administrateur. Migration testée sur restauration privée et installation neuve, puis appliquée en production avec les anciennes valeurs conservées.

Le profil actif est un badge informatif pour les lecteurs et auteurs : une connexion et au moins une action validée sur les 30 derniers jours (achat non annulé, don récupéré, emprunt rendu, avis visible validé, présence confirmée). Période et minimum d'actions sont réglables dans les règles de récompenses. Aucun point ou avantage supplémentaire. Calcul à la consultation, sans statut administratif réécrit ni nouvelle table. L'ancienne configuration de récompenses reste lisible avec ces valeurs par défaut. Tests logiques isolés et recette navigateur à 375, 768 et 1440 px réussis ; la vérification de livraison est conservée dans le suivi privé.

### Fiches, aperçu et panier minute — 31 octobre 2026

Nouvelle présentation des fiches dans la charte existante : quatre états, épuisés désactivés, prix et économies uniquement avec référence connue, résumé dépliable, tags, onglets, auteur, éditions et série. Les offres entre lecteurs, dons, emprunts et avis restent accessibles. Les cartes proposent aperçu latéral, ajout rapide et moyenne des avis ; fermeture clavier, retour du focus et réduction des animations.

Le panier réserve les exemplaires du magasin pendant 15 minutes pour les visiteurs comme les clients. Un ajout relance le délai commun ; lecture et retrait ne le prolongent pas. Un identifiant aléatoire en cookie HttpOnly retrouve la réservation après une nouvelle session, sans reconnecter le membre. Les disponibilités déduisent les réservations actives des autres paniers. Le stock physique diminue seulement lors d’une commande validée. Expiration, changement d’état, quantités et concurrence sont vérifiés côté serveur sous transaction ; la validation contrôle à nouveau le délai avant commit. Les suggestions sous le panier sont supprimées ; la jauge vers 50 € reste affichée.

Connexion équipe sans captcha, avec CSRF et limitation persistante : cinq échecs bloquent l’adresse IP ou l’identifiant pendant 15 minutes. Le captcha client est conservé.

Migration rejouable `sql/maj_panier_minute_series.sql` : `panier_reservation`, `connexion_admin_limite`, `serie`, `livre_serie`, colonne nullable `livre.prix_neuf`. Installer la migration avant le code. L’installation complète inclut la même structure. Le remplissage des séries et les nouvelles références constituent un lot distinct ; aucun tome fictif n’est livré.

Recette sur PHP 8.4 / MariaDB 11.8 isolés : panier, expiration visible, reprise par cookie, concurrence sur le dernier exemplaire, changement d’état, refus CSRF, commande unique et expiration pendant la commande ; connexion équipe limitée ; interface visiteur/client aux largeurs 375, 768 et 1440 px. Les essais ne représentent ni un paiement bancaire réel ni un audit d’accessibilité exhaustif.


### Séries françaises, collections et éditions — 31 octobre 2026

Le lot de contenu préparé rassemble 2 258 références, 136 séries ou collections et leurs couvertures. Sur l’état de production sauvegardé, il ajoute 2 204 fiches, 8 742 offres et 17 558 exemplaires de présentation ; les fiches déjà présentes et leurs stocks sont conservés. Les prix d’occasion et quantités sont des données de démonstration. Un prix neuf inconnu reste absent et ne donne pas lieu à une offre neuve inventée.

Les volumes doubles affichent leur plage de tomes. Les éditions sont choisies par œuvre et disponibilité : une seule édition par tome ou œuvre dans la sélection groupée. Les anciennes éditions et les intégrales restent distinctes lorsque le découpage diffère. Les autres éditions d’un même livre sont retrouvées même avec des titres différents ; deux tomes partageant un titre ne sont pas confondus. Les collections policières n’ont pas de numéro de tome artificiel.

Migration rejouable `sql/maj_dates_volumes_series.sql`, également intégrée à l’installation complète : date de parution nullable, fin de plage, numéro de tome nullable, nature de collection et clé d’œuvre. Le contenu et ses images sont un import additif séparé, sans création de compte ni remplacement des données existantes.

Recette PHP 8.4 / MariaDB 11.8 isolés, compatibilité syntaxique PHP 8.2, restauration privée sans HTTP, installation neuve et double import ; anciennes lignes des 54 tables préservées. Contrôles des éditions, volumes doubles, disponibilité, non-réapprovisionnement et interface visiteur/client à 375, 768 et 1440 px. Les références papier françaises non identifiées et les sources détaillées sont consignées dans la livraison et le suivi privé.

### Démonstration, liste d'attente et connexion Google — 1er novembre 2026

Bandeau « Site de démonstration » discret et refermable (cookie de 30 jours, sans clignotement au chargement), rappel juste avant le paiement, dans le mail de confirmation de commande et en tête des CGU. Le bouton « Filtrer » du catalogue se trouve sous le bloc « Particularités ». Le badge « Coup de cœur » est retiré des cartes ; les badges restants sont placés en haut à gauche, loin des boutons aperçu, panier et favori.

Liste d'attente des emprunts : un profil actif au moment de l'inscription sur la liste passe devant, puis l'ordre d'arrivée s'applique ; positions affichées et attribution automatique suivent le même ordre. Migration `sql/maj_attente_prioritaire.sql` ; sans elle, l'ordre d'arrivée reste appliqué.

Connexion avec Google pour les lecteurs (OpenID Connect, state, nonce, PKCE S256, contrôle de l'émetteur, de l'audience, de l'expiration et de l'email vérifié). Première connexion : compte lecteur créé ; adresse déjà connue : liaison après confirmation du mot de passe (5 essais). Comptes de l'équipe refusés. Bouton masqué tant que les identifiants Google sont vides. Migration `sql/maj_connexion_google.sql`.

Recette sur PHP 8.4 / MariaDB 11.8 isolés et PHP 8.2 / MariaDB 10.4 (migrations rejouées deux fois) : 66 contrôles métier et 175 contrôles navigateur à 375, 768 et 1440 px, en visiteur et connecté, sans échec. Le parcours réel avec Google n'est testable qu'après création des identifiants.

### Données de démonstration de la communauté — 1er novembre 2026

`sql/donnees_demo_communaute.sql` : 60 lecteurs de démonstration non connectables, 254 avis rédigés pour chaque livre (répartition naturelle des notes, trois spoilers signalés), 38 dons répartis dans une quinzaine de villes (photos issues des couvertures des fiches), 24 événements à venir de novembre 2026 à mai 2027 et 306 inscriptions, dont des événements complets avec liste d'attente. Le prix éventuel d'un événement figure dans sa description : la table `evenement` n'a pas de colonne de prix. Import testé deux fois sur une copie de la production (aucun doublon) puis sur la copie isolée du site.

### Nouvelles séries et Bibles — 1er novembre 2026

Le lot de contenu ajoute 2 978 fiches : 2 970 tomes répartis dans 175 séries (nouvelles séries et tomes manquants des séries existantes) et 8 éditions françaises de la Bible dans la nouvelle catégorie « Spiritualité & religions » (sous Art & Culture, migration `sql/maj_categorie_spiritualite.sql`, reprise dans l'installation complète). Il crée 11 712 offres et 23 600 exemplaires de présentation ; les fiches, stocks et ISBN déjà présents sont conservés. Les références proviennent des notices de la BnF (ISBN, éditeur, date, pages, prix neuf lorsqu'il est connu) ; une édition principale par tome, les éditions perfect, deluxe ou originale formant des séries distinctes. Les titres sans édition papier française identifiable et les volumes sans couverture fiable sont écartés et consignés dans la livraison.

Recette : import double sans doublon sur une copie de la production (MariaDB 11.8) et sur MariaDB 10.4 local, couvertures contrôlées visuellement, 69 contrôles navigateur à 375, 768 et 1440 px en visiteur et connecté, sans échec.

### Accueil : bandeau aléatoire et univers illustrés — 1er novembre 2026

Le bandeau « Chaque livre mérite une seconde lecture » présente trois livres tirés parmi les 50 derniers titres ajoutés ayant une couverture et au moins un exemplaire disponible, un seul par série (`livresBandeauAccueil()`). Le tirage suit la graine de session du tri « Découverte » : il change à chaque nouvelle visite et reste stable pendant la visite. « Nouveau chez nous » est inchangé.

Les tuiles « Explorez nos univers » portent sept illustrations SVG originales au trait (`illustrations_univers_lib.php`). Au survol et au focus clavier, la carte prend la teinte de son univers, un détail du dessin s'anime et une flèche suit le nom ; rien ne bouge avec `prefers-reduced-motion`. Sur mobile, la rangée défile avec les flèches du carrousel. Recette PHP 8.4 / MariaDB 11.8 isolés : 45 contrôles à 375, 768 et 1440 px, visiteur et connecté, sans échec.

### Espaces de l'équipe et messagerie interne — 2 novembre 2026

Connexion commune par `admin/login.php`. `admin/index.php` sert un tableau de bord par rôle : l'administrateur garde la vue existante ; le gestionnaire (`admin/_tableau_gestionnaire.php`) et le modérateur (`admin/_tableau_moderateur.php`) ont la leur, inaccessible directement. Chaque fichier ne calcule que les données de son rôle : le modérateur ne reçoit ni chiffre de vente, ni commande, ni stock. Les blocs suivent la grille « Droits de l'équipe » : un droit retiré masque le bloc, un droit ajouté apparaît dans le menu.

Modérateur : fiches livres, annonces et dons à valider, avis à valider et signalés, messages signalés, membres signalés (auteurs d'avis ou de messages signalés non traités, faute de signalement de membre dédié), ventes à surveiller, propositions d'événements (liste en lecture seule sans le droit « Événements ») et ses dernières actions (avis, annonces, dons, signalements de messages, événements ; la validation des fiches livres n'enregistre pas son auteur). Gestionnaire : ventes du jour et du mois, commandes validées des dernières 48 heures (il n'existe pas d'étape d'expédition), stock bas et ruptures avec clients en attente, emprunts du jour (à préparer, retirés, retours attendus et rendus, retards, liste d'attente), suivi des relances, événements à venir avec inscrits et places, files de modération selon ses droits ; raccourcis vers l'ajout de livre, le comptoir et la création d'événement.

Messagerie de l'équipe (`admin/messagerie_equipe.php`, `messagerie_equipe_lib.php`, migration `sql/maj_messagerie_equipe.sql`) : fil « Équipe » commun, conversations privées et groupes de 2 à 12 membres, lus par leurs seuls participants. Non-lus dans le menu et sur les tableaux de bord, recherche, dates et auteurs, contenu échappé avec liens cliquables vers les pages du back-office, 2 000 caractères et 60 envois par heure au plus, POST avec jeton CSRF. Un compte suspendu ou retiré de l'équipe perd sa session ; une conversation privée avec lui passe en lecture seule. Bouton « Demander l'avis de l'équipe » sur les fiches livres, annonces, dons, avis signalés, conversations et propositions d'événements.

Recette sur PHP 8.4 / MariaDB 11.8 isolés avec deux modérateurs et deux gestionnaires de test : 96 contrôles (connexion et redirection, menus, tableaux de bord, 14 pages interdites, droit ajouté puis retiré, messagerie entre tous les couples de rôles, fil Équipe, groupe, non-lus, échappement, longueur, limitation, CSRF, recherche, compte suspendu ou retiré, client refusé, visiteur et client inchangés, affichage à 375, 768 et 1440 px). Bibliothèque et requêtes vérifiées sur PHP 8.2 / MariaDB 10.4 (16 contrôles, migration rejouée deux fois).

### Candidatures à l'équipe, bienvenue et messagerie en direct — 2 novembre 2026

- Formulaire public `rejoindre_equipe.php` (l'ancienne adresse `postuler_moderateur.php` y redirige) : rôle visé (modérateur ou gestionnaire), prénom, nom, pseudo souhaité, email (celui du compte pour un membre) et motivations de 30 à 5 000 caractères. Jeton CSRF, une candidature en cours par adresse ou par compte, 3 dépôts par jour et par connexion (empreinte de l'adresse IP), 30 jours d'attente après un refus. Aucun compte et aucun mail au dépôt. Le profil d'un lecteur ou d'un auteur affiche l'état de sa candidature.
- Back-office `admin/candidatures.php` (droit `admin`) : candidatures en attente, pseudo souhaité modifiable (signalé s'il est déjà pris), message facultatif, historique des décisions. Accepter un visiteur crée le compte (actif, adresse à confirmer) et envoie l'invitation à choisir le mot de passe, la même que la création manuelle (`envoyerInvitationCompte()` dans `recuperation_mdp_lib.php`). Accepter un membre donne le rôle à son compte (pseudo modifiable) et ferme sa session lecteur. Refuser ne crée ni ne modifie aucun compte.
- À chaque arrivée dans l'équipe (candidature acceptée ou compte créé par l'administrateur avec un rôle d'équipe), `accueillirNouveauMembreEquipe()` dépose un message privé de bienvenue de l'administrateur et une annonce dans le fil « Équipe ».
- Messagerie de l'équipe en direct : `javascript/messagerie_equipe.js` interroge `admin/messagerie_equipe_nouveaux.php` toutes les 5 secondes (30 secondes onglet masqué, ralentissement progressif en cas d'erreur). Les nouveaux messages s'ajoutent au fil (bouton « Nouveaux messages » si l'on a remonté la conversation), la liste des conversations, le badge du menu et le titre de l'onglet se mettent à jour. Ces appels ne prolongent pas la session (déconnexion après 15 minutes sans action, lot 12).
- Migration `sql/maj_candidatures_equipe.sql`, reportée dans `sql/biblioccaz.sql`. Recette : 67 contrôles sur la copie isolée PHP 8.4 / MariaDB 11.8, 96 contrôles de la recette de l'équipe rejoués, 14 contrôles sur MariaDB 10.4.

### Consolidation : accueil et séparation des romans Orange — 4 novembre 2026

Le bandeau élargit sa sélection aux 200 derniers titres éligibles, toujours trois livres avec couverture et stock disponible, un par série et un tirage stable pendant la visite. « Nouveau chez nous » est inchangé. Une icône SVG d'onglet est déclarée dans les en-têtes public et du back-office.

La série Orange mélangeait trois adaptations en roman avec les mangas. `sql/maj_orange_editions.sql`, également reporté dans l'installation complète, sépare « Orange — Romans » de Yui Tokiumi, corrige l'auteur de la série manga en Ichigo Takano et explicite les titres des romans. Les fiches conservent leur identifiant, ISBN et couverture ; offres, stocks et commandes ne sont pas modifiés. Aucun tome manquant n'est inventé ou réimporté.

Recette fictive à accès restreint : 405 contrôles sur PHP 8.4.27 / MariaDB 11.8.9 et 405 sur PHP 8.2 / MariaDB 10.4.32, migration rejouée deux fois. Navigateur : 59 contrôles en visiteur et client à 375, 768 et 1440 px ; captures pleine page avec réduction des mouvements pour figer les apparitions liées au défilement. Les comptes applicatifs de test ne peuvent pas accéder à la base principale. Sauvegarde locale restaurée dans une base privée non exposée en HTTP, comparée sur les 58 tables, puis migration locale conforme au résultat testé.

L'audit technique des 5 433 fiches locales trouve 5 403 images lisibles, une fiche sans couverture et 29 fichiers absents dans l'ancien dossier `livres/demo/`. Ce constat local ne décrit pas automatiquement la production et n'atteste pas la correspondance bibliographique de toutes les images. Les trois couvertures des romans Orange ont été examinées visuellement.

Suite et critères de recette : [plan de consolidation](plan-consolidation.md). Le propriétaire reporte la configuration Google. La livraison suivante précise les décisions sur les événements gratuits, l'historique et les QR ; écran de santé, audit complet d'accessibilité et purges restent des chantiers à venir.

### Événements gratuits et historique des décisions — 4 novembre 2026

Le propriétaire maintient tous les événements gratuits : aucun prix ni filtre gratuit/payant n'est ajouté. Le calendrier et les fiches l'annoncent explicitement, les formulaires d'équipe et d'auteur le rappellent. `sql/maj_evenements_gratuits.sql` remplace uniquement les neuf phrases tarifaires de démonstration connues ; les autres informations et inscriptions sont conservées. Le SQL de démonstration est harmonisé pour les nouvelles installations.

`admin/historique_decisions.php` expose les nouvelles décisions en lecture seule : acteur et pseudo d'époque, date, objet, décision, états avant/après et motif éventuel. Recherche textuelle, filtres par domaine/acteur/action/période, 30 lignes par page. Chaque rôle voit seulement les domaines actuellement autorisés ; aucun lecteur ou auteur n'y accède et les candidatures d'équipe restent privées à l'administrateur.

`decisions_equipe_lib.php` accompagne sous transaction la validation et correction de fiches, leur archivage/réactivation, les décisions sur les annonces et dons, la modération des avis, l'acceptation/refus des propositions d'événements et des candidatures d'équipe. Une écriture du journal en échec annule l'action. Double validation d'une fiche : une seule action et une seule trace. Le nouveau tableau `equipe_decision` est créé par `sql/maj_historique_decisions.sql`, reporté dans l'installation complète. Aucune ancienne décision n'est reconstituée ; les historiques métier antérieurs restent conservés. Messages, litiges de ventes et candidatures auteur ne sont pas centralisés dans cette première version.

Recette : 60 contrôles métier et neuf contrôles complémentaires des points et traces sur chacune des versions PHP 8.4/MariaDB 11.8 et PHP 8.2/MariaDB 10.4 ; quatre processus concurrents n'obtiennent qu'une validation et une trace sur chaque version. Navigateur : 137 contrôles à 375/768/1440, visiteurs et clients sur les événements, trois rôles d'équipe sur l'historique, files de modération, CSRF, échappement, droits modifiés, suspension et migration absente. Captures examinées et libellés de l'historique rendus lisibles. Installation neuve vérifiée avec les données de démonstration : historique vide et aucune mention de tarif payant.

Le propriétaire confirme le bon fonctionnement du scan physique des dons sur son téléphone. Cette confirmation n'est pas une nouvelle recette indépendante ; les emprunts restent à vérifier physiquement et Google est reporté.

### Santé du site — 5 novembre 2026

`admin/sante.php` et `sante_site_lib.php` ajoutent un aperçu réservé à l'administrateur, accessible depuis le menu Système. Quatre indicateurs : dernier passage des relances, échecs de courriel signalés dans le journal d'activité, entrées datées du journal PHP et derniers exports SQL ou dossiers de copie du code détectés dans les dossiers privés prévus. Aucun contenu de journal, adresse email, nom de fichier d'export ou chemin privé n'est affiché. L'actualisation est manuelle.

Le suivi signale un début de relance de plus de 36 heures, un passage en cours depuis plus de quinze minutes, un résultat en erreur ou des dates incohérentes. Les compteurs de journaux portent sur les 24 dernières heures dans les 512 Kio les plus récents ; un extrait tronqué ou une information indisponible reste explicitement identifié. Un compteur de courriels à zéro ne prouve pas leur réception.

Les exports des conventions de déploiement et XAMPP sont recensés sans descendre dans les sous-dossiers. Taille, date de fichier et marqueur final de mysqldump sont contrôlés ; un fichier vide, incomplet, daté dans le futur ou vieux de plus de sept jours est à vérifier. L'inventaire est limité à 2 000 entrées et indiqué partiel si nécessaire. Un dossier de copie du code repéré ne prouve pas son contenu. Les sauvegardes externes de l'hébergeur ne sont pas recensées et aucun test automatique de restauration n'est revendiqué par l'écran.

Sans migration : les informations existantes sont lues, aucune structure ni donnée métier n'est modifiée. La page ne lance ni relance, envoi de mail, sauvegarde ou restauration. Le fonctionnement habituel des sessions et de la journalisation des visites du back-office reste inchangé.

Recette : 44 contrôles sous PHP 8.4/MariaDB 11.8 et 43 sous PHP 8.2/MariaDB 10.4, dont lecture SQL sous transaction en lecture seule, fichiers inchangés, absences, dates limites, exports incomplets et journaux volumineux. Le contrôle supplémentaire 8.4 porte sur un dossier de plus de 2 000 entrées. 139 contrôles navigateur à 375/768/1440 : quatre scénarios d'indicateurs, accès administrateur, refus des autres rôles, menu masqué, absence de contenu privé et accueil visiteur/client inchangé. Les captures des trois largeurs ont été examinées.

### Catalogue et parcours de lecture — 5 novembre 2026

`trouver_lecture.php` et `lecture_lib.php` proposent un choix public d'univers, d'ambiance et de budget par livre. Les trois critères sont combinés strictement ; seuls les livres validés avec une offre magasin disponible sont retenus, au plus six et un par série, avec une graine de session stable. Aucun profil supplémentaire ni choix enregistré en base. Le formulaire GET fonctionne sans JavaScript ; le lien vers le catalogue conserve les critères. En l'absence de résultat, aucun élargissement silencieux. Les suggestions personnalisées existantes sont conservées. Prix hors livraison et remises ; prix et stock sont à nouveau contrôlés par le panier.

Le calcul retient 120 candidats ordonnés par une empreinte déterministe avant dédoublonnage des séries : il peut donc afficher moins de six livres, sans prétendre explorer toutes les œuvres. Recette sur données fictives : 29 assertions sur PHP 8.4/MariaDB 11.8 et 29 sur PHP 8.2/MariaDB 10.4 ; 78 contrôles Playwright visiteur/client à 375/768/1440, entrées malformées, état vide, lien d'accueil et absence de JavaScript. Mesure CLI sur une restauration privée non exposée de 5 695 fiches : médianes 76 ms sans critère et 33 ms avec univers/ambiance/budget, hors temps réseau et rendu complet.

L'audit technique de production porte sur 5 695 fiches : aucun ISBN renseigné à clé invalide, aucun ISBN dupliqué, aucune plage de numérotation inversée, aucune couverture absente ou illisible. Les numéros communs à plusieurs éditions ne sont pas supprimés. Ces constats ne prouvent ni la complétude des séries ni la correspondance visuelle de toutes les couvertures. La série manga Fullmetal Alchemist portait encore l'auteur des romans ; `sql/maj_fullmetal_auteur.sql` corrige uniquement son auteur en Hiromu Arakawa, confirmé par la notice BnF du tome 2 (EAN 9782351420188, notice FRBNF40034102). Les 27 mangas, 18 Perfect Edition et cinq romans restent séparés et leurs stocks inchangés. Structure d'installation également mise à jour.

En local, 29 fichiers manquants sont récupérés depuis les couvertures de production associées aux mêmes ISBN, sans écraser de fichier existant. Après récupération : 5 432 couvertures lisibles, une fiche locale sans couverture ni ISBN vérifiable conservée pour contrôle manuel. Aucune image de livre ajoutée à Git.
### Collections personnelles — 5 novembre 2026

`ma_collection.php` et `collection_personnelle_lib.php` ajoutent le suivi privé des séries pour les comptes lecteur et auteur actifs. Recherche de séries, enregistrement des volumes possédés, retrait du suivi et liste des volumes manquants avec disponibilité et prix magasin. Accès depuis le menu du compte et le bouton « Compléter ma collection » des pages de séries. Aucun ajout automatique depuis les achats ; aucune alerte ni mail dans cette version.

Migration rejouable `sql/maj_collection_personnelle.sql`, reprise dans l'installation : `collection_serie` (membre, série) et `collection_volume` (volume logique, référence). La suppression d'un membre ou d'une série supprime son suivi par clés étrangères. Les volumes numérotés sont identifiés par début/fin dans une série ; les autres par clé d'œuvre, puis référence si aucune clé. Les rééditions du même volume sont regroupées selon le catalogue existant. Un double 1–2 reste distinct des simples 1 et 2 ; les formats Perfect/Deluxe déjà séparés ne sont pas assimilés. Ces rapprochements dépendent des métadonnées existantes et ne certifient pas leur exactitude bibliographique.

Les manquants sont calculés parmi les seuls livres publics référencés. La sélection d'édition privilégie une offre disponible puis le prix ; une référence indisponible reste affichée comme telle. Les choix sont enregistrés manuellement, indépendamment des offres et commandes. Les anciennes références masquées ne sont pas effacées lors d'un nouvel enregistrement. Limites : 100 séries suivies et 500 volumes affichés par formulaire ; recherche limitée à 50 résultats. Le dernier enregistrement complet fait foi en cas d'onglets concurrents.

Écritures POST/CSRF, validation serveur des volumes de la série, transactions et verrouillage de la ligne du membre ; seuls ses propres enregistrements sont lus et modifiés. Un échec conserve les choix précédents. Sans migration, consultation avec avertissement et enregistrement refusé. Recette isolée : 29 assertions métier par version PHP 8.4/MariaDB 11.8 et PHP 8.2/MariaDB 10.4, six écritures concurrentes cohérentes, installation neuve de 61 tables sur chaque version, puis 101 assertions navigateur aux trois largeurs pour lecteur/auteur et les refus des autres rôles. Aucun compte de test en production.
### Sélection en masse et menu du compte compact — 5 novembre 2026

Dans Mes collections, les boutons « Tout cocher » et « Tout décocher » changent uniquement les cases de la série affichée. Le compteur annonce les changements et rappelle d'enregistrer. Aucune sauvegarde automatique ni nouvelle action serveur : le formulaire POST/CSRF existant reste nécessaire. Les boutons sont masqués sans JavaScript et le formulaire manuel reste utilisable. Script partagé et versionné par la date du fichier : javascript/collection_personnelle.js.

Le menu du compte conserve ses liens et compteurs, avec des lignes resserrées, défilement interne et hauteur plafonnée à 480 px sur ordinateur ou 560 px sur écran étroit, réduite selon la place disponible. Sur mobile, le panneau garde son voile de fermeture et sa croix reste visible pendant le défilement. Navigation clavier par Tab, flèches, Début/Fin et Échap ; focus contenu dans le panneau mobile, puis rendu au déclencheur. L'interception limitée au menu personnalisé évite le conflit avec le gestionnaire clavier délégué de Bootstrap. Les animations existantes sont raccourcies et le mouvement réduit est respecté. Sans changement SQL.
### Messages de progression des collections — 5 novembre 2026

Les compteurs administratifs de Mes collections sont remplacés par des encouragements : première trouvaille, volumes déjà sur l'étagère, dernier volume à dénicher et félicitations avec le nom de la série. La réussite est accompagnée de la précision « volumes référencés dans notre catalogue » ; elle ne certifie ni une série terminée ni une bibliographie exhaustive. Les totaux des résumés dédoublonnent les éditions du même volume et excluent les références masquées, sans effacer les choix sauvegardés. Les lectures nécessaires sont regroupées pour toutes les séries du membre, sans requête par série. Aucune migration ni modification des enregistrements existants. Recette : 13 contrôles ciblés sur chaque version PHP/MariaDB et 138 vérifications navigateur des états vide/partiel/presque complet/complet à 375/768/1440, lecteur et auteur.
### Couvertures à cocher et étagère virtuelle — 5 novembre 2026

Mes collections présente chaque volume comme une carte avec sa couverture et son titre, entièrement cliquable pour cocher la case native. Deux cartes par ligne sur téléphone, grille adaptative sur les autres écrans ; couverture non déformée, état coché visible et focus clavier conservé. Une image de remplacement est utilisée si le fichier manque, via le helper existant. Aucune nouvelle couverture importée ni image de livre versionnée.

L'étagère est rendue en PHP à partir des choix enregistrés puis mise à jour en JavaScript au clic, au clavier et lors des sélections en masse. Trois niveaux exposent jusqu'à 36 volumes ; les autres restent sélectionnés et leur nombre est annoncé. Des recadrages des couvertures évoquent les tranches : ce ne sont pas des photographies authentifiées des dos des éditions. Animation légère des nouveaux livres, désactivée en mouvement réduit. Sans JavaScript, l'aperçu enregistré reste visible et le formulaire fonctionne. L'enregistrement POST/CSRF reste explicite. Aucun changement SQL ni nouvelle écriture automatique.

94 vérifications navigateur réussies sur comptes fictifs lecteur/auteur à 375/768/1440, avec images publiques associées aux références de recette et stocks fictifs : sélection par image, clavier, remplissage/vidage, persistance, couvertures chargées et non déformées, séries longues, limite d'aperçu, image manquante, modes avec/sans JavaScript et mouvement réduit. Rendu sans JavaScript corrigé pour neutraliser le flottement Bootstrap de la légende du formulaire. Syntaxe PHP 8.2/8.4 et JavaScript contrôlée, captures aux trois tailles revues. Pas de nouvel audit bibliographique global ni de compte de test en production.
### Meuble illustré et couverture du tome 22 — 5 novembre 2026

L'étagère verte schématique est remplacée par une illustration SVG originale de meuble, avec montants, tablettes, fond ombré, grain discret, pieds et profondeur. Les livres ont désormais exactement la même hauteur et la même largeur relative sur les trois niveaux ; les variations nth-child sont supprimées. Les images des volumes exposés (36 au plus) sont chargées immédiatement pour éviter une case blanche lors de l'apparition de l'aperçu. L'animation, les cases et l'enregistrement explicite sont conservés. Les tranches restent des représentations à partir des couvertures, et non des photographies des dos des éditions.

La couverture du tome 22 de Jujutsu Kaisen (EAN 9791032716205) provient désormais de la fiche officielle Ki-oon : https://ki-oon.com/9791032716205-jujutsu-kaisen-edition-simple-volume-22. Image portrait 800 × 1218, sans les marges blanches de l'ancienne miniature. Nouveau chemin pour éviter l'ancien cache ; image non versionnée dans Git, ancienne image conservée. Migration ciblée et rejouable sql/maj_couverture_jjk22.sql, également reportée dans l'installation, sans toucher aux volumes possédés ni aux stocks.

112 vérifications navigateur réussies sur copie fictive PHP 8.4/MariaDB 11.8, lecteur/auteur à 375/768/1440 : chargement de l'illustration et des couvertures, hauteur identique des 31 livres (écart inférieur à 1 pixel), sélection, persistance, modes sans JavaScript et mouvement réduit. Correction de couverture rejouée sur MariaDB 11.8 et 10.4 avec résultat identique. Le rendu conserve les limites de 36 livres exposés et de 500 volumes par formulaire.
### Dos de livres typographiques — 5 novembre 2026

Les couvertures recadrées sont retirées de l'étagère. Chaque dos est une illustration typographique de taille uniforme, avec le titre de la série à la verticale et le numéro réel du volume ; les œuvres non numérotées affichent leur titre et un repère décoratif sans numéro inventé. Aucun logo d'éditeur ni photographie de tranche n'est inventé. Ce sont des tranches stylisées, pas la reproduction exacte de chaque édition. Les couvertures entières restent disponibles dans la grille de sélection.

Le rendu PHP enregistré et le rendu JavaScript interactif utilisent les mêmes métadonnées et ne créent plus d'image dans les dos. Limites, animation, modes sans JavaScript et mouvement réduit, sauvegarde explicite et distinction des formats conservés. Aucune migration ni modification des choix enregistrés. Recette : 136 vérifications navigateur réussies en lecteur/auteur à 375/768/1440, dont absence de couverture recadrée, titre vertical, numéros exacts, hauteurs uniformes et maintien de la grille de couvertures.
### Livres de face sur l’étagère — 5 novembre 2026

À la demande de Gaël, les tranches typographiques sont remplacées par les couvertures entières de face. Cinq livres par niveau, soit quinze couvertures exposées ; les autres volumes restent cochés, enregistrés et comptabilisés. Les cadres sont uniformes et les images conservent leurs proportions, sans recadrage. Le rendu PHP enregistré et l’aperçu JavaScript utilisent les mêmes couvertures que la grille. Aucune migration ni modification des choix enregistrés.

### Meuble de collection agrandi — 5 novembre 2026

Meuble vertical à cinq tablettes, cadre gris sauge, fond clair et ombres discrètes. Jusqu’à vingt-cinq couvertures entières exposées, cinq par niveau, et largeur maximale portée à 620 px. Les volumes supplémentaires restent enregistrés et comptabilisés.

### Diversification du catalogue — 5 novembre 2026

Import de 500 nouvelles références papier françaises à ISBN contrôlés : 100 supports scolaires (primaire, collège, lycée et prépa), 90 romans ou récits, 35 recueils de poésie, 25 ouvrages de théâtre, 30 références d’art/culture, 60 de savoirs et 35 de vie pratique ; les 125 références graphiques représentent exactement 25 % (87 BD, 32 comics, 6 mangas). La catégorie existante Poésie & Théâtre accueille les deux ensembles. Les niveaux et matières scolaires sont renseignés quand ils sont identifiables dans les notices. Les programmes anciens ne sont pas présentés comme les programmes actuels.

500 notices BnF conservées avec leurs identifiants et sources ; 500 couvertures optimisées, environ 13,3 Mo. Les dates complètes ne sont pas inventées : seule l’année bibliographique vérifiée est renseignée. Prix neuf laissé inconnu pour 36 éditions ; les autres montants sont ceux de la notice, potentiellement historiques. Trois offres d’occasion de démonstration par référence, soit 1 500 offres et 1 500 exemplaires fictifs. Les présentations sont bibliographiques et ne prétendent pas résumer chaque intrigue. Les collections bibliographiques sont conservées sans créer de numéros ou rattachements de série incertains.

`sql/ajout_catalogue_equilibre_20261105.sql` est un ajout de données optionnel, sans évolution de structure. Il n’écrase ni les anciennes fiches, ni leurs couvertures, ni les stocks. Réexécution sans doublon et sans réapprovisionnement d’une offre épuisée. Les images sont livrées séparément, hors Git ; le CSV public fournit les références et sources.

### Découverte, scolaire et suivi des lectures — 6 novembre 2026

Trois carrousels mettent en avant les romans, la poésie/théâtre et les révisions à 10 € maximum. Sélection quotidienne déterministe, limitée à huit livres disponibles avec couverture par rayon et un livre par série. Le bandeau de trois livres et « Nouveau chez nous » conservent leur fonctionnement. Pas de donnée commerciale inventée ni de nouvelle couverture.

`scolaire.php` propose le niveau puis les matières correspondantes, une sélection de douze exemplaires disponibles avec l’année d’édition, et un lien vers tous les résultats avec les mêmes filtres. Le rayon scolaire des menus mène à ce parcours. Les saisies inconnues ou mal formées renvoient 400 ; un choix sans stock conserve un message et des liens de repli. Les années anciennes ne sont pas présentées comme des programmes actuels. Parcours utilisable sans JavaScript.

Mes collections distingue possession et lecture : statuts facultatifs « À lire », « En cours », « Lu », ou non renseigné. `sql/maj_statut_lecture_collection.sql` ajoute la colonne nullable `collection_volume.statut_lecture`, également ajoutée à l’installation. Aucune lecture n’est déduite des volumes déjà possédés. Les éditions équivalentes gardent leur clé logique et leur statut. La sauvegarde conserve les statuts si un ancien formulaire n’en transmet pas ; les volumes décochés sont retirés explicitement. Le propriétaire est verrouillé, le rôle et le statut actif contrôlés, toutes les écritures sont POST/CSRF. Sans migration, le suivi de possession reste disponible et les choix de lecture ne sont pas affichés.

Les statuts sont regroupés dans un champ JSON avant soumission avec JavaScript : une collection de 500 volumes reste sous la limite du nombre de champs PHP. Les choix de lecture sont activés lorsque le volume est coché. Sans JavaScript, enregistrez d’abord un nouveau volume possédé pour activer son choix de lecture ; les listes natives fonctionnent ; si un très grand formulaire dépasse la limite du serveur, un marqueur de fin permet de refuser la demande complète plutôt que d’enregistrer des choix tronqués. Le statut non renseigné est conservé comme NULL.

La revue couvre les nouveaux parcours en visiteur, lecteur et auteur à 375/768/1440, ainsi que les pages principales de compte et de communauté sur téléphone, les libellés de boutons, les filtres invalides, CSRF, JSON incorrect, formulaire tronqué, pages vides et clavier. Correction d’une fiche absente qui répondait 200 : réponse 404 et lien de retour au catalogue. Ces contrôles ciblés ne constituent pas un audit d’accessibilité exhaustif.

### Fiche livre plus compacte et illustrée — 6 novembre 2026

Titre en police de lecture, limité à environ 22 px sur téléphone et 30 px sur ordinateur ; prix, avis et choix d’état regroupés dans un panneau bordé. Deux colonnes de conditions, repères d’année d’édition/pages/langue affichés seulement lorsqu’ils sont renseignés, onglets soulignés et rappel explicite de la démonstration. Une illustration SVG originale de lecteur, plantes, cœur et étoiles accompagne les couvertures entières ; la référence visuelle fournie inspire l’organisation, sans copie d’illustration ou de promesse commerciale. Légère inclinaison au survol, désactivée en mouvement réduit ; mode sombre conservé. Une colonne sur tablette et téléphone, deux sur grand écran.

Aucune modification des prix, stocks, formulaires et contrôles d’achat ; données et actions existantes conservées. Auteur NULL normalisé en chaîne vide lors du chargement et affiché comme non renseigné, sans lien vers une recherche vide. Aucun extrait ou délai d’expédition inventé. Illustration d’interface versionnée, aucune image de livre ajoutée au dépôt. Pas de migration.

### Aide au prix du stock — 6 novembre 2026

Dans les exemplaires du magasin, le gestionnaire et l’administrateur disposent d’une proposition par état : dernier tarif enregistré (offre de plus grand identifiant pour cet état, même épuisée), sinon prix neuf de référence pour le neuf, ou 63/46/30 % de cette référence pour très bon/bon/usé. La provenance est affichée ; sans référence ni tarif existant, aucune somme n’est inventée. Les annonces de particuliers et les emprunts sont exclus. Aucun tarif n’est présenté comme une cote actuelle ; les références peuvent dater et les anciens prix peuvent venir de la démonstration.

Le bouton renseigne le champ seulement sur demande ; changer d’état actualise l’aide sans écraser une saisie. Le montant reste libre, enregistré uniquement par le formulaire POST/CSRF habituel. Sans JavaScript, les quatre propositions et leur provenance restent lisibles. Aucune offre existante, commande ou quantité n’est modifiée par la consultation de l’aide. Aucune migration.

La fiche d’administration permet aussi de renseigner ou de vider le prix neuf de référence déjà prévu dans le schéma. Validation positive, deux décimales maximum ; champ vide enregistré NULL. Une modification de référence ne réécrit pas les tarifs des exemplaires. Un ancien formulaire sans ce champ conserve la référence existante.

### Fluidité de l’inventaire et confirmation de commande — 6 novembre 2026

L’inventaire affiche 50 titres par page, avec total et compteurs sur tout le résultat filtré. Recherche, catégories, archives et tris restent disponibles ; la pagination conserve les filtres, les tris réinitialisent la page et un identifiant départage les ex æquo. Les couvertures sont chargées à la demande.

La gestion d’un titre propose quatre vues accessibles par URL : En vente, Réapprovisionner (avec aide au prix), Offres épuisées et Emprunts. Une seule tâche est affichée ; les données des emprunts sont interrogées seulement sur leur vue. La création d’un titre arrive sur Réapprovisionner ; le comptoir mène à la vue Emprunts. Actions POST/CSRF et historique conservés. Pas de migration.

Après paiement, les liens vers commandes et points/badges sont espacés de 16 px. Une règle de zone tactile qui appliquait des marges négatives aux liens en gras exclut désormais les boutons, qui ont déjà leur propre surface de clic. La carte s’adapte à la tablette. Aucun changement du calcul, de la réservation ou de la validation du paiement. Les mesures de gain concernent l’inventaire sur copie fictive et ne constituent pas un audit de performance de tout le site.

### Couvertures légères et suggestions réutilisées — 6 novembre 2026

Les cartes partagées utilisent une miniature WebP locale (260 px de haut maximum, 360 px de large maximum, sans recadrage ni agrandissement) en affichage standard et proposent l’original en 2x. Les grandes fiches restent sur les originaux. Le repli est automatique si la miniature manque, est plus ancienne que l’original ou si la source est distante/hors du dossier livres. Les miniatures ont une URL versionnée et restent hors Git. Le générateur CLI outils/generer_miniatures.php crée les dérivés sans changer les originaux ; il est rejouable et n’est pas exécuté pendant une requête web. Après un nouvel import, sa relance peut préparer les nouvelles miniatures ; en attendant, les originaux restent utilisables. Aucun dossier de photos de membres, d’annonces ou de dons n’est traité.

Les suggestions de recherche gardent en mémoire dans la page au maximum 20 réponses publiques pendant 30 secondes. La temporisation de saisie, l’annulation des requêtes, la protection contre les réponses tardives et Échap sont conservés. Aucun cache partagé de page personnelle, aucun stockage persistant ni changement du classement, du stock, des avis ou des favoris. Recherche classique utilisable sans JavaScript. Les gains mesurés concernent le poids des couvertures en affichage standard ; les requêtes SQL du catalogue et de la recherche n’ont pas été changées. Pas de migration.

### Calcul du catalogue Découverte regroupé — 6 novembre 2026

Le comptage complet, la sélection limitée à deux tomes par série et la page de résultats du mode Découverte sont calculés dans une seule requête de lecture. Les opérations de filtrage et d’agrégation restent avant les fonctions de rang ; le total complet est calculé avant le plafonnement par série, le total paginé après. Une page trop grande est ramenée à la dernière page dans cette même requête ; un résultat vide garde zéro livre et une page vide utilisable. Graine de visite, ordres, prix, stocks libres de réservation, filtres, tags, avis et favoris conservés.

Les autres tris, dont Pertinence par défaut lors d’une recherche texte, ainsi que le compteur AJAX conservent leur chemin de calcul. Aucun cache partagé ni index ou migration ajouté. Compatibilité vérifiée sur MariaDB 10.4 et 11.8, comparaisons avant/après à sessions et graines identiques. Les mesures distinguent le calcul SQL principal du temps HTTP de la page complète.


### Audit UX/UI et corrections locales — 6 novembre 2026

Réalisé localement après le commit 0cc932b, puis publié avec le commit 828529b le 14 novembre 2026. Aucun changement de schéma, de base principale, de prix, de stock ou de règles de commande.

L’audit initial porte sur 45 combinaisons rôle/route à 375, 768 et 1440 px, soit 135 affichages. Les critères graphiques des captures ont été confrontés au projet : conservation des verts, des deux polices locales, des illustrations originales et du relief des couvertures. Les panneaux non interactifs sont stabilisés, sans supprimer les micro-interactions utiles.

Corrections : liens d’évitement public et équipe ; repère main unique sur les pages concernées ; titres d’onglets ; focus et bordures de champs ; débordement des titres d’annonces ; recherche avec clavier, attente et erreur explicites, annulation et cache conservés ; avis de l’accueil sans rotation automatique ; notes nommées pour les technologies d’assistance ; signalement des avis de démonstration identifiables ; une commande annulée ne donne plus le repère de commande. Deux paramètres GET mal formés (newsletter et tri des avis) sont traités sans erreur PHP. Liens sociaux sans destination et logos de paiements non implémentés retirés. Le thème sombre existant utilise davantage les couleurs partagées ; aucun nouveau sélecteur de thème n’est ajouté.

Les essais ciblés complètent le relevé visuel : recherche avec et sans JavaScript, clavier, cache, erreur réseau, contenu échappé, avis et droits d’équipe. Le contrôle de contraste automatisé reste partiel. Ce lot ne constitue pas une conformité WCAG 2.2 AA : lecteur d’écran, alternative accessible au captcha et configurations juridiques réelles restent à vérifier. Les CGU non publiées dans la copie fictive ne démontrent pas une absence en production. Les rapports et captures privés sont conservés hors Git.


### Audit technique et préparation à la préproduction — 6 et 7 novembre 2026

Réalisé localement sur refonte après 0cc932b, sans migration, puis publié avec le lot suivant le 14 novembre 2026 (commit 828529b, branches refonte et main, hébergement IONOS).

CSRF ajouté à la connexion client et à l’inscription ; types et longueurs des champs d’inscription contrôlés côté serveur. Limites persistantes par empreinte d’IP dans un fichier privé verrouillé : inscription 10/heure, newsletter 5/heure, récupération 5/15 minutes, connexion client 30/15 minutes après validation du puzzle. Les compteurs ne dépendent pas du cookie de session ; la fermeture du stockage refuse les nouvelles demandes. Ce mécanisme vise un serveur unique, pas une protection contre une attaque distribuée. Les limites de connexion équipe et de messagerie existantes sont conservées.

L’actualisation de la messagerie d’équipe utilise POST avec CSRF ; la lecture GET du point JSON ne marque plus les messages lus. Les journaux neutralisent les retours à la ligne des valeurs et verrouillent les écritures. Un en-tête de proxy ne prouve plus HTTPS sans correspondance avec une adresse de proxy explicitement autorisée (PROXY_HTTPS_FIABLES, vide par défaut dans le modèle). Vérifier le fonctionnement HTTPS de l’hébergeur avant tout futur déploiement, sans recopier les secrets.

Les exceptions PHP non interceptées en mode production reçoivent une réponse générique HTML ou JSON avec code 500 ; une configuration manquante ou une base indisponible renvoie 503. Ce traitement ne remplace pas celui des erreurs du serveur web ou des erreurs antérieures au chargement de PHP. La route 404 Apache est indépendante du sous-dossier XAMPP. Les protections des fichiers et les codes HTTP ont été vérifiés avec un Apache isolé.

Référencement préparé : description, canonique et partage Open Graph pour les pages publiques autorisées en mode production ; image originale reprenant le favicon et la charte ; robots.txt servi par robots.php ; sitemap.php limité aux entrées statiques retenues et aux livres publiés. Le local interdit l’indexation et ferme son sitemap. Pages privées, recherche et variantes paramétrées exclues par défaut ; les collections par auteur restent non indexables. Aucun traceur d’audience ajouté. Les durées de conservation non implémentées et les cookies réellement utilisés sont décrits honnêtement dans le texte de confidentialité par défaut ; un texte personnalisé en base garde la priorité et doit être revu séparément.

Recette : PHP 8.4/MariaDB 11.8, PHP 8.2/MariaDB 10.4 et Apache isolés ; contrôles CSRF, types invalides, inscription et connexion complètes, emails capturés localement, droits, accès horizontal ciblé, erreurs, liens et rendu 375/768/1440. Six dépendances verrouillées concordent avec vendor ; audit Composer sans avis applicable au moment du contrôle. Les tests métier antérieurs de commandes et de concurrence ne sont pas présentés comme réexécutés dans ce lot.

État retenu : techniquement prêt pour une recette de préproduction, pas validé pour une ouverture à de vrais utilisateurs. Avant publication réelle : textes et durées juridiques, purge/anonymisation, consentement et désabonnement newsletter sans compte, expiration/hachage des jetons de validation email, hébergement HTTPS, accessibilité complète et reprise après incident restent à vérifier ou finaliser. Voir checklist-preproduction.md. LCP et CLS ont été relevés en laboratoire local, sans preuve d’INP ni de Core Web Vitals réels ; quelques CLS dépassent légèrement 0,1.


### Confirmation des emails et consentement newsletter — 7 novembre 2026

Publié le 14 novembre 2026 : commits 828529b et 2036858 sur refonte et main, mise en ligne IONOS. Migration sql/maj_consentement_email.sql, reproduite dans l’installation complète, appliquée sur la base locale et sur la base de production après sauvegarde.

Validation des comptes : jeton aléatoire de 32 octets, empreinte SHA-256 uniquement en base, expiration à 24 heures, consommation conditionnelle à usage unique. GET affiche une confirmation, POST/CSRF active le compte. Renvoi depuis renvoyer_validation.php : réponse générique, délai de 60 secondes par adresse et limite de 5 demandes par IP/15 minutes. Le nouveau lien révoque le précédent. La migration convertit les anciens jetons valides au format prévu en empreintes, leur laisse 24 heures et efface la colonne historique en clair ; rejouer la migration ne prolonge pas les liens. Sans migration, la création de compte refuse proprement plutôt que de conserver un jeton en clair. Récupération de mot de passe, invitations et Google ne sont pas remplacés par ce mécanisme.

Newsletter : le formulaire public crée seulement une demande, sans activer d’abonnement. Le lien reçu expire à 24 heures ; une confirmation POST/CSRF active les préférences et enregistre la date, le texte exact du choix et sa source. Le profil authentifié avec email vérifié conserve un choix direct, enregistré avec la source profil. Les préférences membre et abonné externe sont synchronisées pour qu’un désabonnement ne laisse pas une deuxième inscription active.

Chaque campagne envoie un email individuel avec un lien de désabonnement aléatoire propre au message, conservé seulement sous empreinte. Le lien ouvre une page lisible sans compte ; seul POST/CSRF retire l’abonnement. Plusieurs anciens emails d’un abonnement courant restent utilisables ; un réabonnement change la génération et révoque les anciens liens. Pas de retrait automatique au simple passage d’un analyseur de liens. En-tête List-Unsubscribe fourni, sans annoncer le protocole One-Click qui n’est pas implémenté. Les erreurs d’envoi sont comptées, sans relance automatique des destinataires ayant déjà reçu leur lettre. L’envoi reste synchrone ; une file est à prévoir pour de grandes campagnes.

Les abonnements historiques (source reprise, sans date de confirmation) sont « à reconfirmer » : aucune date ou preuve de consentement n’est inventée, ils sont exclus des campagnes et ne reçoivent aucune relance automatique. Ils restent désabonnables et peuvent reconfirmer depuis le pied de page (lien par email) ou le profil. L’administration distingue abonnés confirmés et à reconfirmer. Les textes personnalisés en base ne sont pas réécrits ; ils devront être relus avant publication.

Recette : 36 contrôles métier par version sur PHP 8.4/MariaDB 11.8 et PHP 8.2/MariaDB 10.4 ; 147 contrôles web, 16 emails capturés localement, vues 375/768/1440 en visiteur et connecté. Deux processus concurrents pour un même lien n’acceptent qu’une confirmation (compte et newsletter). Installation neuve et migration rejouée vérifiées sur les deux versions, données livres/exemplaires/commandes conservées. Les limites RGPD, l’accessibilité exhaustive et l’hébergement de la checklist restent ouverts. Les rapports privés antérieurs constituent des états historiques ; ce lot corrige leurs points newsletter et validation email sans prétendre clore les autres réserves.

### Lot 1 des retours : correctifs et compte — 14 novembre 2026

Onglets de la fiche livre (Livraison, Paiement (démo), Nos engagements, puis résumé, détails et auteur) : panneaux superposés de hauteur constante, plus aucun défilement ni saut au clic (0 décalage sur 54 mesures à 375, 768 et 1440 px).

Mots de passe (inscription lecteur et auteur, changement, réinitialisation) : bouton afficher/masquer avec aria-pressed et libellé, atteignable au clavier ; règle « Au moins 8 caractères » sous le champ avec état et annonce vocale en direct ; contrôle serveur commun dans mot_de_passe_lib.php. La candidature d’auteur utilise désormais le jeton de validation sous empreinte (les candidats ne pouvaient plus valider leur adresse depuis la mise en ligne du 14 novembre).

« Rester connecté » : lecteurs seulement, table connexion_persistante (sql/maj_connexion_persistante.sql), sélecteur et empreinte SHA-256 du validateur, 30 jours, cookie BIBLIOCCAZ_SOUVENIR HttpOnly, SameSite=Lax et Secure en HTTPS. Rotation complète à chaque reconnexion automatique (expiration d’origine conservée) ; un validateur faux supprime tous les jetons du membre. Chaque reconnexion revérifie rôle, statut, email vérifié et empreinte du mot de passe. Jetons supprimés à la déconnexion, au changement et à la réinitialisation du mot de passe, au changement d’email, à la suspension, au changement de rôle et à la fermeture des sessions par l’administration. Sans migration, la case reste sans effet et rien ne casse. Cookie décrit dans la politique de confidentialité.

Connexion : message neutre « Identifiants incorrects » pour une adresse inconnue comme pour un mauvais mot de passe, identifiant conservé, lien « Pas encore de compte ? Créer un compte » qui pré-remplit l’email via la session (jamais depuis l’URL, usage unique), encart de création de compte mis en avant.

Avis : motif de signalement obligatoire avec option vide non sélectionnable ; points réservés aux avis d’au moins 50 caractères (compteur en direct, message clair, règle côté serveur, points en attente annulés si l’avis est raccourci sous le seuil). Les avis existants et leurs points ne sont pas modifiés.

Hébergement : modèle versionné .htaccess.production (page 404 et redirection HTTPS d’IONOS) ; outils/preparer_envoi.php génère la version de production depuis .htaccess et s’arrête si elle ne correspond plus au modèle.

Recette : PHP 8.4/MariaDB 11.8 isolés (62 contrôles HTTP, 4 contrôles sans migration, 153 contrôles navigateur à 375/768/1440 en visiteur et connecté, rôles administrateur, modérateur et gestionnaire), installation neuve et migration rejouée, PHP 8.2/MariaDB 10.4 locaux (syntaxe, migration rejouée, encodage).

### Lot 2 des retours : panier et avis — 14 novembre 2026

Panier : bouton « Vider le panier » (POST + CSRF, confirmation, viderPanierMinute() supprime aussitôt les réservations de 15 minutes du panier en base) ; animation des lignes vers l’icône du panier puis apparition du panier vide, désactivée si le mouvement réduit est demandé ; fonctionne sans JavaScript par redirection. L’état d’un exemplaire ne se modifie plus dans le panier : affichage en lecture seule, action serveur « changer » supprimée. Le récapitulatif d’achat affiche le libellé d’état lisible.

Avis : cartes séparées, fond blanc et contour noir de 1 px. Motifs de signalement : vulgarité, haine, spam, hors sujet, autre ; les anciens motifs (offensant, spoiler) restent lisibles par l’équipe sur les signalements existants.

Spoilers (sql/maj_avis_spoiler.sql : colonnes avis.spoiler et avis.score_spoiler) : case « Mon avis révèle l’intrigue » ; détection côté serveur dans avis_spoiler_lib.php (mots-clés pondérés, formulations « sans spoiler » neutralisées, seuil 4) recalculée à chaque enregistrement. Un avis marqué (auteur ou détecté) garde sa note visible ; titre et texte sont floutés et cachés aux lecteurs d’écran jusqu’au bouton « Afficher » (réversible, clavier, lien d’ancre sans JavaScript). Aucun signalement n’est créé ; l’équipe voit un badge avec le score. Avis spoilers exclus du bloc d’avis de l’accueil. Les avis existants ne sont pas réanalysés. Sans migration, la case n’apparaît pas et les avis restent enregistrés normalement.

Recette : PHP 8.4/MariaDB 11.8 isolés (44 contrôles sans migration, 56 avec, 15 contrôles de rôles d’équipe, non-régression du lot 1 62/62), 212 contrôles navigateur à 375/768/1440 en visiteur et connecté, dont mouvement réduit et mode sans JavaScript ; installation neuve, migration rejouée ; PHP 8.2/MariaDB 10.4 locaux.

### Lot 3 des retours : captcha et notifications — 14 novembre 2026

Captcha : captcha_lib.php et partie_captcha.php partagés par la connexion, l’inscription, le mot de passe oublié, le renvoi de validation, « Rejoindre l’équipe » et la candidature auteur sans compte. Un puzzle réussi pose captcha_reussi_le dans la session : pendant 30 minutes, les formulaires affichent « Vérification de sécurité déjà réussie » sans nouveau puzzle, toujours vérifié côté serveur. Trois mots de passe erronés effacent le marqueur. Les limites par IP existantes restent actives. Le back-office reste sans captcha.

Notifications (sql/maj_notifications.sql, table notification : type, message, lien, lu, date_lecture) : notifications_membre_lib.php (notifierMembre(), liens internes relatifs uniquement, notifications lues purgées après 90 jours). Cloche pour lecteurs et auteurs avec compteur, aperçu des cinq dernières au survol, page notifications.php ; notification_action.php en POST + CSRF (ouvrir = marquer lu puis suivre le lien, tout marquer comme lu), limité aux notifications du membre. Déclencheurs : modération des dons (validé, à corriger, corrigé, annulé, réservation libérée), réservation, annulation et libération par les membres, récupération ; inscription, liste d’attente, place libérée, événement complet (créateur lecteur ou auteur), proposition validée, refusée ou annulée. Sans migration, la cloche reste vide et rien ne casse.

Recette : PHP 8.4/MariaDB 11.8 isolés (60 contrôles : captcha sur chaque formulaire, expiration, trois échecs, back-office, cloche, CSRF, droits, déclencheurs dons et événements, absence de table ; non-régression lots 1 et 2), contrôles navigateur à 375/768/1440 en visiteur, lecteur et auteur, pages d’équipe pour administrateur, gestionnaire et modérateur ; PHP 8.2/MariaDB 10.4 locaux (syntaxe, migration rejouée, encodage).

### Lot 4 des retours : dons — 14 novembre 2026

Recherche des livres à donner (dons.php) : mots combinés sur titre, auteur, description et fiche liée (ISBN sans tirets), jokers SQL neutralisés, lieu, catégorie, état, tri (récents, à récupérer bientôt, titre), 24 dons par page, puces de filtres retirables, valeurs invalides ignorées. Formulaire de don : la liste complète du catalogue (datalist et JSON embarqués) est remplacée par don_recherche_livre.php (membres connectés, 8 résultats, titre/auteur/ISBN) et javascript/don_recherche.js (combobox ARIA, flèches, Entrée, Échap ; modifier le titre délie la fiche).

Don depuis une commande : livraison fictive (livraison_lib.php, COMMANDE_LIVRAISON_JOURS = 3 jours après la commande, commande validée) affichée dans « Mes commandes » ; « Donner ce livre » sur chaque ligne livrée liée au catalogue ; don_creer.php?ligne= vérifie propriétaire, livraison et nombre de dons actifs par ligne (colonne don.id_commande_ligne, sql/maj_dons_commande.sql), revérifié avec verrou au POST ; un don annulé libère l’exemplaire. Libellé « Voir les livres à donner » dans les raccourcis, le pied de page et la page 404.

Mails : publication du don (lien vers la fiche du don), récupération au donneur (existant) et confirmation au bénéficiaire (nouveau), en plus des notifications du lot 3.

Recette : PHP 8.4/MariaDB 11.8 isolés (35 contrôles avec Mailpit, contrôle sans migration, non-régression lots 1 à 3), navigateur à 375/768/1440 en visiteur et connecté (recherche, puces, combobox au clavier, commandes, don pré-rempli) ; PHP 8.2/MariaDB 10.4 locaux.

### Lot 5 des retours : retours de commande — 14 novembre 2026

Tables retour_commande, retour_ligne, retour_historique (sql/maj_retours_commande.sql) ; retours_lib.php. Une demande active ou remboursée par commande. Retour : commande validée et livrée (livraison fictive J+3), 14 jours ; lignes et quantités contrôlées côté serveur ; montant = part des livres × (total hors livraison / sous-total). Remboursement : commande annulée depuis moins de 30 jours, montant total. Statuts demande → accepte → recu → rembourse (remboursement sans « reçu »), refuse (motif de 10 caractères) et annule (client, tant que « demandé »). Transitions vérifiées avec le statut attendu et un verrou ; « reçu » remet le stock (exemplairePourRetour) sauf motif « abîmé » et prévient les alertes ; « remboursé » retire au plus les points d’achat de la commande sur la part remboursée. Notification et mail au client à chaque étape. Back-office admin/retours.php et admin/retour.php (droit catalogue), menu Ventes avec compteur, lien depuis la fiche commande. Sans migration : pages client sans erreur, back-office explique l’import.

Recette : PHP 8.4/MariaDB 11.8 isolés (51 contrôles : délais, CSRF, quantités, motifs, droits client et équipe, transitions, stock, points, mails, absence de table ; non-régression lots 1 à 4), navigateur à 375/768/1440 en client, administrateur et gestionnaire ; installation neuve ; PHP 8.2/MariaDB 10.4 locaux.

### Lot 6 des retours : service client — 14 novembre 2026

Tables demande_client et demande_client_message (sql/maj_service_client.sql) ; service_client_lib.php. Page service_client.php (lecteurs et auteurs connectés, lien dans le menu du compte avec le nombre de réponses non lues et dans le pied de page) : nouvelle demande (catégorie parmi huit, sujet, commande facultative vérifiée côté serveur, message de 10 à 2 000 caractères), référence SC-AAAA-NNNNN, fil d’échanges, réponse, « Ma question est réglée », réouverture en écrivant pendant 14 jours. Limites : 5 demandes ouvertes, 5 nouvelles par jour, 20 messages par heure. Statuts nouvelle → en_cours → attente_client (« Réponse envoyée ») → resolue.

Back-office (droit equipe : administrateur, gestionnaire, modérateur) : admin/service_client.php (file « À traiter », filtres statut, catégorie, responsable, recherche ; compteur du menu Ventes) et admin/demande_client.php (fil complet avec notes internes jamais montrées au client, réponse avec contrôle de fraîcheur du fil, prise en charge, « Confier à un collègue » qui ouvre ou réutilise la conversation privée de la messagerie de l’équipe avec un lien vers la demande, résolution et réouverture). « Demander l’avis de l’équipe » disponible sur les demandes client et les demandes de retour. Le client reçoit une notification et un mail à chaque réponse, une notification à la résolution. Sans migration : pages sans erreur, back-office explique l’import.

Recette : PHP 8.4/MariaDB 11.8 isolés (51 contrôles : CSRF, catégories, propriété des commandes et des demandes, limites, notes internes masquées, message dans la messagerie d’équipe, fil périmé, réouverture, mails, absence de table ; non-régression lots 1 à 5), navigateur à 375/768/1440 en client, administrateur, gestionnaire et modérateur ; installation neuve (70 tables) ; PHP 8.2/MariaDB 10.4 locaux.

### Lot 7 des retours : messagerie instantanée entre membres — 14 novembre 2026

Colonne conversation_participant.date_ecriture (sql/maj_messagerie_instantanee.sql). message_nouveaux.php (déjà exclu du rafraîchissement d’activité de session) garde son ancien appel GET de comptage et accepte en POST avec jeton CSRF : actualiser (nouveaux messages après le dernier identifiant, 50 au plus, rendus côté serveur par htmlMessageConversation() partagée avec partie_conversation.php ; marque lu seulement si l’onglet est visible ; indicateur de frappe, état ouvert ou fermé, non-lus total et par conversation), ecrire (signal de frappe, valable 6 secondes, effacé à l’envoi) et envoyer (mêmes règles qu’envoyerMessage : participant, conversation ouverte, 1 000 caractères, 30 messages par heure). javascript/messagerie.js : interrogation toutes les 4 secondes uniquement quand une conversation ouverte est affichée et l’onglet visible, arrêt à l’onglet caché et reprise immédiate, recul progressif en cas d’erreur, aucune requête simultanée, dédoublonnage par identifiant, séparateurs de jour, défilement automatique si l’on est en bas sinon bandeau « Nouveau message », annonce pour les lecteurs d’écran, fermeture de la saisie si la conversation est close, message clair en cas de session expirée. Envoi sans rechargement (Ctrl+Entrée compris) ; sans JavaScript, le formulaire vers message_action.php reste inchangé. Sans migration : tout fonctionne sauf l’indicateur de frappe.

Recette : PHP 8.4/MariaDB 11.8 isolés (32 contrôles : CSRF, participants, visiteur, échappement, longueur, frappe et expiration, marquage lu selon la visibilité, conversation close, ancien appel GET, absence de colonne ; non-régression lots 1 à 6), navigateur à deux participants à 375/768/1440 (frappe visible, envoi sans rechargement, réception en environ 4 secondes, compteurs de la liste et de l’entête, aucune requête onglet caché ou sans conversation ouverte), back-office des conversations pour l’administrateur et le modérateur ; installation neuve ; PHP 8.2/MariaDB 10.4 locaux.

### Lot 8 des retours : correctifs UX, commandes, dons, SAV et recherche — 14 novembre 2026

Captcha : captcha_lib.php garde la validation 30 minutes dans la session et dans un cookie BIBLIOCCAZ_CAPTCHA (horodatage signé HMAC avec une clé aléatoire créée à l’exécution dans le dossier privé, liée au navigateur, httponly, SameSite Lax) ; la validation survit à la déconnexion et à l’expiration de session. Trois mots de passe erronés effacent la session et le cookie et inscrivent le cookie dans captcha_echecs.json (dossier privé) pour qu’il ne soit plus accepté. L’inscription demande toujours le puzzle sans validation récente.

Menus : menu du compte regroupé (compte ; achats et retours ; favoris, alertes, adresses ; dons, annonces, ventes, emprunts, événements, collections ; avis, messages, notifications ; Service client en dernier avant la déconnexion), séparateurs. Cloche : bouton « Tout marquer comme lu » dans l’aperçu (POST + CSRF en AJAX via javascript/notifications.js, réponse JSON de notification_action.php, envoi classique en secours) ; bouton conservé sur notifications.php.

Service client : catégorie commande, livraison ou retour seule à montrer et enregistrer la commande (javascript/service_client.js, contrôle serveur) ; message simplement non vide ; réponses types (SERVICE_REPONSES_TYPES, javascript/reponses_types.js) ; « Résoudre et fermer » ferme la demande ; réouverture refusée côté page et serveur après 14 jours (date de clôture, à défaut dernière mise à jour).

Dons : texte de vendre.php corrigé ; table don_photo (sql/maj_dons_photos.sql), deux photos en plus de la principale (DON_PHOTOS_MAX = 3), contrôle getimagesize et renommage par enregistrerImageEnvoyee(), fichiers supprimés en cas d’erreur ou de retrait ; galerie sur la fiche et dans le back-office. don_valider.php : scan caméra (BarcodeDetector + getUserMedia, javascript/scanner_don.js) avec repli sur la saisie du code ; sans BarcodeDetector (Safari iOS, Firefox) un message renvoie vers l’appareil photo du téléphone ou le code à 6 caractères. Back-office : motif facultatif pour valider (« Don conforme. »), un mot suffit pour les autres actions (500 caractères au plus), correction du contenu sans motif obligatoire.

Commandes : colonne commande.etape_suivi (sql/maj_commandes_suivi.sql, rattrapage des commandes existantes sans mail) ; livraison_lib.php : étapes Confirmée, En préparation (4 h), Expédiée (J+1), Livrée (J+3), avancerSuiviCommandes() appelée à l’ouverture des pages (toutes les 5 minutes par session) et de « Mes commandes », avancée conditionnelle sans doublon, notification et mail par étape (aucun mail pour une commande de plus de 4 jours). Annulation par le client pendant 4 heures (POST + CSRF, propriété vérifiée, annulerCommande() : stock, points, mail). Frise de suivi, commandes de plus de 4 livres repliées après 3.

Recherche : recherche_lib.php compare aussi titre et auteur sans tirets, espaces, apostrophes, points ni deux-points (colonneCompacteSql(), motifCompactRecherche()), dans catalogue.php (pertinence comprise) et recherche.php.

Recette : PHP 8.4/MariaDB 11.8 isolés (67 contrôles ; non-régression lots 1 à 7 avec deux contrôles adaptés aux nouvelles règles), navigateur à 375/768/1440 en visiteur, client, administrateur, gestionnaire et modérateur ; installation neuve (71 tables) ; PHP 8.2/MariaDB 10.4 locaux.

### Lot 9 : droits de l’équipe par pôle — 14 novembre 2026

Grille par défaut (droits_equipe_lib.php, réglable par l’administrateur dans « Droits de l’équipe ») : pôle objet et logistique pour le gestionnaire (catalogue, stock, fiches proposées, commandes, retours, emprunts, événements, gestion complète des dons, ventes entre lecteurs) ; pôle communauté et conduite pour le modérateur (avis, annonces entre particuliers, conversations, suspensions, ventes entre lecteurs, consultation des dons). Nouvelles familles dons, dons_lecture et ventes ; la gestion des dons inclut leur consultation. Un réglage déjà enregistré est complété par les valeurs par défaut des nouvelles familles ; sql/maj_droits_equipe_poles.sql y applique la nouvelle répartition (rejouable, version et historique conservés).

Contrôles serveur : pages en 403 hors droits ; dons en lecture seule pour le modérateur (formulaires masqués, POST refusés, contrôle aussi dans moderation_dons_lib.php) avec « Demander l’avis de l’équipe » ; conversations lisibles par le pôle de l’objet (emprunt, vente, don), outils de modération réservés au droit modération. Historique des décisions : les dons restent visibles en consultation.

Service client : catégories reliées aux familles (commande, livraison, retour → catalogue ; don → dons ; événement → événements ; compte, avis, messages → modération ; problème sur le site et autre → toute l’équipe), deux nouvelles catégories « Avis et commentaires » et « Messages entre membres ». File « Mon pôle » par défaut (catégories du pôle et demandes confiées) ou « Toute l’équipe », compteur du menu limité au pôle. Hors de son pôle : lecture, notes du fil et « Confier à un collègue » (le collègue choisi devient responsable et peut répondre) ; réponse, prise en charge et résolution refusées (403, contrôle aussi dans service_client_lib.php). Tableaux de bord gestionnaire et modérateur recalculés selon les droits.

Recette : PHP 8.4/MariaDB 11.8 isolés (61 contrôles : grille, matrice d’accès de 18 pages par rôle, dons, menus, tableaux de bord, service client, conversations, grille modifiée puis rétablie, ancienne configuration ; non-régression lots 1 à 8 avec attentes mises à jour pour les nouveaux droits), navigateur à 375/768/1440 en visiteur, client, administrateur, gestionnaire et modérateur ; installation neuve (71 tables) ; PHP 8.2/MariaDB 10.4 locaux.

### Lot 10 : événements payants ou gratuits et nettoyage des données — 14 novembre 2026

Événements : colonne `evenement.prix` DECIMAL(6,2) NOT NULL DEFAULT 0.00 (`sql/maj_evenements_prix.sql`, reportée dans `sql/biblioccaz.sql`), qui remplace la décision du 4 novembre (tous gratuits). Saisie dans le back-office et dans la proposition d’un auteur (0 = gratuit, virgule ou point, deux décimales, 500 € au plus), conservée à la validation ; paiement sur place, sans encaissement en ligne. Badge « Gratuit » ou prix sur la liste, la fiche (« à régler sur place »), l’accueil et la liste du back-office ; filtre « Tous les tarifs / Gratuits / Payants » ; un changement de prix prévient les inscrits avec la date et le lieu. Sans la colonne, les pages restent utilisables (tout est gratuit, filtre payant vide, champ masqué).

Bandeau d’accueil : déjà tiré parmi les 200 derniers titres éligibles depuis la consolidation du 4 novembre (`livresBandeauAccueil()`), rien à changer.

Données : les 60 lignes signalées par `sql/verifier_encodage.php` étaient correctes (« Âme », « Âge », « Moyen Âge »). Le contrôle ne signale plus un « Â » suivi d’une lettre ni un « ® » seul, et détecte toujours les vraies séquences cassées (« Ã© », « Â » suivi d’un caractère U+00A0 à U+00BF, « â€ », caractères cp850). Série douteuse du lot C (Orange) : les trois romans de Yui Tokiumi, déjà séparés dans « Orange — Romans », passent de « Mangas » à « Romans ados & young adult » avec une présentation de roman ; tome 16 de Spy x Family : auteur ramené à Tatsuya Endō (traducteurs retirés). Corrections ciblées par ISBN, aucun ISBN créé ni modifié (`sql/maj_orange_romans_categorie.sql`, rejouable).

### Lot 11 : correctifs mobiles et parcours — 14 novembre 2026

Accueil : le carrousel « Derniers avis » affiche un avis à la fois (les autres sont masqués, plus de cadre vide ni de diapositive étirée), points masqués sans JavaScript ou avec moins de deux avis, glissement au doigt, animation respectant la réduction de mouvement ; bloc masqué s’il n’a aucun avis. Barre de raccourcis : fondu réduit à 18 px et à 40 % d’opacité au lieu de 56 px transparents. Fiche livre : tags alignés en haut (`align-items` / `align-self: flex-start`, hauteur automatique), le « +N » déplie les tags restants sur une ligne entière, plus de chevauchement. Sous-titre des événements mis à jour au lot 10.

Retour après connexion : `retour_connexion_lib.php` (`retourConnexionValide()` n’accepte qu’une page PHP existante à la racine du site, hors pages de connexion, actions et fichiers internes ; `lienConnexion()`, `memoriserRetourConnexion()`, `terminerConnexionAvecRetour()` appelée par `ouvrirSessionMembre()`). Les liens « Se connecter » de l’en-tête et des fiches portent `?retour=`. Un visiteur qui clique sur un cœur ou « Ajouter aux favoris » : requête POST + CSRF qui mémorise le livre en session (30 minutes), puis connexion, retour à la page et favori ajouté avec un message unique. Sans jeton valide, rien n’est mémorisé ; un retour externe ou interdit renvoie à l’accueil.

Événements passés : toujours exclus de la liste principale ; section repliée « Événements passés » en bas de page (six plus récents, mêmes filtres sauf le mois, lien vers l’onglet complet).

Alertes de série : tables `alerte_serie` et `alerte_serie_envoi` (`sql/maj_alertes_serie.sql`), `alertes_serie_lib.php`, actions `serie_suivre` / `serie_ne_plus_suivre` dans `compte_action.php`, encart sur la fiche d’un tome, liste dans « Mes alertes », type de notification `serie`. Déclenchement quand un tome passe de 0 à du stock (ajout ou +1 dans le back-office, annulation de commande, retour reçu : `notifierRetourEnStock()` reçoit la quantité ajoutée) ; message « Nouveau dans la série » si le tome n’a jamais été vendu ni signalé, sinon « de nouveau en stock » ; une alerte par lecteur et par tome toutes les 24 heures au plus, pas d’alerte aux comptes suspendus ni en double d’une alerte de stock du même livre. Sans les tables, la fiche et « Mes alertes » restent utilisables.

### Lot 12 : délai de déconnexion automatique — 15 novembre 2026

Déconnexion après **15 minutes** (900 secondes) sans action pour tous les comptes connectés : lecteurs et auteurs sur le site, administrateur, gestionnaires et modérateurs dans le back-office (sessions séparées, même contrôle `controlerSessionUtilisateur()`). La valeur unique est `SESSION_INACTIVITE` dans `sessions_lib.php`, reprise par l’horloge PHP de la session, la validité du registre des connexions (`session_utilisateur.derniere_activite`) et la page « En ligne » ; `session.gc_maxlifetime` est relevé à 900 secondes au moins pour que le serveur ne supprime pas une session plus tôt. Les appels automatiques (actualisation de la messagerie entre membres, messagerie de l’équipe, journal en direct) vérifient toujours la session mais ne la prolongent pas ; seuls l’envoi d’un message et la frappe dans une conversation comptent comme une activité réelle. « Rester connecté » (lecteurs, 30 jours) reste inchangé. Aucune migration et aucun script de déconnexion côté navigateur : l’expiration est constatée par le serveur à la requête suivante.

### Lot 13 : scanner d’ISBN, « Qui sommes-nous ? » et finitions — 15 novembre 2026

Scanner : `javascript/scanner_isbn.js` + `scanner_isbn.css`, chargés par l’en-tête du site (et par `admin/livre.php` via `$stylesPage` / `$scriptsPage`). Tout bouton `[data-scanner-isbn]` (masqué sans JavaScript) ouvre une fenêtre `<dialog>` : caméra arrière, `BarcodeDetector` natif (`ean_13`, `ean_8`) quand il existe, sinon décodeur EAN-13/EAN-8 intégré (lignes et colonnes de l’image, distances bord à bord, garde et zone blanche contrôlées, même code lu deux fois avant d’être accepté), et saisie manuelle de l’ISBN-10 ou 13 si la caméra est absente ou refusée. Seuls les ISBN valides sont utilisés : clé de contrôle vérifiée, préfixe 978/979 exigé (EAN-8 et codes de produits refusés avec un message), ISBN-10 converti en ISBN-13. Modes : `recherche` (en-tête : remplit la recherche et l’envoie vers le catalogue), `saisie` (don : remplit le champ du livre, `don_recherche.js` rattache la fiche dont l’ISBN correspond ou vide le titre avec un message si l’ISBN est absent), `catalogue` (annonce : cherche l’ISBN via `don_recherche_livre.php` et rattache la fiche exacte par son id, même si deux éditions portent le même titre), `champ` (proposition de livre et ajout au back-office : remplit le champ ISBN et signale une fiche existante ; le back-office interroge `admin/livre_isbn.php`, JSON en lecture seule, droit `catalogue`). Le décodeur intégré a été testé sur images synthétiques et sur une fausse caméra Chrome ; le scan d’un vrai livre au téléphone reste à confirmer.

Page « Qui sommes-nous ? » : `qui_sommes_nous.php`, servie à `/qui-sommes-nous` par le `.htaccess` (et `.htaccess.production`) ; `/qui_sommes_nous.php` et `/qui-sommes-nous/` redirigent en 301. Chiffres clés en direct : livres validés du catalogue, dons récupérés, membres lecteurs et auteurs non suspendus définitivement (tiret si une requête échoue). Lien dans la colonne « Communauté » du pied de page et sur la page 404.

Finitions : menu du compte « Mes ventes entre lecteurs » (au lieu de « Mes ventes et achats ») pour les lecteurs et auteurs, titre de `mes_transactions.php` aligné. `/index.php` redirige en 301 vers la racine (requêtes GET/HEAD, paramètres conservés, `THE_REQUEST` pour fonctionner aussi dans un sous-dossier) ; liens et redirections internes vers `./`. Référencement : `seo_lib.php` contenait du texte doublement encodé (description de l’accueil affichée « dâ€™occasion » en production, pages Nouveautés et Dernière chance jamais indexables) ; texte corrigé, canonique de l’accueil à la racine, page « Qui sommes-nous ? » indexable et ajoutée au sitemap. Aucune migration.

### Lot 14 : menu du compte à deux colonnes et purge des notifications — 15 novembre 2026

Menu du compte (`header.php`, `style.css`) : les liens sont décrits par `$colonnesMenuCompte` (titre de colonne, liens `[page, icône, libellé, compteur, petitÉcran]`) et le pied par `$serviceClientMenu`. Lecteur : « Mon espace & achats » (Mon profil, Mes points et badges, Suggestions pour vous, Mes achats, Mes retours, Mes adresses, Mes alertes) et « Communauté & activités » (Mes dons, Mes annonces, Mes ventes entre lecteurs, Mes emprunts, Mes événements, Mes collections, Mes avis). Auteur : « Mon espace créateur » (Mon profil, Espace créateur, Suggestions, Proposer un livre, Proposer un événement) et « Communauté & activités » (annonces, ventes entre lecteurs, emprunts, collections). Membres de l’équipe connectés côté site : une seule colonne (Mon profil, Back-office, Fiches à valider, Avis signalés). Pied sur toute la largeur, séparé par un filet : Service client à gauche (lecteurs et auteurs, avec le nombre de réponses non lues) et Déconnexion en rouge à droite (POST + CSRF inchangés). À partir de 992 px, panneau `.menu-entete--large` de `min(540px, 100vw - 1.5rem)`, aligné à droite, hauteur libre dans la limite de l’écran : tout le menu tient sans défilement (vérifié à 1440×900, 1280×720 et 1024×700). « Mes notifications » est retiré du menu (cloche dédiée) ; « Mes favoris » et « Mes messages » ne s’affichent dans le panneau que sous 576 px (`.menu-entete__petit-ecran`), là où leurs icônes d’en-tête sont masquées. Sous 992 px, le panneau latéral existant est conservé en une colonne avec les titres de groupe. Navigation clavier, voile et croix inchangés.

Purge des notifications (`notifications_membre_lib.php`) : `purgerNotificationsSiNecessaire()` est appelée par `header.php` ; au plus une fois par heure (`NOTIFICATIONS_PURGE_INTERVALLE = 3600`), contrôlée par la date du fichier `purge_notifications.txt` du dossier privé puis par un verrou non bloquant (`flock`) et l’horodatage écrit dedans, pour qu’une seule requête lance la purge. `purgerNotifications()` supprime les notifications lues de plus de 30 jours (`NOTIFICATIONS_LUES_CONSERVATION_JOURS`) puis toutes celles de plus de 90 jours (`NOTIFICATIONS_CONSERVATION_JOURS`), d’après `date_creation`. Chaque purge non vide est tracée dans le journal (type `NOTIFICATION`) ; une erreur SQL est journalisée sans bloquer la page. La suppression par membre lors d’une nouvelle notification suit aussi la règle des 30 jours. Mention sur `notifications.php` : « Les notifications lues sont automatiquement supprimées au bout de 30 jours, les autres au bout de 90 jours. » Aucune migration.

### Lot 15 : menu du compte affiné, défilement mobile et application web — 16 novembre 2026

Menu du compte (`header.php`) : « Mes points et badges » et « Suggestions pour vous » retirés des colonnes (les suggestions restent sur l’accueil, les points sur « Mon profil »). L’encart des points (solde, jauge, palier suivant) devient un seul lien `a.menu-entete__points` vers `points_badges.php`, avec l’indication « Points et badges › ». Lecteur : « Mon compte & ventes » (Mon profil, Mes achats, Mes retours, Mes annonces, Mes ventes entre lecteurs, Mes adresses, Mes alertes) et « Dons, emprunts & communauté » (Mes dons, Mes emprunts, Mes événements, Mes collections, Mes avis, puis Mes favoris et Mes messages sous 576 px). Auteur : « Suggestions pour vous » retiré aussi. Pied inchangé : Service client à gauche, Déconnexion en rouge à droite.

Correctif mobile : sous 992 px, la ligne de la croix (`.menu-entete__fermer`) était collante (`position: sticky`) avec un fond blanc pleine largeur d’environ 52 px, et le panneau gardait 8 px de marge haute : en faisant défiler, une bande blanche restait figée en haut et le contenu passait dans la marge au-dessus. La ligne est désormais sans hauteur ni fond (`height: 0`, `pointer-events: none`), seule la croix ronde reste flottante (ombre légère), la marge haute du panneau est supprimée et l’en-tête du profil réserve la place de la croix.

Application web : `manifest.json` (`name` / `short_name` « BibliOccaz », `display: standalone`, `start_url` et `scope` à `./` — la racine `/` en production, le sous-dossier en local —, `theme_color` #274e13, `background_color` #f5f8f3, icônes 192 et 512 « any » et « maskable », icône 180). Icônes PNG opaques (RVB, sans canal alpha) générées depuis `ressources/application/icone-application.svg` : livre ouvert crème sur vert forêt, dans la zone sûre des icônes masquables ; `apple-touch-icon.png` à la racine, aussi recherché automatiquement par iOS. Balises dans `header.php` : `link rel="manifest"`, `link rel="apple-touch-icon"`, `theme-color`, `application-name`, `mobile-web-app-capable`, `apple-mobile-web-app-capable`, `apple-mobile-web-app-status-bar-style` (`default`), `apple-mobile-web-app-title`. `javascript/application_web.js` n’agit qu’en mode autonome (`navigator.standalone` ou `display-mode: standalone`) : clic gauche sans touche de modification sur un lien de même origine et dans la portée du manifeste → navigation dans la même fenêtre ; liens externes, téléchargements, ancres de la page, déclencheurs Bootstrap et clics déjà interceptés par un autre script laissés tels quels. Le back-office n’a pas ces balises. Aucune migration, `.htaccess` inchangé (`.json` et `.png` déjà servis).

### Lot 16 : plusieurs appareils, finitions iOS, adresses sans .php et ergonomie mobile — 16 novembre 2026

Plusieurs appareils (`sessions_lib.php`, `connexion_persistante_lib.php`, `profil.php`) : diagnostic sur copie isolée et journal de production, la connexion d’un second appareil ne fermait pas le premier ; les déconnexions perçues venaient de l’inactivité de 15 minutes et, sur iPhone, de l’application ajoutée à l’écran d’accueil, qui a ses propres cookies, séparés de Safari. Limite explicite de 5 sessions valides par membre et par espace (`SESSIONS_SIMULTANEES_MAX`) : à la 6e connexion, dans la même transaction, la plus ancienne (dernière activité) est révoquée par le membre lui-même (message « session fermée » sur l’appareil concerné), son jeton « Rester connecté » supprimé et l’opération tracée (SECURITE). Chaque session garde son expiration individuelle de 15 minutes. Correctif : une session fermée depuis le back-office pouvait être rouverte par son jeton « Rester connecté » ; la colonne `session_utilisateur.id_connexion_persistante` relie désormais la session au jeton qui l’a créée ou restaurée, et toute fermeture (profil, back-office, limite) supprime ce jeton. Rubrique « Mes appareils connectés » (`#appareils`) sur « Mon compte » : appareil (navigateur · système), date de connexion, dernière activité, appareil courant mis en avant, « Déconnecter » par appareil et « Déconnecter les autres appareils » (confirmation, POST + CSRF, contrôle de propriété côté serveur). Migration rejouable : `sql/maj_sessions_multi_appareils.sql` (reportée dans `sql/biblioccaz.sql`).

Application web iOS : `viewport-fit=cover` ; en mode autonome (`display-mode: standalone` ou `navigator.standalone`), le corps prend la hauteur de l’écran avec `env(safe-area-inset-top/bottom)` en marge, l’en-tête reste en place et le contenu défile dans `#zone-defilement` (ouverte par `header.php`, fermée par `footer.php`), si bien que l’indicateur de défilement ne passe plus sur l’en-tête ni sous la barre d’accueil. Back-office installable séparément : `admin/manifest.json` (« BibliOccaz Back-office », « BO BibliOccaz », portée `admin/`, #142310), icônes `admin/apple-touch-icon.png`, `icon-192.png`, `icon-512.png` opaques générées depuis `ressources/application/icone-back-office.svg`, balises dans `admin/_application_web.php` (inclus par `_haut.php` et `login.php`).

Ergonomie mobile : sous 992 px, la cloche ouvre un tiroir compact (`javascript/tiroir_notifications.js`) au lieu de la page : voile, croix, Échap, focus gardé dans le tiroir et rendu à la cloche, défilement interne, « Voir toutes mes notifications » collé en bas ; au-delà de 992 px, menu déroulant inchangé. « Notre histoire » (`qui_sommes_nous.php`, `javascript/qui_sommes_nous.js`) : sous 768 px, première phrase puis « Lire la suite » / « Réduire » (`aria-expanded`), texte complet sans JavaScript. Back-office : sous 992 px, la zone de liens du menu latéral prend la hauteur restante sous l’en-tête du panneau et défile jusqu’au dernier lien (avant : 44 px hors de l’écran à 375×667). « Voir le site client » devient « Aperçu du site » : `admin/apercu.php` pose un cookie `BIBLIOCCAZ_APERCU` signé (HMAC, clé générée dans le dossier privé, une heure) et ouvre le site dans un nouvel onglet ; `apercu_lib.php` revérifie signature, expiration et compte actif de l’équipe à chaque page ; bandeau discret « Mode aperçu back-office » (retour au back-office, « Quitter l’aperçu » via `apercu_quitter.php`) ; les pages vues en aperçu ne sont pas journalisées comme visites.

Adresses sans `.php` : `.htaccess` et `.htaccess.production` (lignes HTTPS, 404, protection de `secrets.php` et des fichiers sensibles conservées) servent `/page` depuis `page.php` quand le fichier existe, avec des alias : `/connexion` (login), `/mon-compte` (profil), `/livre` (detail_livre), `/qui-sommes-nous`, `/nouveautes`, `/derniere-chance`. Les requêtes GET/HEAD directes vers `page.php`, `/login`, `/profil`, `/detail_livre`, `/index` sont redirigées en 301 vers l’adresse propre, paramètres conservés ; jamais les POST, ni les appels portant `X-Requested-With: XMLHttpRequest`, ni `connexion_google.php` / `liaison_google.php` (adresse de retour déclarée chez Google). Fichiers interdits (`config`, `*_lib`, `partie_*`…) : 403 en `.php`, 404 sans extension. `urls_lib.php` (`urlPropre()`, `pageDepuisChemin()`) convertit les adresses : liens et formulaires des pages, redirections, mails, notifications (les anciens liens enregistrés en `.php` sont convertis à l’affichage), retour après connexion (`connexion?retour=livre%3Fid%3D12`), canoniques, plan du site et `robots.txt` (`/sitemap`). Le back-office garde ses adresses en `.php`. Recette : routeur du serveur PHP intégré aligné sur ces règles.

### Lot 17 : session unique, aperçu isolé, back-office sans .php et tableaux de bord — 16 novembre 2026

Session unique (`sessions_lib.php`, `config.php`) : un seul contexte authentifié par compte pour les lecteurs, les auteurs et l’équipe. Après `session_regenerate_id(true)`, l’enregistrement de la nouvelle session révoque les autres dans la même transaction, sous verrou du compte : en cas de connexions simultanées, seule la dernière validée reste ouverte. La session de l’autre espace (site ou back-office) du même navigateur est conservée. L’ancienne session est refusée dès sa requête suivante : redirection vers la connexion pour une page, code 401 et JSON structuré pour un appel AJAX, jamais de HTML inattendu. Trois motifs distincts : expiration, déconnexion, nouvelle connexion (« Vous avez été déconnecté car une nouvelle connexion a été ouverte sur un autre appareil »). Migration `sql/maj_session_unique.sql`.

Expiration : au-delà de 900 secondes d’inactivité, vérifiées côté serveur avant toute mise à jour de l’activité ; une requête tardive ne réactive jamais la session. Les scripts d’actualisation automatique (`SCRIPTS_SANS_ACTIVITE`) ne prolongent ni la session PHP ni le registre.

« Mémoriser mon identifiant » (`identifiant_memorise_lib.php`, `login.php`, `traitement_login.php`) remplace « Rester connecté ». La reconnexion automatique de 30 jours, sa table et son code sont supprimés ; un ancien cookie est effacé à la première visite. Le cookie `BIBLIOCCAZ_IDENTIFIANT` ne contient que l’identifiant (90 jours, HttpOnly, Secure en HTTPS, SameSite=Lax, limité à la page de connexion, valeur échappée à l’affichage, supprimé si la case est décochée). La rubrique « Mes appareils connectés » du profil est retirée.

Aperçu isolé en lecture seule (`apercu_lib.php`, `admin/apercu.php`) : barre de contrôle Vue Invité / Vue Client, Desktop / Mobile (cadre de 390 px, pas une émulation du matériel) et sortie. Session distincte `BIBLIOCCAZ_VUE`, lecteur fictif sans mot de passe utilisable (`sql/maj_apercu_client.sql`), aucune connexion client réelle. Le serveur refuse POST, PUT, PATCH et DELETE (403, HTML ou JSON selon l’appel), place la base en lecture seule pour neutraliser les écritures faites en GET, ne journalise pas les visites et ne prolonge pas la session de l’équipe. Le cadre est isolé (`sandbox` sans navigation de la page parente).

Adresses du back-office sans `.php` : règles ajoutées aux deux `.htaccess` (lignes de production conservées), liens et redirections de `admin/` convertis. Les GET en `.php` répondent 301 avec leurs paramètres ; les POST et les appels AJAX ne sont jamais redirigés ; `admin/index` renvoie à `admin/`. Correction d’un défaut du lot 16 : à l’expiration, le back-office renvoyait vers `admin/connexion`, qui n’existe pas.

Tableaux de bord par rôle (`admin/_tableau_priorites.php`, `admin/tableau_donnees.php`, `admin/tableau_bord.js`) : bloc « Priorités » en tête de `admin/`, cartes en grille, accès rapides, états vides. Gestionnaire : dons à valider, retraits en retard, commandes validées depuis 48 heures (le site n’a pas d’étape d’expédition), retours, tickets de son pôle. Modérateur : avis signalés, spoilers à relire, annonces en attente, litiges entre lecteurs, conversations actives depuis 24 heures, tickets de son pôle. Administrateur : synthèse des pôles, tickets, alertes système, sessions actives, raccourcis. Les indicateurs reprennent les définitions déjà utilisées par les files et le menu ; ils sont filtrés côté serveur par rôle et par droit ; une donnée illisible vaut « — », jamais 0. Actualisation toutes les 60 secondes par une seule boucle, uniquement quand l’onglet est visible, avec annulation de la requête en cours, recul en cas d’erreur réseau (2, 4 puis 5 minutes) et arrêt sur 401 ou 403.

Mobile : la lenteur perçue du menu du compte venait d’animations décoratives enchaînées (jauge de niveau différée de 200 ms puis animée 800 ms, lignes en cascade) et, sur écran tactile, d’effets de survol qui restaient collés ; aucune attente n’était due au JavaScript. Les panneaux Notifs et Profil s’animent désormais en 200 ms (`transform` et `opacity`, courbe `cubic-bezier(0.22, 1, 0.36, 1)`), sans cascade ; un calcul de mise en page inutile à l’appui est supprimé sous 992 px ; `touch-action: manipulation` sans désactiver le zoom ; retour d’appui `scale(.97)` ; survols réservés aux pointeurs qui survolent ; `prefers-reduced-motion` conservé. Icônes du back-office régénérées (fond uni opaque, marge d’au moins 22 % par côté, centrage corrigé du poids de l’engrenage).

Mesures (Chrome piloté, 375 × 812, CPU ralenti 4 fois, médiane de 7 ouvertures, copie isolée locale) : contenu du menu Profil stable en 286 ms contre 1 037 ms avant ; Notifs 283 ms, inchangé ; premier rendu des deux panneaux en 35 ms environ ; aucun décalage de mise en page mesuré sur l’accueil pendant ce test. Ces chiffres ne valent pas mesure sur un iPhone ou un iPad réel.

Recette sur copie isolée (PHP 8.4.27, MariaDB 11.8.9) : 1 006 contrôles HTTP des lots 1 à 17 et 261 contrôles Playwright à 375, 768 et 1 440 px (visiteur, lecteur, auteur, administrateur, gestionnaire, modérateur). Les recettes des lots 1 et 16 ont été adaptées : les contrôles de « Rester connecté » et des cinq appareils sont remplacés par ceux de la session unique.
