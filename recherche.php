<?php

require_once 'config.php';


$recherche = isset($_GET['q']) ? trim ($_GET['q']) : '';

$livres =[];

if (!empty($recherche)) {
    $req = $bdd->prepare("SELECT id_livre, titre, auteur, couverture, description FROM livre WHERE titre LIKE :q OR auteur LIKE :q");
    $stmt-> execute (["%$recherche%"]);
    $livres = $stmt->fetchAll(PDO::FETCH_ASSO);
}

header ('Content-Type : application/json');
echo json_encode($livres);
exit;