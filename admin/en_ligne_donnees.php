<?php
require_once __DIR__ . '/_init.php';
exigerDroit('admin', true);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if (!suiviSessionsDisponible($pdo)) {
    http_response_code(503);
    echo json_encode(['erreur' => 'Migration des sessions requise.']);
    exit;
}
$sessions = array_map(fn($session) => [
    'id_suivi' => (int) $session['id_suivi'],
    'id_user' => (int) $session['id_user'],
    'pseudo' => $session['pseudo'],
    'role' => ROLES_LIBELLES[$session['role']] ?? $session['role'],
    'espace' => $session['espace'] === 'admin' ? 'Back-office' : 'Site',
    'connexion' => date('d/m/Y H:i:s', strtotime($session['date_connexion'])),
    'activite' => date('d/m/Y H:i:s', strtotime($session['derniere_activite'])),
    'courante' => (bool) $session['courante'],
    'modifiable' => $session['role'] !== 'admin' || (int) $session['id_user'] === (int) $_SESSION['user_id'],
], sessionsActives($pdo));
echo json_encode($sessions);
