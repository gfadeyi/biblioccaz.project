# BIBLIOccaz

Site e-commerce de vente de livres d'occasion, réalisé en PHP / MySQL.

Projet annuel de 1re année (ESGI, classe 1A1) par **Gaël Fadeyi**, **Jeanny Benon** et **David Radom**, repris et maintenu par Gaël Fadeyi.

## Fonctionnalités

- **Catalogue** : nouveautés, meilleures ventes, petits prix, recherche, fiche détaillée de chaque livre
- **Stock d'occasion** : plusieurs exemplaires par livre, avec état (neuf, très bon, bon, usé) et prix
- **Panier et commande** : remises automatiques (-5 % dès 30 €, -10 % dès 50 €), livraison offerte dès 20 €, historique des commandes
- **Points fidélité** : 1 point par euro dépensé
- **Comptes et rôles** : client, auteur (proposition de livres), modérateur (validation du catalogue), administrateur
- **Back-office** : gestion du stock, modération, gestion des membres, logs d'activité, export PDF des inscrits
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
6. Ouvrir http://localhost/projet_web/biblioccaz/

Compte administrateur de démonstration : `admin` / `admin123` (à changer).

### Emails en local

Avec [Mailpit](https://github.com/axllent/mailpit), les mails envoyés par le site sont capturés et lisibles sur http://localhost:8025, sans rien envoyer réellement. C'est la configuration par défaut de `secrets.example.php`.
