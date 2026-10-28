<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/avis_lib.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_string($_POST['csrf'] ?? null) || !verifierCsrf()) {
    http_response_code(403);
    exit('Requête de déconnexion invalide.');
}
terminerSuiviSession($pdo);
insertLog('DECONNEXION', 'Déconnexion volontaire du site');
detruireSessionCourante();
header("Location: index.php");
exit();
