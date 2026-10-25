<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM commande WHERE id_user = ? ORDER BY date_commande DESC, id_commande DESC");
$stmt->execute([$_SESSION['user_id']]);
$commandes = $stmt->fetchAll();

$lignesParCommande = [];
if ($commandes) {
    $ids = array_column($commandes, 'id_commande');
    $place = implode(',', array_fill(0, count($ids), '?'));
    $stmtL = $pdo->prepare("
        SELECT cl.*, l.couverture
        FROM commande_ligne cl
        LEFT JOIN livre l ON l.id_livre = cl.id_livre
        WHERE cl.id_commande IN ($place)
        ORDER BY cl.id_ligne");
    $stmtL->execute($ids);
    foreach ($stmtL->fetchAll() as $ligne) {
        $lignesParCommande[$ligne['id_commande']][] = $ligne;
    }
}

function etatLisible($etat) {
    $e = strtolower($etat);
    if ($e == 'tres bon' || $e == 'très bon') return 'Très bon état';
    if ($e == 'bon') return 'Bon état';
    if ($e == 'neuf') return 'Neuf';
    if ($e == 'use' || $e == 'usé') return 'État usé';
    return ucfirst($e);
}

include 'header.php';
?>

<div class="container mt-5 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Mes commandes</h2>
        <a href="profil.php" class="btn btn-outline-secondary rounded-pill px-4"><i class="bi bi-arrow-left me-1"></i> Mon compte</a>
    </div>

    <?php if (empty($commandes)): ?>
        <div class="card p-5 text-center shadow-sm border-0 bg-white" style="border-radius: 15px;">
            <i class="bi bi-bag text-muted fs-1 mb-3"></i>
            <h5 class="text-secondary">Vous n'avez pas encore passé de commande.</h5>
            <a href="index.php" class="btn btn-success rounded-pill px-4 mt-3 mx-auto">Découvrir nos livres</a>
        </div>
    <?php else: ?>
        <?php foreach ($commandes as $cmd): ?>
            <div class="card border-0 shadow-sm bg-white mb-4" style="border-radius: 15px;">
                <div class="card-header bg-light border-0 d-flex flex-wrap justify-content-between align-items-center gap-2 p-3" style="border-radius: 15px 15px 0 0;">
                    <div>
                        <span class="fw-bold">Commande n°<?= $cmd['id_commande'] ?></span>
                        <span class="text-muted small ms-2"><?= date('d/m/Y à H:i', strtotime($cmd['date_commande'])) ?></span>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <?php if ($cmd['points_gagnes'] > 0): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">+<?= $cmd['points_gagnes'] ?> points</span>
                        <?php endif; ?>
                        <span class="fw-bold text-success fs-5"><?= number_format($cmd['total'], 2) ?> €</span>
                    </div>
                </div>
                <div class="card-body p-3">
                    <?php foreach ($lignesParCommande[$cmd['id_commande']] ?? [] as $l): ?>
                        <div class="d-flex align-items-center mb-2">
                            <?php if (!empty($l['couverture'])): ?>
                                <img src="img/<?= htmlspecialchars($l['couverture']) ?>" class="rounded border" style="width: 40px; height: 55px; object-fit: cover;">
                            <?php else: ?>
                                <div class="rounded border bg-light d-flex align-items-center justify-content-center" style="width: 40px; height: 55px;"><i class="bi bi-book text-muted"></i></div>
                            <?php endif; ?>
                            <div class="ms-3">
                                <div class="fw-bold small"><?= htmlspecialchars($l['titre']) ?></div>
                                <div class="text-muted small"><?= etatLisible($l['etat']) ?> · <?= $l['quantite'] ?> × <?= number_format($l['prix_unitaire'], 2) ?> €</div>
                            </div>
                            <div class="ms-auto fw-medium small"><?= number_format($l['prix_unitaire'] * $l['quantite'], 2) ?> €</div>
                        </div>
                    <?php endforeach; ?>
                    <hr class="my-2">
                    <div class="small text-secondary">
                        <?php if ($cmd['remise'] > 0): ?>Remise : -<?= number_format($cmd['remise'], 2) ?> € · <?php endif; ?>
                        Livraison : <?= $cmd['frais_livraison'] > 0 ? number_format($cmd['frais_livraison'], 2) . ' €' : 'offerte' ?>
                        · Livré à <?= htmlspecialchars($cmd['prenom'] . ' ' . $cmd['nom']) ?>, <?= htmlspecialchars($cmd['adresse']) ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
