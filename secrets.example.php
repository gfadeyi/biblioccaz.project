<?php
/*
 * MODÈLE DE CONFIGURATION - BIBLIOccaz
 * ------------------------------------
 * 1. Copie ce fichier et renomme la copie en "secrets.php"
 * 2. Remplis tes vraies valeurs dans secrets.php
 *
 * secrets.php est ignoré par Git : tes mots de passe ne partent jamais sur GitHub.
 * Ce fichier-ci (secrets.example.php) ne contient AUCUNE vraie valeur.
 */

// Adresse du site (sans "/" à la fin) - sert pour les liens envoyés par mail
define('SITE_URL', 'http://localhost/projet_web/biblioccaz');

// Base de données MySQL
define('DB_HOST', 'localhost');
define('DB_NAME', 'biblioccaz');
define('DB_USER', 'utilisateur_mysql');
define('DB_PASS', 'mot_de_passe_mysql');

// Envoi des mails (SMTP)
// En local avec Mailpit : SMTP_HOST 'localhost', SMTP_PORT 1025,
//                         SMTP_USER '', SMTP_PASS '', SMTP_SECURE ''
// Avec Gmail           : SMTP_HOST 'smtp.gmail.com', SMTP_PORT 587,
//                         SMTP_USER ton adresse, SMTP_PASS un mot de passe
//                         d'application Google, SMTP_SECURE 'tls'
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 1025);
define('SMTP_USER', '');
define('SMTP_PASS', '');
define('SMTP_SECURE', '');            // '', 'tls' ou 'ssl'

// Expéditeur des mails
define('MAIL_FROM', 'noreply@biblioccaz.local');
define('MAIL_FROM_NAME', 'BIBLIOccaz');
