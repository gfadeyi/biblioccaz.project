<?php
require_once 'config.php';
require_once 'messagerie_lib.php';
require_once 'compte_lib.php';
require_once 'avis_lib.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$idUser = (int) $_SESSION['user_id'];

$messagerieInstallee = true;
$conversations = [];
$conversation = null;
$messages = [];
try {
    $idDemande = (int) ($_GET['id'] ?? 0);
    if ($idDemande) {
        $conversation = conversationParId($pdo, $idDemande);
        if (!$conversation || !estParticipant($conversation, $idUser)) {
            $conversation = null;
            $_SESSION['flash_message'] = ['warning', "Cette conversation n'existe pas ou ne vous concerne pas."];
        } else {
            $messages = messagesConversation($pdo, $idDemande);
            marquerConversationLue($pdo, $idDemande, $idUser);
        }
    }
    $conversations = conversationsUtilisateur($pdo, $idUser);
} catch (PDOException $e) {
    $messagerieInstallee = false;
}

$flash = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);
$csrf = jetonCsrf();
$retourConversation = 'messages';
$flashConversation = $conversation ? $flash : null;
$lienRetourListe = true;
$lienVersObjet = true;
$titreDocument = $conversation ? 'Messages · ' . $conversation['objet']['titre'] : 'Mes messages';
$scriptsPage = ['messagerie.js'];
include 'header.php';
?>

<div class="container mt-4 mb-5 page-messages<?= $conversation ? ' page-messages--fil' : '' ?>">
    <nav aria-label="Fil d'Ariane" class="fil-ariane">
        <a href="profil.php">Mon compte</a>
        <span aria-hidden="true">›</span>
        <?php if ($conversation): ?>
            <a href="messages.php">Mes messages</a>
            <span aria-hidden="true">›</span>
            <span aria-current="page"><?= htmlspecialchars($conversation['objet']['titre']) ?></span>
        <?php else: ?>
            <span aria-current="page">Mes messages</span>
        <?php endif; ?>
    </nav>

    <h1 class="titre-page-compte mb-1">Mes messages</h1>
    <p class="text-muted mb-4">Les échanges liés à vos dons, ventes et emprunts. Une conversation s'ouvre avec chaque transaction.</p>

    <?php if (!$messagerieInstallee): ?>
        <div class="alert alert-warning rounded-3">La messagerie n'est pas encore installée : importez <code>sql/maj_messagerie.sql</code>.</div>
    <?php endif; ?>
    <?php if ($flash && !$conversation && $flash[0] !== 'envoye'): ?>
        <div class="alert alert-<?= htmlspecialchars($flash[0]) ?> rounded-3" role="status"><?= htmlspecialchars($flash[1]) ?></div>
    <?php endif; ?>

    <?php if (!$conversations): ?>
        <div class="carte-compte etat-vide-compte">
            <?= iconeDessin('bulle') ?>
            <h2 class="h5">Aucune conversation pour l'instant</h2>
            <p>Quand vous réservez un don ou qu'un lecteur réserve le vôtre, une conversation s'ouvre ici pour organiser l'échange, sans jamais partager votre adresse email.</p>
            <a href="dons.php" class="bouton bouton-principal bouton-petit">Voir les livres à donner</a>
        </div>
    <?php else: ?>
        <div class="messagerie">
            <nav class="messagerie__liste carte-compte" aria-label="Mes conversations">
                <ul>
                    <?php foreach ($conversations as $c): ?>
                        <?php
                        $active = $conversation && (int) $conversation['id_conversation'] === (int) $c['id_conversation'];
                        $apercu = $c['dernier_masque'] ? 'Message masqué' : (string) $c['dernier_contenu'];
                        if ($c['dernier_auteur'] !== null && (int) $c['dernier_auteur'] === $idUser) {
                            $apercu = 'Vous : ' . $apercu;
                        }
                        ?>
                        <li>
                            <a href="messages.php?id=<?= (int) $c['id_conversation'] ?>#conversation"
                               class="ligne-conversation<?= $c['non_lus'] ? ' ligne-conversation--non-lue' : '' ?><?= $c['statut'] !== 'ouverte' ? ' ligne-conversation--fermee' : '' ?>"
                               <?= $active ? 'aria-current="page"' : '' ?>>
                                <img src="<?= htmlspecialchars($c['objet']['image']) ?>" alt="" class="ligne-conversation__visuel" loading="lazy">
                                <span class="ligne-conversation__texte">
                                    <span class="ligne-conversation__haut">
                                        <span class="ligne-conversation__titre"><?= htmlspecialchars($c['objet']['titre']) ?></span>
                                        <time datetime="<?= date('c', strtotime($c['date_dernier_message'] ?? $c['date_creation'])) ?>"><?= dateMessage($c['date_dernier_message'] ?? $c['date_creation']) ?></time>
                                    </span>
                                    <span class="ligne-conversation__qui">
                                        <?= htmlspecialchars(TYPES_CONVERSATION[$c['type_objet']][0] ?? '') ?> · <?= $c['autre_pseudo'] !== null ? '@' . htmlspecialchars((string) $c['autre_pseudo']) : 'Équipe BIBLIOccaz' ?>
                                        <?php if ($c['statut'] !== 'ouverte'): ?> · <?= htmlspecialchars(STATUTS_CONVERSATION[$c['statut']][0] ?? '') ?><?php endif; ?>
                                    </span>
                                    <span class="ligne-conversation__apercu"><?= htmlspecialchars(mb_strimwidth(str_replace("\n", ' ', $apercu), 0, 90, '…')) ?></span>
                                </span>
                                <?php if ($c['non_lus']): ?>
                                    <span class="ligne-conversation__non-lus"><?= (int) $c['non_lus'] ?><span class="visually-hidden"> non lu<?= $c['non_lus'] > 1 ? 's' : '' ?></span></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>

            <div class="messagerie__fil carte-compte<?= $conversation ? '' : ' messagerie__fil--vide' ?>">
                <?php if ($conversation): ?>
                    <?php include 'partie_conversation.php'; ?>
                <?php else: ?>
                    <div class="messagerie__invite">
                        <?= iconeDessin('bulle') ?>
                        <p>Choisissez une conversation pour lire les messages et répondre.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
