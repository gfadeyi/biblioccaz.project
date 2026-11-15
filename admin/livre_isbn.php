<?php
require_once __DIR__ . '/_init.php';
exigerDroit('catalogue', true);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$isbn = strtoupper(str_replace(['-', ' '], '', is_string($_GET['q'] ?? null) ? trim($_GET['q']) : ''));
if (!preg_match('/\A(\d{9}[\dX]|\d{13})\z/', $isbn)) {
    echo json_encode(['livres' => []]);
    exit();
}
$stmt = $pdo->prepare("SELECT id_livre, titre, auteur, isbn FROM livre WHERE REPLACE(REPLACE(UPPER(isbn), '-', ''), ' ', '') = ? ORDER BY id_livre LIMIT 5");
$stmt->execute([$isbn]);
echo json_encode(['livres' => array_map(fn($livre) => [
    'id' => (int) $livre['id_livre'],
    'titre' => $livre['titre'],
    'auteur' => (string) ($livre['auteur'] ?? ''),
    'isbn' => (string) $livre['isbn'],
], $stmt->fetchAll())], JSON_UNESCAPED_UNICODE);
