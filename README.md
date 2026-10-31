# BIBLIOccaz

Librairie en ligne de livres d'occasion, pensée comme une vraie boutique de quartier : on y achète des livres vérifiés à petit prix, on donne ceux qu'on a lus, on se retrouve à des dédicaces et des ateliers, et chaque action rapporte des points.

Site complet en PHP et MySQL, sans framework : catalogue rangé par univers, panier et commandes, dons avec QR code, événements avec billets, avis modérés, programme de fidélité, et un back-office séparé pour l'équipe.

![Page d'accueil](docs/apercu/accueil.png)

## Le projet

BIBLIOccaz est né comme **projet annuel de 1re année à l'ESGI** (classe 1A1), réalisé en groupe par **Gaël Fadeyi**, **Jeanny Benon** et **David Radom** : catalogue, panier, comptes, dons et premier back-office.

Je l'ai ensuite **repris et largement étendu** : refonte visuelle complète, univers et méga-menu, événements, programme de points et de badges, back-office repensé, sécurité (requêtes préparées, jetons CSRF, uploads vérifiés), accessibilité, données de démonstration et documentation.

## Aperçu

| Catalogue et filtres | Fiche d'un livre |
|---|---|
| ![Catalogue](docs/apercu/catalogue.png) | ![Fiche livre](docs/apercu/fiche-livre.png) |

| Points, niveaux et badges | Événements |
|---|---|
| ![Points et badges](docs/apercu/points-badges.png) | ![Événement](docs/apercu/evenement.png) |

| Back-office | Sur téléphone |
|---|---|
| ![Tableau de bord du back-office](docs/apercu/back-office.png) | ![Version mobile](docs/apercu/mobile.png) |

Les couvertures visibles sur ces captures sont générées pour l'aperçu : les couvertures d'éditeurs ne sont pas diffusées dans le dépôt.

## Fonctionnalités

### Boutique
- **Catalogue en 6 univers** (BD, Manga & Jeunesse, Littérature, Art & Culture, Scolaire & Études, Savoirs & Pro, Vie pratique) et un rayon Bons plans, avec méga-menu, fil d'Ariane, catégories, tags en trois familles (thèmes, public, particularités), filtres (type d'offre : magasin, entre particuliers ou don, prix, état, note), tris (dont la **pertinence**, par défaut lors d'une recherche) et pagination ; filtres repliés dans un volet sur téléphone
- **Fiche livre** : caractéristiques complètes, choix de l'état (neuf, très bon, bon, usé) avec le prix et le stock de chacun, liste « Toutes les offres » (magasin, annonces de particuliers et dons) triable par prix, état ou type, avis des lecteurs, bloc « Vous aimerez aussi » (même catégorie, même univers ou thèmes communs, chaque livre avec sa raison)
- **Panier et commande** : remises automatiques (-5 % dès 30 €, -10 % dès 50 €), livraison offerte dès 20 €, carnet d'adresses, bons de réduction, mail de confirmation ; prix et stocks toujours relus en base, commande verrouillée en transaction
- **Classements communautaires** : « Les plus empruntés » compte les prêts effectivement retirés (en cours ou rendus), « Les plus donnés » compte les dons récupérés ; titres sans activité exclus, compteurs affichés, filtres et tris conservés, offres actuellement disponibles uniquement.
- **Liste d'envies** et **alertes de retour en stock** par mail
- **Suggestions personnalisées** : page « Suggestions pour vous », carte dans le profil et carrousel « Pour vous » sur l'accueil, calculées sans apprentissage automatique d'après les préférences de lecture, les achats, emprunts, favoris, notes et fiches consultées, pondérés par la popularité et la note moyenne ; une phrase explique chaque choix (« Parce que vous aimez « Mangas » ») ; livres déjà achetés, empruntés, indisponibles ou du lecteur lui-même exclus ; meilleures ventes et mieux notés quand on ne connaît pas encore ses goûts
- **Recherche** par titre, auteur ou ISBN (avec ou sans tirets), suggestions temporisées et filtres combinables par niveau scolaire et matière ; champs scolaires renseignables depuis le back-office

### Communauté
- **Emprunts en magasin** : réservation gratuite de trois livres au maximum, retrait sous trois jours ouvrés avec code ou QR, liste d'attente avec attribution automatique, tickets PDF, renouvellements, rappels par mail, suivi des retours et blocages temporaires en cas de retard ; messagerie avec l'équipe, points pour les retours à l'heure et badge « Habitué de la bibliothèque »
- **Revente entre particuliers** : annonce avec prix libre (de 0,50 € à 150 €), état, jusqu'à 3 photos vérifiées, description et mode de remise (main propre ou envoi) ; un livre absent du catalogue crée une nouvelle fiche ; chaque annonce est **vérifiée par l'équipe avant publication** (validation, refus ou retrait avec motif envoyé par mail) ; page « Mes annonces » pour modifier, retirer, remettre en ligne ou supprimer
- **Ventes entre lecteurs** : demande d'achat depuis l'annonce (5 en attente au maximum, expiration après 7 jours sans réponse), acceptation ou refus par le vendeur, **file d'attente** quand le livre est réservé, échange organisé dans la messagerie, paiement direct entre membres, **le vendeur indique la vente conclue** (livre vendu, **reçu PDF** pour les deux), puis l'acheteur a 7 jours pour confirmer la réception ou signaler un litige à l'équipe, page « Mes ventes et achats » ; +10 points vendeur (5 ventes par mois) crédités à la confirmation, ou après 7 jours sans signalement, et badge « Seconde main »
- **Suivi des dons par l’équipe** : validation avant publication (un nouveau don reste privé jusqu’à son acceptation), demande de correction motivée, correction du contenu par l’équipe, fiche détaillée, historique des interventions, annulation motivée ou libération d’une réservation, notification des personnes concernées ; les dons récupérés et leurs points sont conservés.
- **Dons de livres** : annonce gratuite avec photo réelle obligatoire et aperçu avant publication, vérifiée par l’équipe avant d’être visible, modifiable par le donneur (toute modification repasse en validation), lieu et date limite de retrait, réservation par un autre lecteur, retrait validé par QR code ou code court ; pour les anciens dons sans photo, la couverture du catalogue est affichée et identifiée comme telle
- **Messagerie liée aux transactions** : une conversation s'ouvre à chaque réservation de don ou d'emprunt et à chaque demande d'achat, sans message libre entre membres ni adresse email dévoilée ; bulles, non lus en gras, compteur dans l'entête, mail de notification (au plus un toutes les 15 minutes), anti-spam, conversation fermée en fin de transaction et gardée en lecture seule, signalement d'un message avec motif
- **Événements** (dédicaces, rencontres, ateliers) : calendrier filtrable, inscription dans la limite des places, liste d'attente automatique ou manuelle, billet avec QR code, contrôle des présences le jour J, export PDF des participants, mails en cas d'annulation
- **Événements proposés par les auteurs**, validés ou refusés par l'équipe avec un message
- **Avis** : note, titre et commentaire, un avis par livre, badge « achat vérifié », tri, signalement, modération (un avis masqué reste archivé), page « Mes avis »
- **Newsletter** avec ou sans compte

### Programme de fidélité
- **Points** : 1 point par euro payé, points pour les avis validés, les dons récupérés, les présences aux événements, les ventes entre lecteurs, les emprunts rendus à l'heure (+5 points, cinq récompenses par mois), le premier achat et les badges
- **Bons de réduction** de 5 € à 38 €, utilisables au paiement selon des règles claires (un par commande, plafond de 50 % des livres)
- **Six niveaux de lecteur** avec avantages (livraison offerte plus tôt, priorité sur les listes d'attente, bon d'anniversaire) et **badges** bronze, argent, or et rares
- Page « Mes points et badges » avec la progression et l'historique de chaque mouvement

### Comptes
- Inscription avec confirmation par mail, connexion protégée par un **puzzle captcha** maison vérifié côté serveur, mot de passe oublié avec lien à usage unique valable 1 heure
- Rôles : lecteur, auteur, modérateur, gestionnaire, administrateur ; **candidatures à la modération** avec motivations, étudiées depuis le back-office
- **Candidature auteur** à l'inscription ou depuis le compte : nom de plume, biographie, ouvrages publiés (ISBN vérifié par sa clé), liens et photo ; le compte reste lecteur jusqu'à la validation par l'équipe, qui répond par mail (refus motivé, nouvelle demande possible après 7 jours)
- **Pages auteur publiques** et page « Nos auteurs » avec recherche : biographie, ouvrages, livres disponibles sur le site et rencontres ; l'auteur modifie sa page à tout moment (modifications publiées et inscrites au journal), l'équipe peut la masquer
- Espace « Mon compte » : profil avec téléphone facultatif (visible seulement par l'administrateur) et **préférences de lecture** (univers, catégories, thèmes), photo (recadrée et convertie en WebP), commandes, favoris, alertes, adresses, dons, événements, avis
- **Changement d’adresse email confirmé** : mot de passe actuel, lien à usage unique valable une heure sur la nouvelle adresse, ancienne adresse conservée avant confirmation, annulation possible, limitation des tentatives et notifications ; reconnexion après confirmation. Migration : `sql/maj_changement_email.sql`.
- **Historique de consultation** respectueux de la vie privée : 20 dernières fiches au plus, gardées 90 jours, visibles du seul lecteur, effaçables et suspendables à tout moment
- Déconnexion automatique après 10 minutes d'inactivité

- **Profil actif** : badge informatif pour les lecteurs et auteurs, calculé sur une connexion et au moins une action validée dans les 30 derniers jours ; période et minimum d’actions réglables dans les règles de récompenses. Achats annulés et contenus non validés exclus, sans points ni priorité supplémentaires.

### Back-office (`admin/`)
- Session séparée du site, droits par rôle, menu latéral et fil d'Ariane
- **Connexions actives** : synthèse des sessions et utilisateurs, recherche, filtres et consultation par membre (appareil en libellé court, connexion et dernière activité lisibles, activité la plus récente en premier) ; fermetures individuelles et globale des clients/auteurs avec confirmation, autres administrateurs protégés, anciennes connexions invalidées après changement du mot de passe
- **Règles des emprunts** : limites par lecteur, délai de retrait et valeurs proposées pour les nouveaux exemplaires administrables, historique versionné, durée de prolongation conservée à la réservation ; les prêts existants gardent leurs conditions. Migration rejouable : `sql/maj_regles_emprunts.sql`.
- **Règles de récompenses** : barème de points, plafonds mensuels, seuils des niveaux et paliers des badges modifiables par un administrateur, avec historique des versions et conservation des points déjà gagnés
- **Tableau de bord** : chiffre d'affaires, commandes, nouveaux membres, stock, ventes sur 30 jours et par univers, liste « À traiter »
- **Catalogue** : fiches, exemplaires par état, inventaire avec alertes de rupture, **archivage réversible** des fiches (exemplaires et historique conservés, fiche masquée du site et retirée de la vente et de l’emprunt), catégories et tags, validation des livres proposés par les auteurs, **modération des annonces** entre particuliers (photos, motif de refus ou de retrait, modification par l'équipe), **suivi des ventes entre lecteurs** (ventes en retard, litiges : valider, annuler avec retrait des points, classer)
- **Commandes** : liste filtrable, fiche détaillée, annulation (stock remis, points rendus, client prévenu)
- **Communauté** : avis à valider et signalés, messages signalés (masquer un message, clore une conversation, suspendre un compte), dons, événements et présences, niveaux et badges des membres, membres (rôles, suspension, **création d'un compte** par l'administrateur : la personne reçoit une invitation valable 48 heures pour choisir elle-même son mot de passe, ce qui confirme aussi son adresse), candidatures à la modération et candidatures auteur (accepter, refuser avec message, historique, masquer une page auteur), utilisateurs en ligne, newsletter
- **Système** : journal d'activité, images du captcha, export PDF des membres, **droits de l’équipe** configurables par famille de fonctionnalités pour les gestionnaires et modérateurs (comptes et paramètres réservés à l’administrateur, historique des versions), **paramètres du site** (expéditeur des mails, informations légales, textes des mentions, de la confidentialité et des CGU), **suivi des relances** d’emprunt (passages de la tâche planifiée, rappels envoyés et erreurs d’envoi)
- **Premier administrateur** créé depuis une page d'installation à usage unique, protégée par un jeton défini dans `secrets.php` et fermée dès qu'un administrateur existe

### Qualité
- Requêtes préparées partout, échappement de toutes les sorties, **jeton CSRF** sur chaque formulaire qui modifie des données, uploads vérifiés (contenu réel, taille, renommage)
- Mails au **gabarit commun** aux couleurs du site, avec version texte
- Responsive du téléphone au grand écran, menus qui restent toujours dans l'écran
- Accessibilité : contrastes suffisants, libellés reliés aux champs, textes alternatifs, focus visible au clavier, zones tactiles d'au moins 44 px sur écran tactile
- Page 404 aux couleurs du site
- **Aucune ressource tierce** : polices, Bootstrap, icônes et générateur de QR code sont hébergés dans `ressources/` (versions fixes, licences jointes), si bien qu'aucune adresse IP de visiteur n'est transmise à un service externe
- **Mentions légales** et **politique de confidentialité** (données collectées, finalités, durées de conservation, droits), liées dans le pied de page
- **Mode production** réglé dans `secrets.php` : erreurs masquées et journalisées dans un fichier privé, redirection vers HTTPS, cookies de session `Secure`, `HttpOnly` et `SameSite`, en-têtes de sécurité
- **Dossier privé réglable** (`DOSSIER_PRIVE` dans `secrets.php`) : journaux, erreurs PHP et sessions de production peuvent être placés hors de la racine web
- **Relances d'emprunt automatiques** : script `cron/relances_emprunts.php` lancé par une tâche planifiée (rappel avant échéance, le jour même, relance hebdomadaire en cas de retard, expiration des réservations), refusé s'il est appelé depuis le web
- **Préparation de la mise en ligne** : `outils/preparer_envoi.php` crée hors du dépôt un dossier propre à envoyer, organisé comme l'hébergement (racine web et dossier privé séparés, sans documentation, scripts SQL ni fichiers privés), un modèle de `secrets.php` de production sans mot de passe et un script SQL d'installation sans compte utilisable

## Technologies

PHP 8.2 à 8.4 (PDO) · MySQL / MariaDB · HTML, CSS et JavaScript sans framework · Bootstrap 5 · PHPMailer · Dompdf · Composer · Git

## Installation pas à pas (Windows + XAMPP)

### 1. Prérequis
- [XAMPP](https://www.apachefriends.org) (PHP 8.2, MariaDB) et [Composer](https://getcomposer.org)
- Dans `C:\xampp\php\php.ini`, activer l'extension **GD** (retirer le `;` devant `extension=gd`) : elle sert au captcha et aux photos de profil
- Démarrer **Apache** et **MySQL** depuis le panneau XAMPP

### 2. Récupérer le projet
```bash
cd /c/xampp/htdocs
mkdir -p projet_web && cd projet_web
git clone https://github.com/gfadeyi/biblioccaz.project.git biblioccaz
cd biblioccaz
composer install
mkdir -p secure_data img/captcha
touch secure_data/logs.txt
cp img/Biblioccaz_Logo_New.png img/captcha/
```

### 3. Créer la base
`sql/biblioccaz.sql` crée la base `biblioccaz`, son utilisateur MySQL et toutes les tables (il contient déjà toutes les migrations) :
```bash
/c/xampp/mysql/bin/mysql.exe -u root --default-character-set=utf8mb4 < sql/biblioccaz.sql
```
L'option `--default-character-set=utf8mb4` est importante : sans elle, les accents sont abîmés.

### 4. Ajouter les données de démonstration (facultatif)
Une cinquantaine de livres aux ISBN vérifiés, des lecteurs à tous les niveaux, des commandes, des avis, des dons et des événements, avec des dates calculées à partir du jour de l'import :
```bash
/c/xampp/mysql/bin/mysql.exe -u root --default-character-set=utf8mb4 biblioccaz < sql/donnees_demo.sql
/c/xampp/php/php.exe sql/verifier_encodage.php
```
Un second jeu étoffe le catalogue : environ 100 livres dans tous les univers et 54 manuels et œuvres au programme, du CP aux études supérieures, proposés à l'emprunt avec une dizaine d'exemplaires chacun. Tous les ISBN viennent du catalogue de la BnF. Le jeu ajoute aussi une vingtaine de lecteurs, des fiches auteur et quatre candidatures auteur en attente. Le script peut être rejoué sans créer de doublon ; à importer après `donnees_demo.sql` :
```bash
/c/xampp/mysql/bin/mysql.exe -u root --default-character-set=utf8mb4 biblioccaz < sql/donnees_demo_catalogue.sql
```
Les couvertures d'éditeurs ne sont pas versionnées : les livres concernés affichent la couverture par défaut.

Un troisième jeu ajoute **300 références françaises**, avec quatre états de vente et quatre exemplaires à l'emprunt par titre. Les prix et les stocks sont des exemples de démonstration : 1 200 offres de vente représentent 4 800 exemplaires, auxquels s'ajoutent 1 200 exemplaires à emprunter. Les notices et les couvertures sont issues d'[Open Library](https://openlibrary.org), avec des compléments du [catalogue de la BnF](https://catalogue.bnf.fr). Les sources par ISBN sont conservées dans `docs/catalogue-300.json`. Les images restent hors du dépôt Git et sont hébergées localement lors du déploiement. La mise à jour `sql/maj_couvertures_completes.sql` remplace les anciennes références distantes et les couvertures de démonstration des ouvrages réels par les fichiers récupérés à partir des ISBN. Les trois ouvrages fictifs conservent leurs couvertures de démonstration. Le détail des sources est dans `docs/couvertures-catalogue.json`. À importer après les jeux précédents :

```bash
/c/xampp/mysql/bin/mysql.exe -u root --default-character-set=utf8mb4 biblioccaz < sql/donnees_catalogue_300.sql
```

Ce script ne crée aucun compte et ne modifie aucun stock existant. Son rejeu ne réapprovisionne pas les exemplaires déjà présents. Le script complémentaire `sql/maj_couvertures_catalogue.sql` rétablit 81 couvertures originales du catalogue précédent, uniquement si la référence et son ancienne couverture correspondent encore. Les images locales restent hors du dépôt. Le générateur de livraison inclut désormais les couvertures par défaut, afin de conserver un catalogue complet ; `--sans-couvertures-editeur` permet de les écarter explicitement.


### 5. Configurer
Copier `secrets.example.php` en `secrets.php` et y renseigner l'accès à la base, l'adresse du site et l'envoi des mails. Ce fichier est ignoré par Git. En local, laisser `MODE_PRODUCTION` à `false`.

### 6. Recevoir les mails en local
Lancer [Mailpit](https://github.com/axllent/mailpit) : les mails du site (confirmation d'inscription, commande, mot de passe…) sont capturés sur http://localhost:8025, sans rien envoyer réellement. C'est la configuration par défaut de `secrets.example.php` (SMTP `localhost:1025`).

### 7. Ouvrir le site
- Site : http://localhost/projet_web/biblioccaz/
- Back-office : http://localhost/projet_web/biblioccaz/admin/

Le script d'installation crée un compte administrateur initial. Remplace son mot de passe dès la première connexion et conserve les identifiants dans ta configuration privée.

Si le projet est installé dans un autre dossier que `/projet_web/biblioccaz/`, adapter le chemin de la page 404 dans `.htaccess` et `SITE_URL` dans `secrets.php`.

### Mettre à jour une base existante
Chaque évolution de structure a sa migration `sql/maj_<sujet>.sql`, rejouable sans erreur :
```bash
/c/xampp/mysql/bin/mysql.exe -u root --default-character-set=utf8mb4 biblioccaz < sql/maj_candidatures.sql
```

Pour les emprunts, sauvegarder la base avant toute mise à jour. Si les tables F5 n'existent pas, appliquer `sql/maj_emprunts.sql`, puis `sql/maj_emprunts_securite.sql`. Si F5 est déjà installé mais que `emprunt.blocage_retard` manque, seule `sql/maj_emprunts_securite.sql` est nécessaire : elle conserve le réglage de pénalité de chaque emprunt existant à partir de son exemplaire. Les prochaines réservations prennent leur propre instantané. L'installation complète contient déjà cette structure.

Pour le suivi individuel des connexions et son tableau de bord, sauvegarder la base puis appliquer `sql/maj_sessions.sql`, `sql/maj_sessions_supervision.sql` et `sql/maj_sessions_appareil.sql`. Les sessions anciennes devront se reconnecter pour enregistrer leur rôle d'ouverture. Sans les structures requises, la page signale que la supervision est indisponible. Voir le [guide des connexions](docs/supervision-sessions.md).

Pour administrer les récompenses, appliquer après sauvegarde `sql/maj_regles_recompenses.sql`. Sans cette migration, les règles historiques restent utilisées. Le [guide des récompenses](docs/regles-recompenses.md) précise les effets des changements et leurs limites.

Pour les candidatures et pages auteur, appliquer `sql/maj_candidatures_auteur.sql`. Sans cette migration, les pages concernées indiquent que les candidatures ouvrent bientôt et le reste du site fonctionne normalement.

Pour l’archivage des fiches, les droits configurables, les paramètres du site et le suivi des relances, appliquer après sauvegarde `sql/maj_archivage_catalogue.sql`, `sql/maj_parametres_site.sql` et `sql/maj_suivi_relances.sql`. Sans ces migrations, les droits habituels et les informations de `secrets.php` restent utilisés, et les pages concernées indiquent la migration à installer.

Pour les suggestions personnalisées, appliquer `sql/maj_recommandations.sql` (téléphone, préférences de lecture et historique de consultation). Sans cette migration, les suggestions s'appuient seulement sur les achats, emprunts, favoris et notes, aucun historique n'est enregistré et les préférences sont signalées comme indisponibles.

## Organisation du code

| | Site | Back-office |
|---|---|---|
| Adresse | `/` | `/admin/` |
| Public | visiteurs, lecteurs, auteurs | administrateur, gestionnaire, modérateur |
| Connexion | `login.php` | `admin/login.php` (session séparée) |
| Mise en page | `header.php` / `footer.php` | `admin/_haut.php` / `admin/_bas.php` |

Code partagé : `config.php` (connexion, session), `mailer.php` (mails), bibliothèques `*_lib.php` (points, dons, événements, avis, commandes…), `style.css` et `javascript/`.

Droits dans le back-office (`admin/_init.php`) :
- **Modérateur** : tableau de bord, fiches à valider, annonces, avis, messages, dons
- **Gestionnaire** : en plus, livres, stock, commandes, événements et emprunts (mise à disposition, retrait, retour et levée motivée des blocages)
- Ces droits habituels se règlent par famille (catalogue, modération, événements, candidatures auteur) dans « Droits de l’équipe »
- **Administrateur** : tout, dont membres, candidatures (la validation des auteurs dépend du seul droit `candidatures_auteur`, à étendre au gestionnaire ou au modérateur si besoin), catégories, newsletter, journal, captcha et export PDF
