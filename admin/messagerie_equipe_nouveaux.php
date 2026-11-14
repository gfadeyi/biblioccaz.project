<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../messagerie_equipe_lib.php';
require_once __DIR__ . '/_messagerie_equipe_vues.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (!estEquipe()) {
    http_response_code(401);
    echo json_encode(['authentification_requise' => true]);
    exit();
}
exigerDroit('equipe', true);
if (!messagerieEquipeDisponible($pdo)) {
    echo json_encode(['messages' => [], 'non_lus' => 0]);
    exit();
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && !verifierCsrf()) {
    http_response_code(403);
    echo json_encode(['erreur' => 'csrf']);
    exit();
}
$parametres = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' ? $_POST : $_GET;
$moi = (int) $_SESSION['user_id'];
$idConversation = max(0, (int) ($parametres['c'] ?? 0));
$dernier = max(0, (int) ($parametres['dernier'] ?? 0));
$reponse = ['messages' => [], 'dernier' => $dernier];

if ($idConversation) {
    if (!conversationEquipe($pdo, $idConversation, $moi)) {
        http_response_code(404);
        echo json_encode(['introuvable' => true]);
        exit();
    }
    $stmt = $pdo->prepare('SELECT m.id_message, m.id_auteur, m.contenu, m.date_envoi, u.pseudo, u.role
                           FROM equipe_message m LEFT JOIN user u ON u.id = m.id_auteur
                           WHERE m.id_conversation = ? AND m.id_message > ?
                           ORDER BY m.id_message LIMIT 100');
    $stmt->execute([$idConversation, $dernier]);
    foreach ($stmt->fetchAll() as $message) {
        $reponse['messages'][] = ['id' => (int) $message['id_message'], 'moi' => (int) $message['id_auteur'] === $moi, 'html' => vueMessageEquipe($message, $moi)];
        $reponse['dernier'] = (int) $message['id_message'];
    }
    if ($reponse['messages'] && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($parametres['lu'] ?? '') === '1') {
        marquerConversationEquipeLue($pdo, $idConversation, $moi);
    }
}

$reponse['liste'] = vueListeConversationsEquipe(conversationsEquipe($pdo, $moi), $idConversation ?: null);
$reponse['non_lus'] = nombreNonLusEquipe($pdo, $moi);
echo json_encode($reponse);
