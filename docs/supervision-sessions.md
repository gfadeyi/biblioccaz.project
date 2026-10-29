# Tableau de bord des connexions

La page « Connexions actives » est réservée aux administrateurs. Elle réutilise le registre F6 installé par `maj_sessions.sql` et les sessions PHP existantes. La migration complémentaire `maj_sessions_supervision.sql` ajoute uniquement le rôle enregistré à l'ouverture ; aucune autre infrastructure d'authentification n'est créée.

## Synthèse et recherche

Trois indicateurs globaux : sessions valides, utilisateurs distincts et utilisateurs disposant de plusieurs sessions. Ils sont calculés sur tout le registre, indépendamment des filtres et de la pagination. Un test avec 501 connexions vérifie l'absence de troncature héritée de l'ancien affichage.

La validité exige une dernière requête connue depuis moins de dix minutes, aucune révocation, un mot de passe et un rôle correspondant à l'ouverture, un email vérifié et un compte dans un état autorisé. Les comptes suspendus ou en attente sont exclus, ainsi que les sessions dont le rôle a changé ou n'est pas connu. Ce décompte ne prétend pas prouver une présence humaine en temps réel. Les dates affichées distinguent ouverture et dernière requête connue.

Le tableau est un instantané SQL cohérent, horodaté, actualisé à la demande. Le rendu serveur permet recherche, pagination et confirmations sans JavaScript. L'ancien endpoint F6 reste compatible ; il n'est plus utilisé pour compter les indicateurs du tableau.

La recherche couvre pseudo, nom, prénom et email avec la collation insensible à la casse du projet ; valeurs bornées, requêtes préparées et caractères LIKE échappés. Les rôles et le filtre multi-session sont combinables. La liste est paginée par vingt utilisateurs ; les sessions sont regroupées dans leurs détails, avec repérage de la connexion courante. Les emails affichés sont réservés à l'administrateur ; aucun cookie, identifiant PHP, empreinte, IP ou agent utilisateur n'est rendu.

## Fermetures

Les actions individuelles F6 sont conservées : fermeture d'une session ou des sessions valides d'un membre, droits relus sous verrou, POST/CSRF/confirmation. Les sessions des autres administrateurs sont protégées côté serveur, même avec des identifiants de cible forgés. Le bilan compte les sessions valides effectivement fermées.

L'action globale est séparée et repliée sous « Préparer la fermeture globale ». Son aperçu donne les comptes et sessions clients/auteurs concernés, hors filtres. Modérateurs, gestionnaires et administrateurs sont toujours exclus de cette action. Les actions individuelles restent soumises à leur politique F6 existante.

Lors de la confirmation, le serveur verrouille les comptes concernés dans un ordre déterministe, relit leurs rôles/statuts et verrouille les sessions valides. Le prédicat d'écriture vérifie à nouveau le rôle client/auteur. L'enregistrement d'une session et les changements de rôle respectent ces verrous de comptes. Si les décomptes diffèrent de l'aperçu, l'action est annulée et un nouvel aperçu doit être confirmé. Deux confirmations concurrentes ne ferment pas deux fois les mêmes sessions.

Les comptes, rôles, mots de passe, points et autres données métier ne sont pas modifiés. Le POST global n'actualise pas non plus l'activité du compte administrateur ; sa session reste authentifiée, sous réserve du délai d'inactivité normal. Le GET de retour constitue une activité ordinaire, comme les autres pages du back-office. Le journal de sécurité conserve le nombre réellement fermé. La fermeture s'applique à la prochaine requête authentifiée et n'annule pas une requête déjà autorisée en cours d'exécution.

## Messages à la reconnexion

Le serveur distingue expiration naturelle, révocation documentée par un acteur, modification du mot de passe et suspension. La cause est transmise dans une nouvelle session anonyme, consommée une fois, sans faire confiance à un paramètre URL pour inventer une raison. Si elle ne peut pas être établie, le message reste générique. La fermeture globale produit le message de fermeture, pas celui d'expiration naturelle. Les déconnexions volontaires POST/CSRF existantes conservent leur comportement.

## Vérifications et limites

