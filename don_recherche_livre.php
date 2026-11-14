<?php
require_once 'config.php';
require_once 'dons_lib.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['livres' => []]);
    exit();
}

echo json_encode(['livres' => rechercherLivresPourDon($pdo, is_string($_GET['q'] ?? null) ? $_GET['q'] : '')], JSON_UNESCAPED_UNICODE);
