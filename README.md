# BIBLIOccaz

Site e-commerce de vente de livres d'occasion, réalisé en PHP / MySQL.

Projet annuel de 1re année (ESGI, classe 1A1) par **Gaël Fadeyi**, **Jeanny Benon** et **David Radom**, repris et maintenu par Gaël Fadeyi.

## Fonctionnalités

- **Catalogue** : nouveautés, meilleures ventes, petits prix, recherche, fiche détaillée de chaque livre
- **Stock d'occasion** : plusieurs exemplaires par livre, avec état (neuf, très bon, bon, usé) et prix
- **Panier et commande** : remises automatiques (-5 % dès 30 €, -10 % dès 50 €), livraison offerte dès 20 €, historique des commandes
- **Points, niveaux et badges** : 1 point par euro payé, points pour les avis validés, les dons récupérés et les présences aux événements ; bons de réduction de 5 € à 38 € à utiliser au paiement ; six niveaux de lecteur (bonus de points, livraison offerte plus tôt, priorité sur les listes d'attente, bon d'anniversaire) ; badges bronze, argent, or et badges rares ; page « Mes points et badges » avec historique
- **Validation des avis** : avis publiés tout de suite, points crédités après validation par un modérateur
- **Commandes dans le back-office** : liste filtrable (numéro, client, statut, période) avec chiffre d'affaires et panier moyen, fiche détaillée (livres, stock actuel de chaque offre, client, points, historique) et annulation confirmée : livres remis en stock (clients en attente prévenus), points du bon rendus, points gagnés retirés, mail au client ; les offres épuisées restent en base avec un stock à 0 pour garder l'historique
- **Univers, catégories, tags et filtres** : catalogue rangé en 6 univers et leurs catégories (méga-menu, fil d'Ariane, puces de catégories), tags en trois familles (thèmes & ambiances, public, particularités) combinables avec le nombre de livres par tag, filtres (prix, état, note) repliés derrière un bouton « Filtrer » sur téléphone avec puces des filtres actifs, tris, pagination ; univers, catégories et tags gérés depuis le back-office ; anciennes adresses de catégories redirigées (301)
- **Avis** : notes et commentaires (badge « achat vérifié »), signalement et modération ; page « Mes avis » (modifier ou supprimer, état des points de chaque avis)
- **Dons de livres** : annonces gratuites avec réservation et retrait validé par QR code
- **Événements** : dédicaces, rencontres et ateliers avec calendrier filtrable, inscriptions, liste d'attente, billets à QR code, contrôle des présences et export PDF des participants
- **Événements proposés par les auteurs** : depuis l'espace créateur, un auteur propose une dédicace, une rencontre ou un atelier (date, horaires, lieu, places, image, jusqu'à 5 livres associés), peut la modifier ou la retirer tant qu'elle est en attente ; file « Proposés » avec compteur dans le back-office, validation, correction ou refus motivé, mail à l'auteur à chaque décision ; une fois publié, l'événement affiche « Proposé par … » et ses livres, et l'auteur suit ses inscrits (prénom et initiale, sans email)
- **Comptes et rôles** : client, auteur (proposition de livres), modérateur, gestionnaire, administrateur
- **Espace « Mon compte »** : identité, niveau et points, dernières commandes, favoris, alertes, événements à venir, dons et newsletter sur une seule page, adaptée au rôle (client, auteur, équipe) ; photo de profil (JPG, PNG ou WebP, 2 Mo maximum, aperçu avant d'enregistrer, recadrée et convertie en WebP) affichée dans l'en-tête, le menu du compte et les avis, avec retour possible à l'initiale sur fond coloré
- **Fenêtre de confirmation** du site pour toutes les suppressions et annulations côté client (avis, adresses, dons, événements), sans boîte de dialogue du navigateur
- **Back-office séparé** (`admin/`) aux couleurs du site : tableau de bord (indicateurs avec tendance, ventes sur 30 jours et par univers, liste « À traiter », activité récente), inventaire lisible (stock par état, prix à partir de, filtres par univers, tri par colonne, alertes de stock bas et de rupture), validation des fiches, avis signalés, dons, membres, newsletter, journal d'activité, export PDF des membres ; confirmations dans une fenêtre propre, fil d'Ariane sur chaque page
- **Emails** : vérification d'adresse à l'inscription, récupération de mot de passe, newsletter (PHPMailer)
- **Captcha puzzle** maison, vérifié côté serveur

## Technologies

PHP 8 · MySQL / MariaDB (PDO) · Bootstrap 5 · JavaScript · PHPMailer · Dompdf · Composer

## Installation en local (Windows + XAMPP)

1. Installer [XAMPP](https://www.apachefriends.org) et [Composer](https://getcomposer.org). Dans `C:\xampp\php\php.ini`, activer l'extension **GD** (retirer le `;` devant `extension=gd`, utilisée par le captcha), puis démarrer **Apache** et **MySQL**.
2. Cloner le projet dans `htdocs` :
   ```bash
   cd /c/xampp/htdocs/projet_web
   git clone https://github.com/gfadeyi/biblioccaz.project.git biblioccaz
   cd biblioccaz
   composer install
   ```
3. Créer les dossiers non versionnés :
   ```bash
   mkdir -p secure_data img/captcha
   touch secure_data/logs.txt
   cp "img/Biblioccaz_Logo_New.png" img/captcha/
   ```
4. Importer `sql/biblioccaz.sql` dans phpMyAdmin (base, utilisateur MySQL, tables et données d'exemple).
5. Copier `secrets.example.php` en **`secrets.php`** et y renseigner les identifiants (base de données, envoi de mails). Ce fichier n'est jamais envoyé sur GitHub.
6. Ouvrir le site client : http://localhost/projet_web/biblioccaz/
   et le back-office : http://localhost/projet_web/biblioccaz/admin/

Compte administrateur de démonstration : `admin` / `admin123` (à changer).

## Organisation : site client et back-office

Le projet contient deux interfaces qui partagent la même base de données et le même code commun (`config.php`, `mailer.php`, `*_lib.php`) :

| | Site client (front-office) | Back-office |
|---|---|---|
| Adresse | `/` | `/admin/` |
| Public | visiteurs, clients, auteurs | équipe : administrateur, gestionnaire, modérateur |
| Connexion | `login.php` | `admin/login.php` (refuse les comptes clients) |
| Mise en page | `header.php` / `footer.php` (catalogue, panier) | `admin/_haut.php` / `admin/_bas.php` (menu latéral) |

Droits dans le back-office (définis dans `admin/_init.php`) :

- **Modérateur** : tableau de bord, validation des fiches livres, avis signalés, dons
- **Gestionnaire** : + ajout et modification des livres, stock, événements
- **Administrateur** : tout, dont membres, catégories, newsletter, journal, images du captcha, export PDF

Toutes les actions qui modifient des données passent par des formulaires POST protégés par un jeton anti-CSRF. En production, le dossier `admin/` peut être servi sur son propre sous-domaine (par exemple `admin.biblioccaz.fr`).

### Emails en local

Avec [Mailpit](https://github.com/axllent/mailpit), les mails envoyés par le site sont capturés et lisibles sur http://localhost:8025, sans rien envoyer réellement. C'est la configuration par défaut de `secrets.example.php`.
