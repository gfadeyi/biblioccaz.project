<?php
session_start();
$activiteReelle = $_SESSION['last_activity'] ?? null;
require_once 'config.php';
require_once 'messagerie_lib.php';
if ($activiteReelle !== null) {
    $_SESSION['last_activity'] = $activiteReelle;
}
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$idUser = (int) ($_SESSION['user_id'] ?? 0);
$idConversation = (int) ($_GET['id'] ?? 0);
$dernier = (int) ($_GET['dernier'] ?? 0);
if (!$idUser || !$idConversation) {
    http_response_code(403);
    echo json_encode(['nouveaux' => 0]);
    exit();
}
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM conversation_message m
        INNER JOIN conversation_participant p ON p.id_conversation = m.id_conversation AND p.id_user = ?
        WHERE m.id_conversation = ? AND m.id_message > ? AND (m.id_auteur IS NULL OR m.id_auteur <> ?)");
    $stmt->execute([$idUser, $idConversation, $dernier, $idUser]);
    echo json_encode(['nouveaux' => (int) $stmt->fetchColumn()]);
} catch (PDOException $e) {
    echo json_encode(['nouveaux' => 0]);
}
