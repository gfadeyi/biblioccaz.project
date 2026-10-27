<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../messagerie_lib.php';
exigerDroit('moderation');

$onglets = ['signales' => 'Signalés', 'conversations' => 'Conversations', 'traites' => 'Signalements traités'];
$onglet = isset($onglets[$_GET['onglet'] ?? '']) ? $_GET['onglet'] : 'signales';
$q = trim($_GET['q'] ?? '');

$tablePresente = true;
$lignes = [];
$nombres = ['signales' => 0, 'conversations' => 0, 'traites' => 0];
try {
    $nombres['signales'] = nombreSignalementsMessages($pdo);
    $nombres['conversations'] = (int) $pdo->query("SELECT COUNT(*) FROM conversation")->fetchColumn();
    $nombres['traites'] = (int) $pdo->query("SELECT COUNT(DISTINCT id_message) FROM message_signalement WHERE traite = 1")->fetchColumn();

    if ($onglet === 'conversations') {
        $sql = "
            SELECT c.*, GROUP_CONCAT(u.pseudo ORDER BY p.role_participant SEPARATOR ' · ') AS pseudos,
                   (SELECT COUNT(*) FROM conversation_message m WHERE m.id_conversation = c.id_conversation AND m.est_systeme = 0) AS nb_messages,
                   (SELECT COUNT(*) FROM message_signalement s INNER JOIN conversation_message m ON m.id_message = s.id_message
                    WHERE m.id_conversation = c.id_conversation AND s.traite = 0) AS nb_signalements
            FROM conversation c
            INNER JOIN conversation_participant p ON p.id_conversation = c.id_conversation
            INNER JOIN `user` u ON u.id = p.id_user
            GROUP BY c.id_conversation";
        $parametres = [];
        if ($q !== '') {
            $sql .= " HAVING pseudos LIKE ?";
            $parametres[] = '%' . $q . '%';
        }
        $stmt = $pdo->prepare($sql . " ORDER BY c.date_dernier_message DESC LIMIT 100");
        $stmt->execute($parametres);
        $lignes = $stmt->fetchAll();
        foreach ($lignes as &$ligne) {
            $ligne['objet'] = infosObjetConversation($pdo, $ligne['type_objet'], (int) $ligne['id_objet']);
        }
        unset($ligne);
    } else {
        $stmt = $pdo->prepare("
            SELECT m.id_message, m.id_conversation, m.contenu, m.date_envoi, m.est_masque, a.pseudo AS auteur_pseudo, a.statut AS auteur_statut,
                   COUNT(s.id_signalement) AS nb_signalements, MAX(s.date_signalement) AS dernier_signalement,
                   GROUP_CONCAT(DISTINCT s.motif SEPARATOR ',') AS motifs,
                   GROUP_CONCAT(CONCAT('@', r.pseudo, IF(s.commentaire IS NULL, '', CONCAT(' : ', s.commentaire))) SEPARATOR '\n') AS details
            FROM message_signalement s
            INNER JOIN conversation_message m ON m.id_message = s.id_message
            LEFT JOIN `user` a ON a.id = m.id_auteur
            INNER JOIN `user` r ON r.id = s.id_user
            WHERE s.traite = ?
            GROUP BY m.id_message
            ORDER BY dernier_signalement DESC
            LIMIT 100");
        $stmt->execute([$onglet === 'traites' ? 1 : 0]);
        $lignes = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    $tablePresente = false;
}

$titrePage = 'Messages';
$pageActive = 'messages';
$sousTitrePage = "Conversations entre membres liées aux dons, ventes et emprunts. L'équipe les lit seulement en cas de signalement ou de litige.";
include __DIR__ . '/_haut.php';
?>

<?php if (!$tablePresente): ?>
    <div class="alert alert-warning">La messagerie n'est pas encore installée : importez <code>sql/maj_messagerie.sql</code>.</div>
<?php endif; ?>

<ul class="nav nav-pills mb-3 flex-wrap">
    <?php foreach ($onglets as $cle => $libelle): ?>
        <li class="nav-item">
            <a class="nav-link <?= $onglet === $cle ? 'active bg-success' : 'text-success' ?>" href="?onglet=<?= $cle ?>">
                <?= e($libelle) ?> <span class="badge rounded-pill <?= $onglet === $cle ? 'bg-light text-success' : 'bg-success-subtle text-success-emphasis' ?>"><?= $nombres[$cle] ?></span>
            </a>
        </li>
    <?php endforeach; ?>
</ul>

<?php if ($onglet === 'conversations'): ?>
    <form class="d-flex gap-2 mb-3" method="GET">
        <input type="hidden" name="onglet" value="conversations">
        <input type="search" name="q" value="<?= e($q) ?>" class="form-control" aria-label="Rechercher une conversation" style="max-width: 320px;" placeholder="Pseudo d'un participant">
        <button class="btn btn-outline-secondary" aria-label="Rechercher"><i class="bi bi-search" aria-hidden="true"></i></button>
    </form>
    <div class="carte">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="table-light small text-uppercase">
                    <tr><th>Objet</th><th>Participants</th><th>Messages</th><th>Dernière activité</th><th>Statut</th><th class="text-end">Lire</th></tr>
                </thead>
                <tbody>
                    <?php if (!$lignes): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">Aucune conversation.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($lignes as $c): ?>
                        <tr>
                            <td>
                                <div class="fw-bold"><?= e($c['objet']['titre']) ?></div>
                                <div class="small text-muted"><?= e(TYPES_CONVERSATION[$c['type_objet']][0] ?? $c['type_objet']) ?> #<?= (int) $c['id_objet'] ?></div>
                            </td>
                            <td class="small"><?= e('@' . str_replace(' · ', ' · @', (string) $c['pseudos'])) ?></td>
                            <td class="small">
                                <?= (int) $c['nb_messages'] ?>
                                <?php if ($c['nb_signalements']): ?><span class="badge rounded-pill bg-danger-subtle text-danger-emphasis ms-1"><i class="bi bi-flag" aria-hidden="true"></i> <?= (int) $c['nb_signalements'] ?></span><?php endif; ?>
                            </td>
                            <td class="small"><?= $c['date_dernier_message'] ? date('d/m/Y H:i', strtotime($c['date_dernier_message'])) : '—' ?></td>
                            <td><span class="badge rounded-pill <?= $c['statut'] === 'ouverte' ? 'bg-success-subtle text-success-emphasis' : ($c['statut'] === 'close' ? 'bg-danger-subtle text-danger-emphasis' : 'bg-light text-dark border') ?>"><?= e(STATUTS_CONVERSATION[$c['statut']][0] ?? $c['statut']) ?></span></td>
                            <td class="text-end"><a href="conversation.php?id=<?= (int) $c['id_conversation'] ?>" class="btn btn-sm btn-outline-secondary" aria-label="Lire la conversation"><i class="bi bi-eye" aria-hidden="true"></i></a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <?php if (!$lignes): ?>
        <div class="carte p-5 text-center">
            <i class="bi bi-check2-circle fs-1 text-success" aria-hidden="true"></i>
            <p class="text-muted mb-0 mt-2"><?= $onglet === 'signales' ? 'Aucun message signalé.' : 'Aucun signalement traité pour le moment.' ?></p>
        </div>
    <?php endif; ?>
    <div class="row g-3">
        <?php foreach ($lignes as $m): ?>
            <div class="col-xl-6">
                <article class="carte p-3 h-100 d-flex flex-column">
                    <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                        <div>
                            <strong>@<?= e((string) $m['auteur_pseudo']) ?></strong>
                            <?php if (in_array($m['auteur_statut'], ['refuse_temporaire', 'refuse_definitif'], true)): ?>
                                <span class="badge rounded-pill bg-danger-subtle text-danger-emphasis">Compte suspendu</span>
                            <?php endif; ?>
                            <div class="small text-muted">Envoyé le <?= date('d/m/Y à H:i', strtotime($m['date_envoi'])) ?></div>
                        </div>
                        <span class="badge rounded-pill bg-danger-subtle text-danger-emphasis align-self-start"><i class="bi bi-flag" aria-hidden="true"></i> <?= (int) $m['nb_signalements'] ?> signalement<?= $m['nb_signalements'] > 1 ? 's' : '' ?></span>
                    </div>
                    <blockquote class="message-signale<?= $m['est_masque'] ? ' message-signale--masque' : '' ?>"><?= nl2br(e($m['contenu'])) ?></blockquote>
                    <div class="d-flex flex-wrap gap-1 mb-2">
                        <?php foreach (array_filter(explode(',', (string) $m['motifs'])) as $motif): ?>
                            <span class="badge rounded-pill bg-light text-dark border"><?= e(MOTIFS_SIGNALEMENT_MESSAGE[$motif] ?? $motif) ?></span>
                        <?php endforeach; ?>
                        <?php if ($m['est_masque']): ?><span class="badge rounded-pill bg-light text-dark border"><i class="bi bi-eye-slash" aria-hidden="true"></i> Masqué</span><?php endif; ?>
                    </div>
                    <p class="small text-muted mb-3" style="white-space: pre-line;">Signalé par <?= e((string) $m['details']) ?></p>
                    <div class="mt-auto">
                        <a href="conversation.php?id=<?= (int) $m['id_conversation'] ?>#message-<?= (int) $m['id_message'] ?>" class="btn btn-sm btn-success fw-bold"><i class="bi bi-chat-dots" aria-hidden="true"></i> Lire la conversation et décider</a>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/_bas.php'; ?>
