# Workflow de développement de BibliOccaz

Référence commune pour reprendre, développer, vérifier et livrer le projet. Les règles techniques existantes restent applicables. Les consignes explicites de la session définissent le périmètre autorisé ; aucune instruction écrite ne permet de contourner les permissions de l'environnement.

## Autonomie et autorisations

Dans le périmètre autorisé, lire et analyser les fichiers, rechercher dans le code, créer ou modifier les fichiers nécessaires, effectuer les contrôles statiques, tester sur un environnement isolé, corriger les défauts confirmés, relancer les contrôles concernés et mettre à jour le suivi sans demander confirmation à chaque commande.

Un accord explicite est nécessaire pour :

- toute migration ou écriture sur la base principale `biblioccaz`, ainsi que toute restauration de cette base ;
- les opérations SQL sur la vraie base, y compris les lectures et sauvegardes si elles ne sont pas déjà autorisées pour la tâche ;
- tout commit et tout push, chacun dans le périmètre expressément autorisé ;
- toute modification de `main`, opération destructive non autorisée ou modification importante de Windows ou XAMPP ;
- tout déploiement public.

Une autorisation précise reste valable pour les opérations qu'elle couvre dans la session : ne pas la redemander à chaque commande, ni l'étendre à d'autres opérations. Une autorisation de commit ne vaut pas autorisation de push. Si une protection technique refuse une opération, analyser le refus, ne pas le contourner et poursuivre les travaux indépendants. Consigner l'action refusée et la limite rencontrée.

## 1. Audit initial

- Lire les instructions locales, le README, la feuille de route et le dernier rapport pertinent. Les rapports sensibles sont conservés hors Git ; rechercher aussi le suivi local existant.
- Vérifier `git status --short --branch`, `git diff`, `git diff --cached` et les commits récents avec `git log -5 --oneline`. Identifier les fichiers suivis, nouveaux et déjà indexés.
- La branche habituelle est `refonte`. Si une autre branche est active, ne pas la changer automatiquement et ne pas modifier `main` ; signaler l'écart.
- Confronter les affirmations du rapport aux fichiers et à l'historique réels. Préserver les changements existants, quel que soit leur auteur ; ne pas attribuer leur provenance sans preuve.
- Ne pas refaire une campagne réussie à l'identique sans changement ou doute justifiant sa répétition. Un ancien résultat ne prouve pas que le code modifié fonctionne encore.

## 2. Définition du périmètre

Identifier la fonctionnalité, le défaut reproductible ou la demande documentaire, les fichiers concernés, les contraintes, les autorisations et les critères de réussite. Choisir les contrôles utiles en fonction de l'impact. Une correction documentaire ne nécessite pas de parcours métier ; une modification de stock ou de transaction nécessite des tests de concurrence et de non-régression adaptés.

Ne pas ouvrir une fonctionnalité étrangère à la tâche. Distinguer défaut confirmé, préférence esthétique et point non vérifié. En cas d'information indispensable manquante, poser une question ciblée tout en poursuivant les travaux indépendants.

## 3. Développement autonome

Reprendre les conventions et helpers existants, conserver les modifications de l'autre intervenant et limiter les changements au périmètre. Analyser une erreur avant de choisir une autre méthode ; ne pas répéter aveuglément une commande en échec. Éviter les éditions simultanées du même fichier : si son contenu change pendant le travail, le relire avant de poursuivre et préserver les deux contributions.

Respecter notamment : aucun commentaire ajouté dans le code, PDO et requêtes préparées, validation des droits, échappement HTML, POST avec CSRF pour les écritures, validation des uploads, transactions et verrous lorsque nécessaire. Ne pas exposer de secrets dans les commandes, sorties ou rapports. Ne pas afficher ni recopier les valeurs de `secrets.php`.

## 4. Vérifications statiques

