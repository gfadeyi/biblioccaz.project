<?php
require_once 'config.php';
require_once 'recherche_lib.php';

$search = texteRecherche($_GET['q'] ?? '');
$results = array();

if ($search !== '') {
    $isbn = isbnRecherche($search);
    $condition = $isbn !== '' ? "REPLACE(REPLACE(UPPER(isbn), '-', ''), ' ', '') LIKE ? ESCAPE '!'" : "(titre LIKE ? ESCAPE '!' OR auteur LIKE ? ESCAPE '!' OR isbn LIKE ? ESCAPE '!')";
    $parametres = $isbn !== '' ? [$isbn . '%'] : [motifRecherche($search), motifRecherche($search), motifRecherche($search)];
    $query = "SELECT id_livre as id, titre, auteur FROM livre WHERE is_valide = 1 AND $condition ORDER BY titre, id_livre LIMIT 8";
    $stmt = $pdo->prepare($query);
    $stmt->execute($parametres);
    $results = $stmt->fetchAll();
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($results, JSON_UNESCAPED_UNICODE);
exit;
