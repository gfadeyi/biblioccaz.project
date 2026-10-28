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
