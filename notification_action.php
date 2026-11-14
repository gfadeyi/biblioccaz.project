<?php
require_once 'config.php';
require_once 'avis_lib.php';
require_once 'notifications_membre_lib.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$enJson = str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
function repondreNotification(bool $enJson, array $donnees, int $code, string $redirection): never
{
    if ($enJson) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($donnees, JSON_UNESCAPED_UNICODE);
    } else {
        header('Location: ' . $redirection);
    }
    exit();
}

if (!isset($_SESSION['user_id'])) {
    repondreNotification($enJson, ['ok' => false, 'authentification_requise' => true], 401, 'login.php');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: notifications.php");
    exit();
}
if (!verifierCsrf()) {
    if (!$enJson) {
        $_SESSION['flash_message'] = ['danger', "Session expirée, merci de réessayer."];
    }
    repondreNotification($enJson, ['ok' => false, 'message' => 'Session expirée, rechargez la page.'], 403, 'notifications.php');
}

$idUser = (int) $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($action === 'ouvrir') {
    $lien = ouvrirNotification($pdo, $idUser, (int) ($_POST['id_notification'] ?? 0));
    header("Location: " . ($lien ?? 'notifications.php'));
    exit();
}
if ($action === 'tout_lire') {
    $nombre = marquerNotificationsLues($pdo, $idUser);
    if ($enJson) {
        repondreNotification(true, ['ok' => true, 'marquees' => $nombre, 'non_lues' => nombreNotificationsNonLues($pdo, $idUser)], 200, '');
    }
    $_SESSION['flash_message'] = ['success', $nombre > 0 ? "Toutes vos notifications sont marquées comme lues." : "Aucune notification non lue."];
}
header("Location: notifications.php");
exit();
