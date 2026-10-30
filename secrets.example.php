<?php

define('MODE_PRODUCTION', false);

define('SITE_URL', 'http://localhost/projet_web/biblioccaz');

define('DB_HOST', 'localhost');
define('DB_NAME', 'biblioccaz');
define('DB_USER', 'utilisateur_mysql');
define('DB_PASS', 'mot_de_passe_mysql');

define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 1025);
define('SMTP_USER', '');
define('SMTP_PASS', '');
define('SMTP_SECURE', '');

define('MAIL_FROM', 'noreply@biblioccaz.local');
define('MAIL_FROM_NAME', 'BIBLIOccaz');

define('JETON_INSTALLATION', '');

define('INFOS_LEGALES', [
    'editeur_nom'         => '',
    'editeur_statut'      => '',
    'editeur_adresse'     => '',
    'editeur_email'       => '',
    'directeur_publication' => '',
    'hebergeur_nom'       => '',
    'hebergeur_adresse'   => '',
    'hebergeur_telephone' => '',
    'date_mise_a_jour'    => '',
]);
