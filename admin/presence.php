<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../points_lib.php';
require_once __DIR__ . '/../evenements_lib.php';
exigerDroit('evenements');

$code = preg_replace('/[^a-f0-9]/', '', $_GET['code'] ?? $_POST['code'] ?? '');
$stmt = $pdo->prepare("
    SELECT i.*, u.pseudo, u.nom, u.prenom FROM evenement_inscription i INNER JOIN user u ON u.id = i.id_user
    WHERE i.code = ?");
$stmt->execute([$code]);
$inscription = $stmt->fetch();
if (!$inscription) {
    rediriger('evenements.php', 'danger', "Ce QR code ne correspond à aucun billet.");
}
$evt = chargerEvenement($pdo, (int) $inscription['id_evenement']);
$retour = "evenement_participants.php?id={$evt['id_evenement']}";

$probleme = match (true) {
    $evt['statut'] !== 'publie' => "Cet événement a été annulé.",
    date('Y-m-d') < substr($evt['date_debut'], 0, 10) => "L'événement n'a lieu que le " . dateLongue($evt['date_debut']) . ".",
    $inscription['statut'] === 'attente' => "Cette personne est sur la liste d'attente : elle n'a pas de place confirmée.",
    $inscription['statut'] !== 'inscrit' => "Cette inscription a été annulée.",
    default => '',
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigerPost("presence.php?code=$code");
    if ($probleme === '' && !$inscription['present']) {
        $pdo->prepare("UPDATE evenement_inscription SET present = 1, date_presence = NOW() WHERE id_inscription = ?")->execute([$inscription['id_inscription']]);
        pointsPresence($pdo, $inscription, $evt['titre'], true);
        insertLog('EVENEMENT', "Présence validée par QR code pour l'inscription #{$inscription['id_inscription']} (événement #{$evt['id_evenement']})");
        rediriger($retour, 'success', "Présence validée pour @{$inscription['pseudo']}.");
    }
    rediriger("presence.php?code=$code");
}

$titrePage = 'Contrôle du billet';
$pageActive = 'evenements';
$filAriane = [[null, 'Communauté'], ['evenements.php', 'Événements']];
include __DIR__ . '/_haut.php';
?>

<div class="carte p-4 mx-auto text-center" style="max-width: 460px;">
    <div class="small text-muted mb-1"><?= e($evt['titre']) ?></div>
    <div class="small text-muted mb-3"><?= ucfirst(horaireEvenement($evt)) ?></div>
    <i class="bi bi-person-badge fs-1" style="color: var(--vert);"></i>
    <h2 class="h4 fw-bold mt-2 mb-0"><?= e(trim($inscription['prenom'] . ' ' . $inscription['nom'])) ?: '@' . e($inscription['pseudo']) ?></h2>
    <div class="text-muted mb-3">@<?= e($inscription['pseudo']) ?> · billet <span class="font-monospace"><?= e($inscription['code_court']) ?></span></div>

    <?php if ($probleme !== ''): ?>
        <div class="alert alert-danger mb-3"><?= e($probleme) ?></div>
    <?php elseif ($inscription['present']): ?>
        <div class="alert alert-info mb-3"><i class="bi bi-check2-all"></i> Billet déjà validé le <?= date('d/m à H:i', strtotime($inscription['date_presence'])) ?>.</div>
    <?php else: ?>
        <form method="POST">
            <?= champCsrf() ?>
            <input type="hidden" name="code" value="<?= e($code) ?>">
            <button class="btn btn-vert btn-lg w-100 fw-bold"><i class="bi bi-check-lg"></i> Valider la présence</button>
        </form>
    <?php endif; ?>
    <a href="<?= e($retour) ?>" class="d-block small mt-3">Voir la liste des participants</a>
</div>

<?php include __DIR__ . '/_bas.php'; ?>