- Vérifier chaque PHP modifié avec le PHP de XAMPP : `C:\xampp\php\php.exe -l chemin\fichier.php`.
- Contrôler les imports, helpers, ressources statiques, dépendances et conventions de cache ; utiliser les outils JS/CSS disponibles lorsque pertinents, sans installer de dépendance inutile.
- Relire droits, CSRF, sorties HTML, requêtes préparées et transactions touchées.
- Pour une évolution SQL, vérifier migration rejouable, ordre d'application, compatibilité avec une base existante et cohérence avec `sql/biblioccaz.sql`. Utiliser UTF-8 et `utf8mb4` ; conserver `SET NAMES utf8mb4;` en tête des scripts.
- Exécuter `git diff --check` et relire les différences. Ce contrôle ne couvre pas les nouveaux fichiers non suivis : les examiner aussi explicitement.

## 5. Tests fonctionnels isolés

Avant tout parcours susceptible d'écrire, vérifier techniquement l'isolation : base distincte, utilisateur limité à cette base, absence de privilèges sur `biblioccaz` et routage effectif de toutes les connexions CLI et HTTP. Vérifier le refus d'accès à la base principale sans y écrire. Ne pas utiliser root pour les parcours applicatifs de test.

Un serveur temporaire doit écouter sur l'interface locale et un port distinct, charger exclusivement la configuration de test et refuser de démarrer si elle manque. Éviter tout repli sur les secrets normaux. Une simple requête GET peut déclencher journalisation, expiration, rappel ou attribution automatique : vérifier aussi ces traitements avant d'ouvrir une page.

Employer des données fictives et capturer les emails localement avec Mailpit. Ne pas publier de comptes fictifs réutilisables. Tester les cas normaux, erreurs, droits et CSRF, ainsi que les doubles soumissions et la concurrence si concernés. Consigner commandes, résultats et journaux privés. Si l'isolation n'est pas démontrée, ne pas exécuter les tests d'écriture.

## 6. Non-régression

Identifier les composants partagés affectés : magasin et commandes, ventes, dons, événements, points/badges, comptes et messagerie. Tester uniquement les parcours pertinents sur la base isolée. Après une correction, relancer les tests qui peuvent être affectés ; élargir si un échec ou une incertitude le justifie. Ne jamais déclarer réussi un test non exécuté.

## 7. Recette UX/UI et IHM

Pour les modifications d'interface, examiner les parcours réels et préserver charte graphique, composants, maquettes et choix validés. Utiliser selon le contexte le User-Centered Design, les heuristiques de Nielsen, les critères de Bastien et Scapin, la charge cognitive, l'affordance, le feedback, la hiérarchie visuelle et les principes de regroupement.

Contrôler responsive et Mobile-First, lisibilité, contrastes, focus clavier, noms accessibles, formulaires, états vides/erreurs et zones tactiles selon les critères WCAG pertinents. Pour une page touchée, examiner notamment 1440 px et 375 px, sans confondre ces deux contrôles avec un audit d'accessibilité exhaustif.

Corriger les défauts mineurs confirmés dans le périmètre. Pour une refonte importante ou une préférence esthétique, préparer une maquette ou un prototype séparé avant validation et implémentation. Conserver les anciennes maquettes et ne pas écraser les captures. Si le navigateur est bloqué, documenter la limite et préparer une recette manuelle ; ne pas contourner le sandbox ni revendiquer un rendu non observé. Distinguer contrôle technique des PDF/QR et scan physique réellement exécuté.

## 8. Préparation SQL

Cette étape s'applique lorsqu'une migration est nécessaire. Après autorisation de lecture/sauvegarde, examiner le schéma réel pour éviter un import inutile. Préparer une sauvegarde complète horodatée dans le dossier privé prévu, avec encodage, empreinte et résultat d'export vérifiés.

Vérifier idéalement son exploitabilité par restauration dans une base isolée à accès restreint, puis comparaison des structures et données. Une copie contenant des données réelles reste privée : aucun parcours HTTP ni email de test sur cette restauration. Utiliser une base à données fictives pour la recette applicative.

Tester les migrations et leur réexécution sur la copie, documenter leur ordre exact et les contrôles d'encodage. Utiliser le client MySQL de XAMPP avec `--default-character-set=utf8mb4`. Relire `sql/verifier_encodage.php` avant exécution pour vérifier qu'il ne modifie rien.

