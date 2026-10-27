<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../messagerie_lib.php';
require_once __DIR__ . '/../compte_lib.php';
require_once __DIR__ . '/../livres_lib.php';
exigerDroit('moderation');

$idConversation = (int) ($_GET['id'] ?? 0);
try {
    $conversation = conversationParId($pdo, $idConversation);
} catch (PDOException $e) {
    $conversation = null;
}
if (!$conversation) {
    rediriger('messages.php?onglet=conversations', 'warning', "Cette conversation n'existe pas.");
}
$retour = "conversation.php?id=$idConversation";
$idModerateur = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigerPost($retour);
    $action = $_POST['action'] ?? '';
    $idMessage = (int) ($_POST['id_message'] ?? 0);
    $messageVise = null;
    if ($idMessage) {
        $stmt = $pdo->prepare("SELECT * FROM conversation_message WHERE id_message = ? AND id_conversation = ? AND est_systeme = 0");
        $stmt->execute([$idMessage, $idConversation]);
        $messageVise = $stmt->fetch() ?: null;
    }
    $traiterSignalements = $pdo->prepare("UPDATE message_signalement SET traite = 1, id_traite_par = ?, date_traitement = NOW() WHERE id_message = ? AND traite = 0");

    switch ($action) {
        case 'masquer':
            if (!$messageVise) { rediriger($retour, 'warning', "Message introuvable."); }
            $pdo->prepare("UPDATE conversation_message SET est_masque = 1, id_masque_par = ?, date_masquage = NOW() WHERE id_message = ?")->execute([$idModerateur, $idMessage]);
            $traiterSignalements->execute([$idModerateur, $idMessage]);
            insertLog('MODERATION', "Message #$idMessage masqué (conversation #$idConversation)");
            rediriger("$retour#message-$idMessage", 'success', "Message masqué : il reste conservé ici, mais les membres ne le voient plus.");

        case 'reafficher':
            if (!$messageVise) { rediriger($retour, 'warning', "Message introuvable."); }
            $pdo->prepare("UPDATE conversation_message SET est_masque = 0, id_masque_par = NULL, date_masquage = NULL WHERE id_message = ?")->execute([$idMessage]);
            insertLog('MODERATION', "Message #$idMessage réaffiché (conversation #$idConversation)");
            rediriger("$retour#message-$idMessage", 'success', "Message de nouveau visible.");

        case 'classer':
            if (!$messageVise) { rediriger($retour, 'warning', "Message introuvable."); }
            $traiterSignalements->execute([$idModerateur, $idMessage]);
            insertLog('MODERATION', "Signalement du message #$idMessage classé sans suite");
            rediriger('messages.php', 'success', "Signalement classé sans suite.");

        case 'clore':
            $motif = mb_substr(trim($_POST['motif'] ?? ''), 0, 255);
            if ($motif === '') {
                rediriger($retour, 'warning', "Indiquez la raison de la fermeture : elle s'affiche dans la conversation.");
            }
            if (!fermerConversation($pdo, $idConversation, "Conversation close par l'équipe de modération : $motif", 'close', $motif)) {
                rediriger($retour, 'warning', "Cette conversation est déjà terminée.");
            }
            insertLog('MODERATION', "Conversation #$idConversation close ($motif)");
            rediriger($retour, 'success', "Conversation close : les participants peuvent la relire mais plus y écrire.");

        case 'suspendre':
            $idMembre = (int) ($_POST['id_user'] ?? 0);
            $participant = $conversation['participants'][$idMembre] ?? null;
            $stmt = $pdo->prepare("SELECT role, statut, pseudo FROM `user` WHERE id = ?");
            $stmt->execute([$idMembre]);
            $membre = $stmt->fetch();
            if (!$participant || !$membre || !in_array($membre['role'], ['client', 'auteur'], true)) {
                rediriger($retour, 'warning', "Seul le compte d'un lecteur ou d'un auteur peut être suspendu ici.");
            }
            if (in_array($membre['statut'], STATUTS_COMPTE_SUSPENDU, true)) {
                rediriger($retour, 'info', "Ce compte est déjà suspendu.");
            }
            $pdo->prepare("UPDATE `user` SET statut = 'refuse_temporaire' WHERE id = ?")->execute([$idMembre]);
            insertLog('MODERATION', "Compte de @{$membre['pseudo']} suspendu depuis la conversation #$idConversation");
            rediriger($retour, 'success', "Le compte de @{$membre['pseudo']} est suspendu : il ne peut plus se connecter ni écrire. Un administrateur peut le réactiver depuis Membres.");
    }
    rediriger($retour);
}

