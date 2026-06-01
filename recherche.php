<?php
echo "HELLO SCRIPT"; exit;

require_once 'config.php';


$recherche = isset($_GET['q']) ? trim ($_GET['q']) : '';

$livres =[];

if (!empty($recherche)) {
    $stmt = $pdo->prepare("SELECT id, titre, prix FROM livre WHERE titre LIKE ? LIMIT 5");
    $stmt-> execute (["%$recherche%"]);
    $livres = $stmt->fetchAll(PDO::FETCH_ASSO);
}

header ('Content-Type : application/json');
echo json_encode($livres);
exit;