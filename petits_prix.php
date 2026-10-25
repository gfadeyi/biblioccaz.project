<?php
// Page de catégorie : réutilise le catalogue avec un filtre imposé
$preset = ['prix_max' => 10];
$defauts = ['tri' => 'prix_asc'];
$titrePage = 'Petits prix';
$sousTitre = 'Tous nos livres à moins de 10 €';
require 'catalogue.php';