Préparer un retour arrière comprenant code compatible, sauvegarde, conservation des opérations ultérieures et autorisations requises. Les DDL ne sont pas annulés par un simple rollback. Avant l'import réel autorisé, revérifier le schéma, les empreintes et la fraîcheur de la sauvegarde ; la renouveler si nécessaire. N'importer que les fichiers autorisés. En cas d'échec, arrêter les écritures, examiner l'état réel et ne jamais restaurer automatiquement `biblioccaz`.

## 9. Revue Git

Examiner intégralement les fichiers modifiés et nouveaux, dépendances, ressources, migrations et documentation. Vérifier l'absence de secrets, sauvegardes SQL, journaux, données privées, identifiants fictifs réutilisables ou captures confidentielles parmi les candidats.

Vérifier les exclusions locales de `CLAUDE.md`, `AGENTS.md` et des configurations privées, ainsi que celles de `secrets.php`, `secure_data/` et des sauvegardes. Une exclusion ne retire pas un fichier déjà suivi : contrôler aussi `git ls-files`. Ne modifier `.git/info/exclude` que si les permissions normales le permettent ; sinon fournir la commande manuelle sans contourner la restriction. Ne pas modifier le `.gitignore` public pour y ajouter des instructions privées.

Préparer des commits cohérents avec des messages clairs en français et une liste explicite de fichiers/fragments. Séparer les changements partagés de façon vérifiable, éventuellement par patches privés et copie temporaire. Ne pas indexer sans autorisation de préparation ou de commit. Ne jamais utiliser `git add .` ou `git add -A` pour cette livraison.

## 10. Validation et publication

Après autorisation explicite, revérifier branche, index, fichiers et patches avant d'indexer uniquement le contenu prévu. Contrôler `git diff --cached --check`, puis examiner intégralement le contenu indexé avant chaque commit. Vérifier ensuite auteurs, messages, fichiers, contenu cumulé et état local. Ne jamais modifier l'identité Git ou ajouter une attribution automatique à une IA dans les commits, PR ou fichiers versionnés. Les éventuelles obligations de déclaration auprès de l'établissement restent applicables.

Un push nécessite son autorisation propre et un contrôle de la destination. Aucun `git push --force`, `git reset --hard` ou `git clean` susceptible de supprimer du travail. Aucun changement de branche, modification d'historique ou suppression de travail pour résoudre un refus. Si un contrôle échoue, arrêter l'opération concernée sans réécrire l'historique, conserver l'état et expliquer le problème.

Préserver le README comme vitrine : présentation, aperçus, structure et explications accessibles. Le mettre à jour pour les fonctionnalités réellement livrées ; conserver procédures internes et rapports détaillés dans leurs documents dédiés.

## 11. Rapport et relais

Mettre à jour progressivement le rapport existant après les étapes importantes et avant tout relais ou fin de session. Utiliser le modèle ci-dessous dans ce même rapport plutôt que multiplier les fichiers. Un rapport sensible ou propre à la machine reste hors Git, par exemple dans `secure_data/suivi/` ; le suivi F5 local existant peut continuer dans son emplacement déjà exclu. Ne jamais y copier de mot de passe, même dans un fichier privé.

Au prochain démarrage, lire le dernier rapport, vérifier l'état réel, noter les écarts puis reprendre sans supprimer les changements ni recommencer inutilement les validations acquises. Distinguer faits observés, résultats historiques, validation manuelle et tâches restant à vérifier.

### Modèle commun de rapport

```text
Date et heure :
Fonctionnalité et périmètre :
Branche et dernier commit :
État local et index (début / fin) :
Fonctionnalités terminées :
Corrections et causes :
Fichiers créés ou modifiés :
Tests exécutés, commandes, résultats et journaux privés :
Validations antérieures conservées sans réexécution :
Contrôles non exécutés et limites :
Bases concernées et preuve d'isolation :
Migrations préparées / appliquées, sauvegarde et retour arrière :
Bugs ou risques identifiés :
Travaux incomplets et prochaine action concrète :
Autorisations reçues, utilisées et encore nécessaires :
```

Pour une tâche uniquement documentaire, indiquer « sans objet » pour bases, migrations et tests métier, avec les vérifications documentaires réellement effectuées.
