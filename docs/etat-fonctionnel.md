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
- Messagerie de l'équipe en direct : `javascript/messagerie_equipe.js` interroge `admin/messagerie_equipe_nouveaux.php` toutes les 5 secondes (30 secondes onglet masqué, ralentissement progressif en cas d'erreur). Les nouveaux messages s'ajoutent au fil (bouton « Nouveaux messages » si l'on a remonté la conversation), la liste des conversations, le badge du menu et le titre de l'onglet se mettent à jour. Ces appels ne prolongent pas la session (déconnexion après 10 minutes sans action conservée).
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