<?php
require_once __DIR__ . '/_init.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_string($_POST['csrf'] ?? null) || !verifierCsrf()) {
    http_response_code(403);
    exit('Requête de déconnexion invalide.');
}
if (isset($_SESSION['user_id'])) {
    insertLog('DECONNEXION', "Déconnexion du back-office");
}
terminerSuiviSession($pdo);
detruireSessionCourante();
header('Location: login.php?deconnecte=1');
exit();
