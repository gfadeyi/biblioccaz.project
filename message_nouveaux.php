<?php
require_once __DIR__ . '/sessions_lib.php';
demarrerSession();
$activiteReelle = $_SESSION['last_activity'] ?? null;
require_once 'config.php';
require_once 'messagerie_lib.php';
require_once 'compte_lib.php';
require_once 'icones_lib.php';
require_once 'avis_lib.php';
$action = $_SERVER['REQUEST_METHOD'] === 'POST' && is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
if ($activiteReelle !== null && !in_array($action, ['envoyer', 'ecrire'], true)) {
    $_SESSION['last_activity'] = $activiteReelle;
}
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function repondreJson(array $donnees, int $code = 200): never
{
    http_response_code($code);
    echo json_encode($donnees, JSON_UNESCAPED_UNICODE);
    exit();
}

$idUser = (int) ($_SESSION['user_id'] ?? 0);
$idConversation = (int) ($_POST['id_conversation'] ?? $_GET['id'] ?? 0);
$dernier = max(0, (int) ($_POST['dernier'] ?? $_GET['dernier'] ?? 0));
if (!$idUser) {
    repondreJson(['nouveaux' => 0, 'authentification_requise' => true], 403);
}
if (!$idConversation) {
    repondreJson(['nouveaux' => 0], 403);
}

if ($action === '') {
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM conversation_message m
            INNER JOIN conversation_participant p ON p.id_conversation = m.id_conversation AND p.id_user = ?
            WHERE m.id_conversation = ? AND m.id_message > ? AND (m.id_auteur IS NULL OR m.id_auteur <> ?)");
        $stmt->execute([$idUser, $idConversation, $dernier, $idUser]);
        repondreJson(['nouveaux' => (int) $stmt->fetchColumn()]);
    } catch (PDOException $e) {
        repondreJson(['nouveaux' => 0]);
    }
}

if (!verifierCsrf()) {
    repondreJson(['ok' => false, 'message' => 'Session expirée : rechargez la page.', 'recharger' => true], 403);
}
try {
    $conversation = conversationParId($pdo, $idConversation);
} catch (PDOException $e) {
    $conversation = null;
}
if (!$conversation || !estParticipant($conversation, $idUser)) {
    repondreJson(['ok' => false, 'message' => 'Conversation introuvable.'], 403);
}

function etatConversation(PDO $pdo, array $conversation, int $idUser, int $dernier): array
{
    $idConversation = (int) $conversation['id_conversation'];
    $retour = is_string($_POST['retour'] ?? null) && $_POST['retour'] === 'objet' ? 'objet' : 'messages';
    $csrf = jetonCsrf();
    $messages = [];
    $dernierLu = $dernier;
    $deLautre = false;
    foreach (messagesConversationApres($pdo, $idConversation, $dernier) as $message) {
        $messages[] = [
            'id' => (int) $message['id_message'],
            'jour' => date('Y-m-d', strtotime($message['date_envoi'])),
            'libelle_jour' => libelleJourMessage($message['date_envoi']),
            'html' => htmlMessageConversation($message, $conversation, $idUser, $csrf, $retour),
        ];
        $dernierLu = (int) $message['id_message'];
        $deLautre = $deLautre || (int) $message['id_auteur'] !== $idUser;
    }
    if ($deLautre && ($_POST['visible'] ?? '1') === '1') {
        marquerConversationLue($pdo, $idConversation, $idUser);
    }
    $autre = autreParticipant($conversation, $idUser);
    return [
        'ok' => true,
        'messages' => $messages,
        'dernier' => $dernierLu,
        'suite' => count($messages) >= MESSAGES_ACTUALISATION_MAX,
        'ecrit' => $conversation['statut'] === 'ouverte' && autreParticipantEcrit($pdo, $idConversation, $idUser),
        'ouverte' => $conversation['statut'] === 'ouverte' && !($autre && in_array($autre['statut'], ['refuse_temporaire', 'refuse_definitif'], true)),
        'non_lus_total' => nombreMessagesNonLus($pdo, $idUser),
        'non_lus' => nonLusParConversation($pdo, $idUser),
    ];
}

try {
    if ($action === 'actualiser') {
        repondreJson(etatConversation($pdo, $conversation, $idUser, $dernier));
    }
    if ($action === 'ecrire') {
        $ecrit = ($_POST['ecrit'] ?? '') === '1' && $conversation['statut'] === 'ouverte';
        repondreJson(['ok' => signalerEcriture($pdo, $idConversation, $idUser, $ecrit)]);
    }
    if ($action === 'envoyer') {
        $resultat = envoyerMessage($pdo, $idConversation, $idUser, is_string($_POST['contenu'] ?? null) ? $_POST['contenu'] : '');
        if ($resultat[0] !== 'success') {
            repondreJson(['ok' => false, 'message' => $resultat[1]], 422);
        }
        $pdo->prepare("UPDATE user SET last_activity = NOW() WHERE id = ?")->execute([$idUser]);
        $etat = etatConversation($pdo, conversationParId($pdo, $idConversation), $idUser, $dernier);
        repondreJson($etat + ['envoye' => (int) $resultat[2]]);
    }
} catch (PDOException $e) {
    insertLog('ERREUR', "Messagerie instantanée, conversation #$idConversation : " . $e->getMessage());
    repondreJson(['ok' => false, 'message' => 'Le serveur n’a pas pu traiter la demande.'], 500);
}
repondreJson(['ok' => false, 'message' => 'Action inconnue.'], 400);
