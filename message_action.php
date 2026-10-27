<?php
require_once 'config.php';
require_once 'messagerie_lib.php';
require_once 'avis_lib.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$idUser = (int) $_SESSION['user_id'];
$idConversation = (int) ($_POST['id_conversation'] ?? 0);

function retourConversation(?array $conversation, string $type, string $message): never
{
    $_SESSION['flash_message'] = [$type, $message];
    $destination = 'messages.php' . ($conversation ? '?id=' . (int) $conversation['id_conversation'] : '');
    if ($conversation && ($_POST['retour'] ?? '') === 'objet' && $conversation['objet']['lien']) {
        $destination = $conversation['objet']['lien'];
    }
    header("Location: $destination#conversation");
    exit();
}

$conversation = $idConversation ? conversationParId($pdo, $idConversation) : null;
if (!$conversation || !estParticipant($conversation, $idUser)) {
    retourConversation(null, 'danger', "Conversation introuvable.");
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifierCsrf()) {
    retourConversation($conversation, 'danger', "Session expirée, merci de réessayer.");
}

$resultat = match ($_POST['action'] ?? '') {
    'envoyer' => envoyerMessage($pdo, $idConversation, $idUser, (string) ($_POST['contenu'] ?? '')),
    'signaler' => signalerMessage($pdo, (int) ($_POST['id_message'] ?? 0), $idUser, (string) ($_POST['motif'] ?? ''), (string) ($_POST['commentaire'] ?? '')),
    default => ['danger', "Action inconnue."],
};
if ($resultat[0] === 'success' && ($_POST['action'] ?? '') === 'envoyer') {
    retourConversation($conversation, 'envoye', '');
}
retourConversation($conversation, $resultat[0], $resultat[1]);