$messages = messagesConversation($pdo, $idConversation);
$stmt = $pdo->prepare("
    SELECT s.id_message, s.motif, s.commentaire, s.traite, s.date_signalement, u.pseudo
    FROM message_signalement s
    INNER JOIN conversation_message m ON m.id_message = s.id_message
    INNER JOIN `user` u ON u.id = s.id_user
    WHERE m.id_conversation = ? ORDER BY s.date_signalement");
$stmt->execute([$idConversation]);
$signalements = [];
foreach ($stmt->fetchAll() as $signalement) {
    $signalements[(int) $signalement['id_message']][] = $signalement;
}
$stmt = $pdo->prepare("SELECT id, role, statut FROM `user` WHERE id IN (" . implode(',', array_map('intval', array_keys($conversation['participants'])) ?: '0') . ")");
$stmt->execute();
$comptes = [];
foreach ($stmt->fetchAll() as $compte) {
    $comptes[(int) $compte['id']] = $compte;
}
insertLog('MODERATION', "Lecture de la conversation #$idConversation par l'équipe");

$titrePage = 'Conversation';
$pageActive = 'messages';
$sousTitrePage = (TYPES_CONVERSATION[$conversation['type_objet']][0] ?? '') . ' · ' . $conversation['objet']['titre'];
include __DIR__ . '/_haut.php';
?>

<a href="messages.php" class="d-inline-flex align-items-center gap-1 mb-3 text-success fw-semibold text-decoration-none"><i class="bi bi-arrow-left" aria-hidden="true"></i> Retour aux messages</a>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="carte">
            <div class="carte-entete">
                <h2>Fil de la conversation</h2>
                <span class="badge rounded-pill <?= $conversation['statut'] === 'ouverte' ? 'bg-success-subtle text-success-emphasis' : ($conversation['statut'] === 'close' ? 'bg-danger-subtle text-danger-emphasis' : 'bg-light text-dark border') ?>"><?= e(STATUTS_CONVERSATION[$conversation['statut']][0] ?? $conversation['statut']) ?></span>
            </div>
            <div class="carte-corps fil-admin">
                <?php foreach ($messages as $message): ?>
                    <?php $idMessage = (int) $message['id_message']; ?>
                    <?php if ($message['est_systeme']): ?>
                        <p class="fil-admin__systeme"><?= e($message['contenu']) ?> · <?= date('d/m/Y H:i', strtotime($message['date_envoi'])) ?></p>
                    <?php else: ?>
                        <?php $signalementsMessage = $signalements[$idMessage] ?? []; $enAttente = array_filter($signalementsMessage, fn($s) => !$s['traite']); ?>
                        <div class="fil-admin__message<?= $enAttente ? ' fil-admin__message--signale' : '' ?><?= $message['est_masque'] ? ' fil-admin__message--masque' : '' ?>" id="message-<?= $idMessage ?>">
                            <div class="d-flex flex-wrap justify-content-between gap-2 small mb-1">
                                <strong>@<?= e((string) $message['auteur_pseudo']) ?></strong>
                                <span class="text-muted"><?= date('d/m/Y H:i', strtotime($message['date_envoi'])) ?><?= $message['est_masque'] ? ' · masqué' : '' ?></span>
                            </div>
                            <div class="fil-admin__texte"><?= nl2br(e($message['contenu'])) ?></div>
                            <?php foreach ($signalementsMessage as $s): ?>
                                <p class="fil-admin__signalement<?= $s['traite'] ? ' traite' : '' ?>">
                                    <i class="bi bi-flag" aria-hidden="true"></i> @<?= e($s['pseudo']) ?> : <?= e(MOTIFS_SIGNALEMENT_MESSAGE[$s['motif']] ?? $s['motif']) ?><?= $s['commentaire'] ? ' — « ' . e($s['commentaire']) . ' »' : '' ?><?= $s['traite'] ? ' (traité)' : '' ?>
                                </p>
                            <?php endforeach; ?>
                            <div class="d-flex flex-wrap gap-2 mt-2">
                                <form method="POST">
                                    <?= champCsrf() ?>
                                    <input type="hidden" name="id_message" value="<?= $idMessage ?>">
                                    <input type="hidden" name="action" value="<?= $message['est_masque'] ? 'reafficher' : 'masquer' ?>">
                                    <button class="btn btn-sm <?= $message['est_masque'] ? 'btn-outline-secondary' : 'btn-outline-danger' ?>">
                                        <i class="bi bi-<?= $message['est_masque'] ? 'eye' : 'eye-slash' ?>" aria-hidden="true"></i> <?= $message['est_masque'] ? 'Réafficher' : 'Masquer' ?>
                                    </button>
                                </form>
                                <?php if ($enAttente && !$message['est_masque']): ?>
                                    <form method="POST">
                                        <?= champCsrf() ?>
                                        <input type="hidden" name="id_message" value="<?= $idMessage ?>">
                                        <input type="hidden" name="action" value="classer">
                                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-check2" aria-hidden="true"></i> Classer sans suite</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="carte p-4 mb-3">
            <h2 class="h6 fw-bold mb-3">Participants</h2>
            <?php foreach ($conversation['participants'] as $idMembre => $participant): ?>
                <?php $compte = $comptes[$idMembre] ?? ['role' => '', 'statut' => '']; $suspendu = in_array($compte['statut'], STATUTS_COMPTE_SUSPENDU, true); ?>
                <div class="d-flex align-items-center gap-2 mb-3">
                    <?= avatarMembre($participant['pseudo'], $participant['photo'], 'avatar-admin', '../') ?>
                    <div class="flex-grow-1 small">
                        <div class="fw-bold">@<?= e($participant['pseudo']) ?></div>
                        <div class="text-muted"><?= e(ROLES_PARTICIPANT[$participant['role_participant']] ?? $participant['role_participant']) ?><?= $suspendu ? ' · <span class="text-danger">suspendu</span>' : '' ?></div>
                    </div>
                    <?php if (!$suspendu && in_array($compte['role'], ['client', 'auteur'], true)): ?>
                        <form method="POST" data-confirmer="Suspendre le compte de @<?= e($participant['pseudo']) ?> ? La personne ne pourra plus se connecter ; ses annonces disparaissent du site." data-titre-confirmer="Suspendre le compte" data-bouton-confirmer="Suspendre">
                            <?= champCsrf() ?>
                            <input type="hidden" name="action" value="suspendre">
                            <input type="hidden" name="id_user" value="<?= $idMembre ?>">
                            <button class="btn btn-sm btn-outline-danger" aria-label="Suspendre le compte de @<?= e($participant['pseudo']) ?>"><i class="bi bi-slash-circle" aria-hidden="true"></i></button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            <?php if ($conversation['type_objet'] === 'vente'): ?>
                <a href="ventes.php?id=<?= (int) $conversation['id_objet'] ?>" class="small"><i class="bi bi-tag" aria-hidden="true"></i> Voir la vente (valider, annuler, litige)</a>
            <?php elseif ($conversation['objet']['lien']): ?>
                <a href="../<?= e($conversation['objet']['lien']) ?>" target="_blank" rel="noopener" class="small"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Voir sur le site</a>
            <?php endif; ?>
        </div>

        <?php if ($conversation['statut'] === 'ouverte'): ?>
            <form method="POST" class="carte p-4">
                <?= champCsrf() ?>
                <input type="hidden" name="action" value="clore">
                <h2 class="h6 fw-bold mb-2">Clore la conversation</h2>
                <p class="small text-muted">Les participants pourront la relire mais plus y écrire. La raison s'affiche dans le fil.</p>
                <label for="motif-cloture" class="form-label small fw-bold">Raison</label>
                <textarea id="motif-cloture" name="motif" class="form-control form-control-sm mb-2" rows="2" maxlength="255" required placeholder="Ex. : échanges insultants, transaction en litige…"></textarea>
                <button class="btn btn-sm btn-danger"><i class="bi bi-lock" aria-hidden="true"></i> Clore la conversation</button>
            </form>
        <?php elseif ($conversation['motif_fermeture']): ?>
            <div class="carte p-4 small"><strong>Close par l'équipe :</strong> <?= e($conversation['motif_fermeture']) ?></div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/_bas.php'; ?>
