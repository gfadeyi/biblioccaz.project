# F6 — Contrôle des sessions utilisateurs

## Audit initial du 28 octobre 2026

Reprise sur refonte, HEAD 5fc98c2, dépôt propre. Les sessions PHP et cookies distincts du site et du back-office existent déjà. La connexion régénère l'identifiant PHP ; l'inactivité est limitée à dix minutes dans config.php. Le second contrôle d'expiration est inopérant car l'activité est mise à jour avant sa comparaison, et le journal du premier mentionne à tort 300 secondes.

La supervision admin/en_ligne.php utilise user.last_activity sur cinq minutes : c'est une liste de membres récemment actifs, pas une liste de sessions. La déconnexion volontaire n'efface pas cet horodatage. Aucun registre individuel ni mécanisme de révocation n'a été trouvé. Les droits sont stockés en session, sans relecture systématique du rôle/statut du compte. La réinitialisation du mot de passe ferme la session courante mais pas les autres appareils. Les déconnexions actuelles sont accessibles par GET.

Conséquence de sécurité : une session déjà connectée peut conserver un rôle ou un accès après modification du compte, tant que les pages ne refont pas elles-mêmes ces vérifications. Une activité régulière repousse l'expiration. Il faut donc contrôler les droits actuels au point commun, pas uniquement au moment de la connexion. Les flags de cookies ne sont pas définis explicitement par config.php ; leurs valeurs effectives Apache restent à vérifier, sans assimiler celles du PHP CLI à celles du serveur web.

## Évolution ciblée à réaliser

Conserver PHP comme seul gestionnaire des données de session. Ajouter un registre de suivi SQL, sans cookie, jeton ou identifiant PHP en clair : empreinte SHA-256 liée au nom et à l'identifiant de session, empreinte du hash de mot de passe, utilisateur, espace site/admin, dates et révocation. Le numéro technique de la ligne de suivi n'est pas un secret d'authentification et ne permet pas de se connecter ; aucun identifiant PHP ne doit être montré dans l'interface.

Intégrer le contrôle au point commun config.php et l'enregistrement à la connexion réelle. Une ligne révoquée ou expirée ne doit jamais être recréée automatiquement par une requête authentifiée. Recharger rôle/statut et vérifier le mot de passe associé. Une ancienne session sans suivi devra se reconnecter après activation de la migration. La séparation site/back-office et l'inactivité de dix minutes restent inchangées.

Étendre la page En ligne existante, réservée au droit admin : liste par connexion, utilisateur, espace et activité ; actions POST + CSRF et confirmation. Pas de nouvelle infrastructure d'authentification ni de consultation des fichiers PHP de session des autres utilisateurs. Ne pas afficher les empreintes internes, cookies, IP ou agents utilisateurs.

Politique validée par le propriétaire : un administrateur peut fermer ses propres connexions et celles des comptes non administrateurs. Les connexions d'un autre administrateur sont protégées, même par requête HTTP forgée. Le serveur relit les rôles des deux comptes sous verrou avant de modifier le registre.

## Migration et protection

Migration préparée : sql/maj_sessions.sql, également reportée dans sql/biblioccaz.sql. La tester et la rejouer uniquement sur la base isolée. Aucun import principal autorisé. Sans migration, la page devra signaler que le contrôle individuel est indisponible et ne pas revendiquer de protection par révocation.

Structure également ajoutée à sql/biblioccaz.sql ; création et réexécution réussies sur la base isolée existante avec son compte restreint, accès à biblioccaz refusé avant test. Structure identique entre migration et installation. Aucune table principale modifiée. Le test de schéma est complété par la recette fonctionnelle décrite ci-dessous.

## Acceptation et recette prévue

- Admin autorisé, gestionnaire/modérateur/lecteur refusés ; rôle révoqué ou compte suspendu pris en compte.
- Deux connexions simultanées distinctes ; révocation individuelle sans effet sur l'autre, et révocation de toutes les connexions du compte selon la politique validée.
- Refus GET, CSRF absent/faux et confirmation absente, sans écriture ; aucun secret dans HTML/JSON.
- Révocation effective dès la prochaine requête sur une ressource protégée, y compris une page déjà ouverte soumettant un POST.
- Expiration après dix minutes ; déconnexion volontaire et cookies séparés ; connexion toujours possible après révocation de l'ancienne session.
- Changement/récupération de mot de passe : anciennes sessions invalidées ; comportement de la session courante vérifié.
- Migration absente et réexécution ; cohérence installation complète ; syntaxe, diff et non-régression authentification/F5 ciblée.

Les preuves F5 et propositions de statuts seront documentées séparément ; aucune modification du Google Docs sans accès effectif et autorisation.

## Implémentation et recette isolée

- `sessions_lib.php` et `config.php` : suivi des sessions PHP existantes, empreintes uniquement, contrôle commun avant accès protégé, délai de dix minutes unifié et contrôle du compte/mot de passe. Un changement de rôle impose une reconnexion. Une ligne révoquée ou expirée n'est jamais réenregistrée automatiquement.
- Connexions site et admin : enregistrement après authentification et renouvellement de l'identifiant PHP ; cookie HttpOnly, SameSite Lax et Secure lorsque la requête utilise HTTPS. Aucune configuration Windows/XAMPP modifiée.
- Modification du mot de passe côté site ou back-office : maintien de la connexion courante sous un nouvel identifiant, anciennes connexions refusées dès leur prochaine requête. Récupération : retour à la connexion et refus des anciennes sessions sur les autres appareils.
- Déconnexions site/admin : POST + CSRF, cookie effacé et suivi clos. Boutons et messages adaptés sans refonte graphique.
- Page En ligne conservée et étendue : connexions séparées, espace, dates, connexion courante, confirmation puis fermeture individuelle ou globale. Un numéro de ligne technique sert à l'action sans révéler le cookie, l'identifiant PHP, les empreintes, l'IP ou l'agent utilisateur. Le rafraîchissement automatique ne prolonge pas l'inactivité, ne remplace pas un formulaire en cours d'utilisation et annonce ses erreurs.

