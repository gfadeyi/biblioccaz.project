<?php
require_once 'config.php';

$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$results = array();

if (!empty($search)) {
    $query = "SELECT id_livre as id, titre, auteur FROM livre WHERE is_valide = 1 AND (titre LIKE ? OR auteur LIKE ?) ORDER BY titre LIMIT 8";
    $stmt = $pdo->prepare($query);
    $stmt->execute(["%$search%", "%$search%"]);
    $results = $stmt->fetchAll();
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($results, JSON_UNESCAPED_UNICODE);
exit;