Recette isolée : 66 assertions de supervision, 80 F6/non-régression ciblée et 53 authentification/récupération. Tests incluant recherche, rôles, décomptes, comptes exclus, pagination, CSRF, requêtes forgées, bilans, préservation des données, motifs et concurrence de rôles/ouvertures/fermetures. Les processus concurrents utilisent des connexions SQL indépendantes et attendent la libération des verrous avant de terminer.

La migration est rejouable et synchronisée avec l'installation complète. Initialement testée sur la base fictive, elle a ensuite été appliquée après autorisation selon la section d'activation ci-dessous. Elle conserve les anciennes colonnes et ajoute `role_connexion` nullable, sans inventer le rôle historique des sessions existantes. Ces dernières doivent se reconnecter après activation ; aucune reconstitution à partir du rôle actuel n'est effectuée. Sans colonne, la connexion existante reste compatible, mais le tableau et son endpoint annoncent le prérequis au lieu d'afficher une synthèse incomplète. Une autre installation doit toujours prévoir sauvegarde, restauration vérifiée et autorisation avant import.

Aucune écriture principale effectuée pour cette recette. Données fictives et Mailpit uniquement. La syntaxe PHP et le diff sont contrôlés. L'accès navigateur reste indisponible : le rendu, les contrastes mesurés et l'expérience tactile réelle ne sont pas déclarés validés.

Checklist visuelle sur copie fictive à 375, 768 et 1440 px :

1. Trois cartes lisibles, recherche et filtres sans débordement, emails longs correctement coupés.
2. Navigation Tab/Entrée/Espace dans les détails, focus visible et labels des confirmations.
3. Recherche vide, aucun résultat, page suivante et réinitialisation des filtres.
4. Détails multi-session, distinction site/back-office et connexion courante.
5. Aperçu global indépendant des filtres, confirmation explicite et bilan ; aucune action pour un autre administrateur.
6. Message de fermeture sur le navigateur client, puis reconnexion possible ; message différent après expiration naturelle.

Les comptes à très grand nombre de sessions ont leurs détails chargés ensemble. Une pagination interne des sessions et une recette de charge à grande échelle restent des pistes si les volumes réels le justifient. Les indicateurs sont ceux du registre au moment de l'instantané ; ils ne constituent pas un contrôle du contenu des fichiers PHP de session sur disque.

## Polling de la messagerie

Le rafraîchissement automatique de `message_nouveaux.php` vérifie toujours la validité de la session, les droits, la suspension, le mot de passe, l'expiration et la révocation. Il n'actualise plus l'horloge PHP, la dernière activité du registre F6 ni `user.last_activity`. Recevoir une réponse de polling ne constitue donc pas une nouvelle activité d'usage. Une session valide ne prouve pas une présence humaine instantanée.

La navigation et l'envoi volontaire d'un message continuent à actualiser l'activité. L'endpoint automatique `logs_donnees.php` est traité comme une requête d'observation (l'ancien `en_ligne_donnees.php`, inutilisé, a été supprimé). Les requêtes de recherche déclenchées par une saisie ne sont pas exclues.

La recette HTTP isolée du correctif comporte 21 assertions : polls répétés, nouveaux messages, navigation, envoi, CSRF, accès tiers et refus après expiration, révocation, suspension, changement de rôle ou de mot de passe. Les campagnes F6 (80), messages de fin de session (42) et authentification (53) passent également. Aucun test n'écrit sur la base principale et aucune migration n'est nécessaire.

Le comportement visuel et les redirections Fetch restent à vérifier dans un navigateur : laisser une conversation ouverte sans interaction au-delà de dix minutes, vérifier l'expiration, puis refaire avec une fermeture distante ; vérifier aussi la réception avant expiration et l'ouverture du bandeau des nouveaux messages. Ne pas assimiler ces contrôles manuels à la recette HTTP automatisée.

### Recette complémentaire des réponses au polling