Résultats réellement exécutés : 80 assertions F6 et non-régression ciblée, puis 53 assertions du parcours de récupération sur le code intégré. Couverture : rôles, suspensions, requêtes forgées contre un autre admin, CSRF/confirmation/champs invalides, fermeture individuelle/globale/de soi, prise d'effet au prochain GET et POST métier, expiration PHP/SQL, connexions simultanées, mot de passe site/admin/reset, séparation des cookies, connexion HTTP admin, concurrence de révocation et registre absent. Une réinitialisation intercalée interdit de renouveler une session à partir de l'ancien hash. Non-régression : pages de compte/commandes/dons/événements/ventes/points/messagerie, panier vente et exclusion des prêts, réservation/retrait/renouvellement/ticket/retour F5. Les 141 assertions F5 historiques n'ont pas été intégralement répétées.

Les tests HTTP de connexion utilisent un état CAPTCHA prévalidé dans des sessions fictives : le puzzle visuel n'est pas recetté. Les tests de concurrence utilisent deux processus PHP et connexions SQL indépendants. Emails fictifs capturés localement par Mailpit ; fixtures nettoyées uniquement dans la base isolée. Syntaxe PHP, syntaxe JS et diff contrôlés ; aucun rendu visuel desktop/mobile observé pendant cette session. La table responsive, les noms accessibles, le feedback et les confirmations sont préparés, mais une recette navigateur reste nécessaire avant de valider le rendu.

## Activation et limites

Après sauvegarde vérifiée et autorisation explicite, appliquer uniquement `sql/maj_sessions.sql` à la base existante. L'installation neuve l'inclut déjà. La mise à jour du contrôle commun demandera une reconnexion aux sessions anciennes dépourvues de l'empreinte d'authentification. Après activation du registre, une connexion ouverte sans suivi doit également se reconnecter ; aucun import de cookies ou lecture des fichiers de sessions existants n'est réalisé.

Sans table, les nouvelles connexions restent possibles avec contrôle du compte/mot de passe, mais la révocation individuelle est indisponible et annoncée comme telle. Si une session a déjà été suivie et que le registre disparaît, elle est refusée : aucun repli ne doit ressusciter une connexion révoquée. Aucun DDL automatique dans le code applicatif.

La révocation vaut à la prochaine requête authentifiée ; elle ne peut pas annuler rétroactivement une requête déjà autorisée et en cours d'exécution. Les connexions expirées/révoquées restent dans le registre comme historique technique et sont exclues des actives ; une éventuelle politique de purge est différée. La liste est limitée à 500 connexions et ne constitue pas une promesse de présence humaine instantanée.

État de livraison actualisé : commit `dfe605f` publié sur `refonte`. Migration principale et recette visuelle restent en attente. La sauvegarde principale a été restaurée et la migration rejouée sur cette copie lors du prévol d'intégration ; aucune écriture principale effectuée. Voir [la préparation commune](integration-sessions-recompenses.md). Les rapports et journaux détaillés restent privés.

Activation du 28 octobre 2026 : après autorisation explicite et dernier prévol, `maj_sessions.sql` a été importée sur la base principale avec succès. Table et contraintes identiques à la copie testée ; les 33 tables préexistantes sont conservées en structure, nombre de lignes et checksum. Contrôle d'encodage réussi. Les anciennes sessions dépourvues de suivi doivent se reconnecter. La recette visuelle et les parcours réels nécessitant des écritures restent distincts ; aucune connexion HTTP n'a été lancée sur la base principale pendant l'import.

Évolution du tableau de bord : [synthèse, recherche et fermetures](supervision-sessions.md) implémentées en réutilisant F6. Le nouvel affichage serveur remplace le rafraîchissement automatique et la liste plafonnée : indicateurs globaux non tronqués, vingt utilisateurs par page et détails par membre. Fermeture globale limitée aux clients/auteurs sous verrous et nouvelle confirmation si l'aperçu a changé. 66 assertions dédiées, 80 F6 et 53 récupération réussies en base fictive. Migration complémentaire `maj_sessions_supervision.sql` préparée/testée pour mémoriser le rôle d'ouverture et exclure immédiatement les sessions aux rôles modifiés ; import principal soumis à autorisation, reconnexion des anciennes sessions nécessaire. Rendu navigateur toujours à recetter et livraison Git non effectuée.

Activation complémentaire autorisée du 28 octobre 2026 à 15 h 51 : migration de supervision importée avec succès après sauvegarde/restauration vérifiées. Les 36 tables et leurs données préexistantes sont conservées ; seule la colonne de rôle nullable est ajoutée. Les trois sessions anciennes devront se reconnecter. L'étape de migration est achevée, sans nouvelle recette visuelle ni opération Git.
