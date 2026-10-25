<?php
// Membres actifs dans les 5 dernières minutes, lus par en_ligne.php (réservé à l'admin).
require_once __DIR__ . '/_init.php';
exigerDroit('admin', true);
header('Content-Type: application/json; charset=utf-8');

$stmt = $pdo->query("
    SELECT pseudo, role, last_activity
    FROM user WHERE last_activity > NOW() - INTERVAL 5 MINUTE
    ORDER BY last_activity DESC");

$enLigne = array_map(fn($u) => [
    'pseudo' => $u['pseudo'],
    'role'   => ROLES_LIBELLES[$u['role']] ?? $u['role'],
    'heure'  => date('H:i:s', strtotime($u['last_activity'])),
], $stmt->fetchAll());

echo json_encode($enLigne);
