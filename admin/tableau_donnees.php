<?php
require_once __DIR__ . '/_init.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');

if (!estEquipe()) {
    http_response_code(401);
    echo json_encode(['authentification_requise' => true, 'connexion' => 'login']);
    exit();
}
exigerDroit('equipe', true);
if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD'], true)) {
    http_response_code(405);
    header('Allow: GET, HEAD');
    echo json_encode(['erreur' => 'methode']);
    exit();
}
require_once __DIR__ . '/_tableau_priorites.php';

$priorites = prioritesTableau($pdo);
$bilan = urgencesTableau($priorites);
$donnees = [];
foreach ($priorites as $p) {
    $donnees[$p['cle']] = ['valeur' => $p['valeur'], 'detail' => $p['detail']];
}
echo json_encode(['ok' => true, 'heure' => date('H:i'), 'bilan' => ['libelle' => libelleUrgencesTableau($bilan), 'alerte' => $bilan['total'] > 0], 'priorites' => $donnees], JSON_UNESCAPED_UNICODE);
