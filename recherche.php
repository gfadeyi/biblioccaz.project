<?php

require_once 'config.php';


$recherche = isset($_GET['q']) ? trim ($_GET['q']) : '';

$livres = [];

if (!empty($recherche)) {
    $stmt = $pdo->prepare("SELECT id_livre as id, titre, auteur, couverture, description FROM livre WHERE titre LIKE :q OR auteur LIKE :q");
    $stmt-> execute ([ ':q' => "%$recherche%"]);
    $livres = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

header('Content-Type: application/json');
echo json_encode($livres);
exit;