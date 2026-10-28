<?php
require_once 'config.php';
require_once 'recherche_lib.php';

$search = texteRecherche($_GET['q'] ?? '');
$results = array();

if ($search !== '') {
    $isbn = isbnRecherche($search);
    $clauseIsbn = $isbn !== '' ? "REPLACE(REPLACE(UPPER(isbn), '-', ''), ' ', '') = ?" : "isbn LIKE ? ESCAPE '!'";
    $query = "SELECT id_livre as id, titre, auteur FROM livre WHERE is_valide = 1 AND (titre LIKE ? ESCAPE '!' OR auteur LIKE ? ESCAPE '!' OR $clauseIsbn) ORDER BY titre, id_livre LIMIT 8";
    $stmt = $pdo->prepare($query);
    $stmt->execute([motifRecherche($search), motifRecherche($search), $isbn !== '' ? $isbn : motifRecherche($search)]);
    $results = $stmt->fetchAll();
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($results, JSON_UNESCAPED_UNICODE);
exit;