Un défaut a été reproduit après livraison C : Fetch suivait la redirection HTTP vers la connexion et consommait le message serveur en arrière-plan. Le polling refuse désormais une session invalidée avec HTTP 401 et JSON `authentification_requise`, sans redirection HTTP ; le JavaScript ouvre ensuite `login.php`. Un polling anonyme reçoit HTTP 403 avec le même indicateur. Le motif reste uniquement dans la session serveur, sans paramètre GET, et est affiché une seule fois lors de la navigation vers la connexion. Une conversation invalide ne déclenche pas à elle seule une demande de reconnexion.

La recette complémentaire exécute 322 assertions : 51 messagerie et réponses d'invalidation, 21 polling, 66 supervision, 80 F6, 42 messages de fin de session, 53 authentification et 9 JavaScript avec DOM simulé. Les scénarios HTTP couvrent notamment signalement, masquage/réaffichage, clôture, suspension, réponse de l'équipe à un emprunt, refus d'un tiers, CSRF, quota de messages et intervalle de mail Mailpit. Les tests n'attestent pas une résistance exhaustive aux courses d'envoi ni tous les cycles métier des transactions. Les huit assertions historiques de diagnostic restent conservées séparément.

L'accès au navigateur reste indisponible. Le suivi des réponses et cookies est testé par un client HTTP et la navigation JavaScript par simulation ; les onglets, le rendu, le clavier réel et la navigation Fetch intégrée au navigateur restent à recetter. Aucun import ni écriture sur la base principale n'est effectué pour cette recette.

## Activation SQL du 28 octobre 2026

Après autorisation explicite, sauvegarde complète fraîche et restauration isolée vérifiée, `maj_sessions_supervision.sql` a été appliquée sur `biblioccaz`, code de sortie 0. Les 36 tables préexistantes conservent leurs nombres de lignes et checksums. Le registre conserve ses trois lignes et toutes leurs anciennes valeurs ; seul `role_connexion VARCHAR(20) NULL` est ajouté après `espace`, avec collation utf8mb4. Clés, contraintes et index sont inchangés. Les trois rôles historiques restent NULL : les anciennes sessions doivent se reconnecter pour enregistrer leur rôle. Contrôle global d'encodage réussi. Aucun parcours HTTP ni écriture métier supplémentaire exécuté sur la base principale. Recette visuelle et livraison Git restent distinctes et en attente.

## Validation après migration

Les messages suivent désormais les libellés validés : « Votre session a expiré. Veuillez vous reconnecter. », « Votre session a été fermée. Veuillez vous reconnecter. » et, sans cause prouvée, « Votre session n'est plus valide. Veuillez vous reconnecter. ». Mot de passe et suspension gardent leurs explications spécifiques. La redirection ne contient plus de cause GET ; même l'ancien `?session=expiree` seul ne déclenche aucun motif affiché.

42 assertions supplémentaires vérifient ces messages sur le site et le back-office : passage par une vraie redirection HTTP, message serveur à usage unique, fermeture prioritaire sur une inactivité simultanée, paramètres forgés ignorés et déconnexion volontaire sans fausse alerte. Les 66 assertions de supervision, 80 F6 et 53 authentification sont de nouveau réussies. Les 69 assertions récompenses confirment les parcours utilisant le contrôle commun. Aucun nouveau test ne modifie la base principale ; la migration n'est pas réappliquée. Le rendu graphique reste à observer, le navigateur demeurant indisponible.

## Lisibilité de la page « Connexions actives »

La page regroupe les connexions par membre, triées par activité la plus récente. Chaque connexion affiche l'espace (site ou back-office), un appareil en libellé court (« Chrome · Windows »), la date de connexion et la dernière activité sous la forme « il y a 5 min ». Le libellé est déduit de l'en-tête du navigateur au moment de la connexion ; le texte complet n'est jamais conservé. Les connexions ouvertes avant `sql/maj_sessions_appareil.sql`, ou dont le navigateur n'est pas reconnu, affichent « Appareil inconnu ». Sans la migration, la page et la connexion restent utilisables.

Les fermetures (une connexion, toutes celles d'un membre, fermeture globale dans la « Zone sensible ») passent par la fenêtre de confirmation du back-office. Sans JavaScript, une case de confirmation obligatoire la remplace. Le fonctionnement et les protections ne changent pas : POST, jeton CSRF, confirmation, administrateurs tiers protégés.
