<?php
require_once 'config.php';
require_once 'compte_lib.php';
require_once 'avis_lib.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$idUser = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT a.*, l.titre, l.auteur, l.couverture,
           (SELECT COALESCE(SUM(e.quantite), 0) FROM exemplaire e WHERE e.id_livre = l.id_livre AND " . conditionOffreMagasin() . ") AS stock
    FROM alerte_stock a
    INNER JOIN livre l ON l.id_livre = a.id_livre
    WHERE a.id_user = ?
    ORDER BY a.date_envoi IS NOT NULL, COALESCE(a.date_envoi, a.date_creation) DESC");
$stmt->execute([$idUser]);
$alertes = $stmt->fetchAll();
$enAttente = array_filter($alertes, fn($a) => $a['date_envoi'] === null);
$csrf = jetonCsrf();

$flash = $_SESSION['flash_compte'] ?? null;
unset($_SESSION['flash_compte']);

include 'header.php';
?>

<div class="container my-4" style="max-width: 860px;">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
        <h1 class="h3 fw-bold mb-0" style="color:#274e13;"><i class="bi bi-bell"></i> Mes alertes</h1>
        <a href="wishlist.php" class="btn btn-outline-success rounded-pill"><i class="bi bi-heart"></i> Mes favoris</a>
    </div>
    <p class="text-muted mb-4">Quand un livre épuisé revient en stock, vous recevez un mail. Pour créer une alerte, ouvrez la page d'un livre épuisé et cliquez sur « M'avertir ».</p>

    <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash[0]) ?> rounded-3"><?= htmlspecialchars($flash[1]) ?></div>
    <?php endif; ?>

    <?php if (!$alertes): ?>
        <div class="card border-0 shadow-sm p-5 text-center text-muted" style="border-radius: 15px;">
            <i class="bi bi-bell-slash fs-1 mb-3"></i>
            Aucune alerte pour le moment.
        </div>
    <?php else: ?>
        <div class="list-group shadow-sm" style="border-radius: 15px; overflow: hidden;">
            <?php foreach ($alertes as $a): ?>
                <div class="list-group-item d-flex flex-wrap align-items-center gap-3 py-3">
                    <img src="<?= htmlspecialchars(urlImage($a['couverture'])) ?>" alt="" class="rounded border" style="width: 45px; height: 62px; object-fit: cover;">
                    <div class="flex-grow-1" style="min-width: 180px;">
                        <a href="detail_livre.php?id=<?= $a['id_livre'] ?>" class="fw-bold text-dark text-decoration-none"><?= htmlspecialchars($a['titre']) ?></a>
                        <div class="small text-muted">
                            <?php if ($a['date_envoi']): ?>
                                <i class="bi bi-envelope-check text-success"></i> Prévenu le <?= date('d/m/Y', strtotime($a['date_envoi'])) ?>
                                <?= $a['stock'] > 0 ? ' · <span class="text-success fw-bold">disponible</span>' : ' · de nouveau épuisé' ?>
                            <?php else: ?>
                                <i class="bi bi-hourglass-split"></i> En attente depuis le <?= date('d/m/Y', strtotime($a['date_creation'])) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <?php if ($a['date_envoi'] && $a['stock'] == 0): ?>
                            <form action="compte_action.php" method="POST">
                                <input type="hidden" name="csrf" value="<?= $csrf ?>">
                                <input type="hidden" name="action" value="alerte_ajouter">
                                <input type="hidden" name="id_livre" value="<?= $a['id_livre'] ?>">
                                <input type="hidden" name="retour" value="alertes">
                                <button class="btn btn-sm btn-outline-success rounded-pill"><i class="bi bi-arrow-repeat"></i> Réactiver</button>
                            </form>
                        <?php endif; ?>
                        <form action="compte_action.php" method="POST">
                            <input type="hidden" name="csrf" value="<?= $csrf ?>">
                            <input type="hidden" name="action" value="alerte_retirer">
                            <input type="hidden" name="id_livre" value="<?= $a['id_livre'] ?>">
                            <input type="hidden" name="retour" value="alertes">
                            <button class="btn btn-sm btn-outline-secondary rounded-pill" title="Supprimer l'alerte"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <p class="small text-muted mt-2"><?= count($enAttente) ?> alerte<?= count($enAttente) > 1 ? 's' : '' ?> en attente.</p>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
